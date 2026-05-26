<?php

namespace App\Http\Controllers;

use App\Http\Requests\DailyReportRequest;
use App\Models\DailyReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class DailyReportController extends Controller
{
    /**
     * "Laporan Tim" — menampilkan semua anggota tim yang dikelola user untuk
     * tanggal tertentu (default: hari ini). Anggota yang belum mengirim
     * laporan tetap tampil dengan penanda "Belum Kirim".
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $user->level === User::LEVEL_STAFF) {
            return redirect()->route('daily-reports.mine');
        }

        try {
            $date = $request->filled('date')
                ? Carbon::parse($request->input('date'))->toDateString()
                : now()->toDateString();
        } catch (\Throwable $e) {
            $date = now()->toDateString();
        }

        $teamQuery = $this->teamMembersQuery($user);

        if ($request->filled('name')) {
            $name = $request->string('name');
            $teamQuery->where('name', 'like', "%{$name}%");
        }
        if ($request->filled('division')) {
            $division = $request->string('division');
            $teamQuery->where('division', 'like', "%{$division}%");
        }

        $teamQuery->with(['dailyReports' => function ($q) use ($date) {
            $q->whereDate('report_date', $date);
        }]);

        $members = $teamQuery
            ->orderBy('level')
            ->orderBy('division')
            ->orderBy('name')
            ->get();

        $rows = $members->map(fn ($member) => (object) [
            'user' => $member,
            'report' => $member->dailyReports->first(),
        ])->values();

        $perPage = 15;
        $page = max(1, (int) $request->input('page', 1));
        $rowsPaginated = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $visibleDivisions = $user->visibleDivisions();
        $divisions = $visibleDivisions === null
            ? collect(User::DIVISIONS)
            : collect($visibleDivisions);

        $summary = [
            'total' => $rows->count(),
            'submitted' => $rows->filter(fn ($r) => (bool) $r->report)->count(),
            'missing' => $rows->filter(fn ($r) => ! $r->report)->count(),
        ];

        return view('daily-reports.index', [
            'rows' => $rowsPaginated,
            'divisions' => $divisions,
            'scope' => 'team',
            'selectedDate' => $date,
            'summary' => $summary,
        ]);
    }

    /**
     * Query users yang dikelola oleh user yang login (sebagai "tim").
     * Mengikuti aturan yang sama dengan DailyReport::scopeVisibleTo.
     */
    private function teamMembersQuery(User $user): Builder
    {
        $query = User::query()
            ->where('is_active', true)
            ->where('id', '!=', $user->id);

        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->level === User::LEVEL_OWNER) {
            return $query->whereIn('level', [
                User::LEVEL_MANAGER,
                User::LEVEL_LEADER,
                User::LEVEL_STAFF,
            ]);
        }

        if ($user->level === User::LEVEL_MANAGER) {
            $divisions = $user->visibleDivisions() ?? [];
            $query->whereIn('level', [User::LEVEL_LEADER, User::LEVEL_STAFF]);
            if (empty($divisions)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('division', $divisions);
            }

            return $query;
        }

        if ($user->level === User::LEVEL_LEADER) {
            $divisions = $user->visibleDivisions() ?? [];
            $query->where('level', User::LEVEL_STAFF);
            if (empty($divisions)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('division', $divisions);
            }

            return $query;
        }

        return $query->whereRaw('1 = 0');
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
            'reports' => $reports,
            'divisions' => collect(),
            'scope' => 'mine',
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
            $data['overtime_end'] = null;
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
            $data['overtime_end'] = null;
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
