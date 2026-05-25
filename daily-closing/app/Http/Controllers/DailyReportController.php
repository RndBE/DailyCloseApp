<?php

namespace App\Http\Controllers;

use App\Http\Requests\DailyReportRequest;
use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DailyReportController extends Controller
{
    /**
     * "Laporan Tim" — laporan dari user di bawah level user yang login.
     * Staff (yang tidak punya bawahan) di-redirect ke halaman "Laporan Saya".
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $user->level === User::LEVEL_STAFF) {
            return redirect()->route('daily-reports.mine');
        }

        $query = DailyReport::query()
            ->with('user')
            ->visibleTo($user)
            ->where('user_id', '!=', $user->id);

        $this->applyFilters($query, $request, includeUserFilters: true);

        $reports = $query->latest('report_date')->latest('id')->paginate(15)->withQueryString();

        $divisions = User::query()
            ->whereNotNull('division')
            ->distinct()
            ->orderBy('division')
            ->pluck('division');

        return view('daily-reports.index', [
            'reports'   => $reports,
            'divisions' => $divisions,
            'scope'     => 'team',
        ]);
    }

    /**
     * "Laporan Saya" — hanya laporan milik user yang login.
     */
    public function mine(Request $request): View
    {
        $user = $request->user();

        $query = DailyReport::query()
            ->with('user')
            ->where('user_id', $user->id);

        $this->applyFilters($query, $request, includeUserFilters: false);

        $reports = $query->latest('report_date')->latest('id')->paginate(15)->withQueryString();

        return view('daily-reports.index', [
            'reports'   => $reports,
            'divisions' => collect(),
            'scope'     => 'mine',
        ]);
    }

    private function applyFilters(Builder $query, Request $request, bool $includeUserFilters): void
    {
        if ($request->filled('date_from')) {
            $query->whereDate('report_date', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('report_date', '<=', $request->date('date_to'));
        }
        if ($includeUserFilters && $request->filled('name')) {
            $name = $request->string('name');
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$name}%"));
        }
        if ($includeUserFilters && $request->filled('division')) {
            $division = $request->string('division');
            $query->whereHas('user', fn ($q) => $q->where('division', 'like', "%{$division}%"));
        }
        if ($request->filled('overtime')) {
            $query->where('overtime_status', $request->boolean('overtime'));
        }
        if ($request->filled('need_help')) {
            $query->where('need_leader_help', $request->boolean('need_help'));
        }
    }

    public function create(): View
    {
        return view('daily-reports.create', [
            'report' => new DailyReport(['report_date' => now()->toDateString()]),
        ]);
    }

    public function store(DailyReportRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        if (! $data['overtime_status']) {
            $data['overtime_start'] = null;
            $data['overtime_end']   = null;
        }
        if (! $data['need_leader_help']) {
            $data['leader_help_description'] = null;
        }

        $report = DailyReport::create($data);

        return redirect()->route('daily-reports.show', $report)
            ->with('success', 'Laporan harian berhasil disimpan.');
    }

    public function show(Request $request, DailyReport $dailyReport): View
    {
        abort_unless($dailyReport->isVisibleTo($request->user()), 403, 'Anda tidak berhak melihat laporan ini.');

        $dailyReport->load('user');

        return view('daily-reports.show', ['report' => $dailyReport]);
    }

    public function edit(Request $request, DailyReport $dailyReport): View
    {
        abort_unless($dailyReport->user_id === $request->user()->id, 403, 'Anda hanya bisa mengedit laporan milik sendiri.');

        return view('daily-reports.edit', ['report' => $dailyReport]);
    }

    public function update(DailyReportRequest $request, DailyReport $dailyReport): RedirectResponse
    {
        abort_unless($dailyReport->user_id === $request->user()->id, 403, 'Anda hanya bisa mengedit laporan milik sendiri.');

        $data = $request->validated();

        if (! $data['overtime_status']) {
            $data['overtime_start'] = null;
            $data['overtime_end']   = null;
        }
        if (! $data['need_leader_help']) {
            $data['leader_help_description'] = null;
        }

        $dailyReport->update($data);

        return redirect()->route('daily-reports.show', $dailyReport)
            ->with('success', 'Laporan berhasil diperbarui.');
    }

    public function destroy(Request $request, DailyReport $dailyReport): RedirectResponse
    {
        abort_unless($dailyReport->user_id === $request->user()->id, 403, 'Anda hanya bisa menghapus laporan milik sendiri.');

        $dailyReport->delete();

        return redirect()->route('daily-reports.mine')->with('success', 'Laporan dihapus.');
    }
}
