<?php

namespace App\Http\Controllers;

use App\Http\Requests\DailyReportRequest;
use App\Models\DailyReport;
use App\Models\Holiday;
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
    private function managerRowsQuery(User $user, string $date): \Illuminate\Support\Collection
    {
        if (!$user->isSuperAdmin() && !in_array($user->level, [User::LEVEL_OWNER, User::LEVEL_MANAGER], true)) {
            return collect();
        }

        $query = User::query()
            ->where('is_active', true)
            ->where('level', User::LEVEL_MANAGER);

        if (!$user->isSuperAdmin() && $user->level !== User::LEVEL_OWNER) {
            $divisions = $user->visibleDivisions() ?? [];
            if (empty($divisions)) {
                return collect();
            }
            $query->whereIn('division', $divisions);
        }

        return $query
            ->with(['dailyReports' => fn ($q) => $q->whereDate('report_date', $date)])
            ->orderBy('name')
            ->get()
            ->map(fn ($m) => (object) [
                'user'   => $m,
                'report' => $m->dailyReports->first(),
            ]);
    }

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

    public function rangkuman(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user->isSuperAdmin() || in_array($user->level, [User::LEVEL_OWNER, User::LEVEL_MANAGER, User::LEVEL_LEADER], true),
            403,
            'Anda tidak berhak mengakses halaman ini.'
        );

        try {
            $date = $request->filled('date')
                ? Carbon::parse($request->input('date'))->toDateString()
                : now()->toDateString();
        } catch (\Throwable $e) {
            $date = now()->toDateString();
        }

        $members = $this->teamMembersQuery($user)
            ->with(['dailyReports' => fn ($q) => $q->whereDate('report_date', $date)])
            ->orderBy('division')
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        // Managers get a dedicated section; exclude them from per-division breakdown
        $rows = $members
            ->filter(fn ($m) => $m->level !== User::LEVEL_MANAGER)
            ->map(fn ($member) => (object) [
                'user'   => $member,
                'report' => $member->dailyReports->first(),
            ]);

        // Leader includes their own report alongside their team's reports
        if ($user->level === User::LEVEL_LEADER) {
            $selfReport = DailyReport::where('user_id', $user->id)->whereDate('report_date', $date)->first();
            $rows = collect([(object) ['user' => $user, 'report' => $selfReport]])->merge($rows);
        }

        $byDivision = $rows->groupBy(fn ($row) => $row->user->division ?? 'Tanpa Divisi');

        $managerRows = $this->managerRowsQuery($user, $date);

        $allRows = $rows->values()->merge($managerRows);
        $totalStats = [
            'total'     => $allRows->count(),
            'submitted' => $allRows->filter(fn ($r) => $r->report)->count(),
            'missing'   => $allRows->filter(fn ($r) => ! $r->report)->count(),
            'overtime'  => $allRows->filter(fn ($r) => $r->report?->overtime_status)->count(),
            'help'      => $allRows->filter(fn ($r) => $r->report?->need_leader_help)->count(),
            'late'      => $allRows->filter(fn ($r) => $r->report?->is_late)->count(),
        ];

        return view('daily-reports.rangkuman', [
            'byDivision'   => $byDivision,
            'managerRows'  => $managerRows,
            'totalStats'   => $totalStats,
            'selectedDate' => $date,
        ]);
    }

    public function rangkumanCetak(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user->isSuperAdmin() || in_array($user->level, [User::LEVEL_OWNER, User::LEVEL_MANAGER, User::LEVEL_LEADER], true),
            403
        );

        try {
            $date = $request->filled('date')
                ? Carbon::parse($request->input('date'))->toDateString()
                : now()->toDateString();
        } catch (\Throwable $e) {
            $date = now()->toDateString();
        }

        $members = $this->teamMembersQuery($user)
            ->with(['dailyReports' => fn ($q) => $q->whereDate('report_date', $date)])
            ->orderBy('division')
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $rows = $members
            ->filter(fn ($m) => $m->level !== User::LEVEL_MANAGER)
            ->map(fn ($member) => (object) [
                'user'   => $member,
                'report' => $member->dailyReports->first(),
            ]);

        if ($user->level === User::LEVEL_LEADER) {
            $selfReport = DailyReport::where('user_id', $user->id)->whereDate('report_date', $date)->first();
            $rows = collect([(object) ['user' => $user, 'report' => $selfReport]])->merge($rows);
        }

        $byDivision = $rows->groupBy(fn ($row) => $row->user->division ?? 'Tanpa Divisi');

        $managerRows = $this->managerRowsQuery($user, $date);

        $allRows = $rows->values()->merge($managerRows);
        $totalStats = [
            'total'     => $allRows->count(),
            'submitted' => $allRows->filter(fn ($r) => $r->report)->count(),
            'missing'   => $allRows->filter(fn ($r) => ! $r->report)->count(),
            'overtime'  => $allRows->filter(fn ($r) => $r->report?->overtime_status)->count(),
            'help'      => $allRows->filter(fn ($r) => $r->report?->need_leader_help)->count(),
            'late'      => $allRows->filter(fn ($r) => $r->report?->is_late)->count(),
        ];

        return view('daily-reports.rangkuman-cetak', [
            'byDivision'   => $byDivision,
            'managerRows'  => $managerRows,
            'totalStats'   => $totalStats,
            'selectedDate' => $date,
            'generatedBy'  => $user->name,
        ]);
    }

    private function buildBulananData(Request $request): array
    {
        $user = $request->user();

        $year  = (int) $request->input('year',  now()->year);
        $month = (int) $request->input('month', now()->month);
        $year  = max(2020, min((int) now()->year + 1, $year));
        $month = max(1, min(12, $month));

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();
        $totalDays = $start->daysInMonth;

        // Ambil semua anggota yang visible
        $isStaff = ! $user->isSuperAdmin() && $user->level === User::LEVEL_STAFF;
        if ($isStaff) {
            $members = collect([$user]);
        } else {
            $members = $this->teamMembersQuery($user)
                ->orderBy('division')->orderBy('level')->orderBy('name')
                ->get();
            // Staff juga lihat laporan sendiri di atas
            $members = collect([$user])->merge($members);
        }

        // Ambil semua laporan bulan ini untuk member-member tersebut
        $memberIds = $members->pluck('id');
        $reports = DailyReport::query()
            ->whereIn('user_id', $memberIds)
            ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
            ->with('user')
            ->orderBy('report_date')
            ->get()
            ->groupBy('user_id');

        // Susun data per user
        $rows = $members->map(function ($member) use ($reports, $totalDays) {
            $userReports = $reports->get($member->id, collect());
            return (object) [
                'user'        => $member,
                'reports'     => $userReports,
                'submitted'   => $userReports->count(),
                'total_days'  => $totalDays,
                'overtime'    => $userReports->where('overtime_status', true)->count(),
                'need_help'   => $userReports->where('need_leader_help', true)->count(),
                'late'        => $userReports->where('is_late', true)->count(),
                'missing'     => $totalDays - $userReports->count(),
            ];
        });

        $byDivision = $rows->groupBy(fn ($r) => $r->user->division ?? 'Tanpa Divisi');

        $totalStats = [
            'members'   => $rows->count(),
            'submitted' => $rows->sum('submitted'),
            'overtime'  => $rows->sum('overtime'),
            'need_help' => $rows->sum('need_help'),
            'late'      => $rows->sum('late'),
        ];

        $holidays = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($h) => $h->date->toDateString());

        return [
            'byDivision'  => $byDivision,
            'totalStats'  => $totalStats,
            'year'        => $year,
            'month'       => $month,
            'monthLabel'  => $start->translatedFormat('F Y'),
            'totalDays'   => $totalDays,
            'startDate'   => $start,
            'endDate'     => $end,
            'holidays'    => $holidays,
            'generatedBy' => $user->name,
        ];
    }

    public function laporanBulanan(Request $request): View
    {
        return view('daily-reports.laporan-bulanan', $this->buildBulananData($request));
    }

    public function laporanBulananCetak(Request $request): View
    {
        return view('daily-reports.laporan-bulanan-cetak', $this->buildBulananData($request));
    }

    public function laporanBulananDownload(Request $request)
    {
        $authUser = $request->user();
        $userId   = (int) $request->input('user_id');
        $year     = (int) $request->input('year',  now()->year);
        $month    = (int) $request->input('month', now()->month);

        $targetUser = User::findOrFail($userId);

        $allowed = $authUser->isSuperAdmin()
            || $authUser->id === $userId
            || DailyReport::query()->visibleTo($authUser)->where('user_id', $userId)->exists();
        abort_unless($allowed, 403);

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $reports = DailyReport::query()
            ->where('user_id', $userId)
            ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('report_date')
            ->get();

        $monthLabel = $start->translatedFormat('F_Y');
        $safeName   = preg_replace('/[^A-Za-z0-9_\-]/', '_', $targetUser->name);
        $filename   = "laporan_{$safeName}_{$monthLabel}.xlsx";

        $xlsx = new \App\Support\SimpleXlsx();

        $xlsx->addRow(['Nama',    $targetUser->name], true);
        $xlsx->addRow(['Level',   $targetUser->level_name], true);
        $xlsx->addRow(['Divisi',  $targetUser->division ?? '-'], true);
        $xlsx->addRow(['Periode', $start->translatedFormat('F Y')], true);
        $xlsx->addEmpty();
        $xlsx->addRow([
            'Tanggal',
            'Pekerjaan Diselesaikan',
            'Belum Selesai',
            'Hambatan',
            'Rencana Besok',
            'Jam Selesai',
            'Lembur',
            'Jam Lembur Mulai',
            'Jam Lembur Selesai',
            'Butuh Bantuan',
            'Keterangan Bantuan',
            'Sanksi Terlambat',
            'Catatan Tambahan',
        ], true);

        foreach ($reports as $r) {
            $xlsx->addRow([
                $r->report_date->translatedFormat('d F Y'),
                $r->completed_work,
                $r->unfinished_work ?? '',
                $r->obstacles ?? '',
                $r->tomorrow_plan,
                substr($r->work_finished_at, 0, 5),
                $r->overtime_status ? 'Ya' : 'Tidak',
                $r->overtime_start ? substr($r->overtime_start, 0, 5) : '',
                $r->overtime_end   ? substr($r->overtime_end, 0, 5)   : '',
                $r->need_leader_help ? 'Ya' : 'Tidak',
                $r->leader_help_description ?? '',
                $r->is_late ? 'Ya' : 'Tidak',
                $r->additional_notes ?? '',
            ]);
        }

        return $xlsx->download($filename);
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

        $user = $request->user();
        if (in_array($user->level, [User::LEVEL_LEADER, User::LEVEL_STAFF], true) && now()->hour >= 21) {
            $data['is_late'] = true;
        }

        $report = DailyReport::create($data);

        $message = 'Laporan harian berhasil disimpan.';
        if ($report->is_late) {
            $message .= ' Laporan dikirim setelah pukul 21:00 — Anda mendapat sanksi keterlambatan.';
        }

        return redirect()->route('daily-reports.show', $report)
            ->with($report->is_late ? 'warning' : 'success', $message);
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
