<?php

namespace App\Http\Controllers;

use App\Http\Requests\DailyReportRequest;
use App\Models\CommentNotification;
use App\Models\DailyReport;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\ReportComment;
use App\Models\SecuritySchedule;
use App\Models\User;
use App\Support\SimpleXlsx;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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
            $q->whereDate('report_date', $date)->withCount('comments');
        }]);

        $members = $teamQuery
            ->orderBy('level')
            ->orderBy('division')
            ->orderBy('name')
            ->get();

        $leaveByUser = Leave::whereIn('user_id', $members->pluck('id'))
            ->overlapping($date, $date)
            ->get()
            ->keyBy('user_id');

        $rows = $members->map(fn ($member) => (object) [
            'user' => $member,
            'report' => $member->dailyReports->first(),
            'leave' => $leaveByUser->get($member->id),
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
            'missing' => $rows->filter(fn ($r) => ! $r->report && ! $r->leave)->count(),
            'leave' => $rows->filter(fn ($r) => ! $r->report && $r->leave)->count(),
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
    private function managerRowsQuery(User $user, string $date): Collection
    {
        if (! $user->isSuperAdmin() && ! in_array($user->level, [User::LEVEL_OWNER, User::LEVEL_MANAGER], true)) {
            return collect();
        }

        $query = User::query()
            ->where('is_active', true)
            ->where('level', User::LEVEL_MANAGER);

        if (! $user->isSuperAdmin() && $user->level !== User::LEVEL_OWNER) {
            $divisions = $user->visibleDivisions() ?? [];
            if (empty($divisions)) {
                return collect();
            }
            $query->whereIn('division', $divisions);
        }

        $managers = $query
            ->with(['dailyReports' => fn ($q) => $q->whereDate('report_date', $date)])
            ->orderBy('name')
            ->get();

        $leaveByUser = Leave::whereIn('user_id', $managers->pluck('id'))
            ->overlapping($date, $date)
            ->get()
            ->keyBy('user_id');

        return $managers->map(fn ($m) => (object) [
            'user' => $m,
            'report' => $m->dailyReports->first(),
            'leave' => $leaveByUser->get($m->id),
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

        $leaveByUser = Leave::whereIn('user_id', $members->pluck('id')->push($user->id))
            ->overlapping($date, $date)
            ->get()
            ->keyBy('user_id');

        // Managers get a dedicated section; exclude them from per-division breakdown
        $rows = $members
            ->filter(fn ($m) => $m->level !== User::LEVEL_MANAGER)
            ->map(fn ($member) => (object) [
                'user' => $member,
                'report' => $member->dailyReports->first(),
                'leave' => $leaveByUser->get($member->id),
            ]);

        // Leader includes their own report alongside their team's reports
        if ($user->level === User::LEVEL_LEADER) {
            $selfReport = DailyReport::where('user_id', $user->id)->whereDate('report_date', $date)->first();
            $rows = collect([(object) [
                'user' => $user,
                'report' => $selfReport,
                'leave' => $leaveByUser->get($user->id),
            ]])->merge($rows);
        }

        $byDivision = $rows->groupBy(fn ($row) => $row->user->division ?? 'Tanpa Divisi');

        $managerRows = $this->managerRowsQuery($user, $date);

        $allRows = $rows->values()->merge($managerRows);
        $totalStats = [
            'total' => $allRows->count(),
            'submitted' => $allRows->filter(fn ($r) => $r->report)->count(),
            'missing' => $allRows->filter(fn ($r) => ! $r->report && ! ($r->leave ?? null))->count(),
            'leave' => $allRows->filter(fn ($r) => ! $r->report && ($r->leave ?? null))->count(),
            'overtime' => $allRows->filter(fn ($r) => $r->report?->overtime_status)->count(),
            'help' => $allRows->filter(fn ($r) => $r->report?->need_leader_help)->count(),
            'late' => $allRows->filter(fn ($r) => $r->report?->is_late)->count(),
        ];

        return view('daily-reports.rangkuman', [
            'byDivision' => $byDivision,
            'managerRows' => $managerRows,
            'totalStats' => $totalStats,
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

        $leaveByUser = Leave::whereIn('user_id', $members->pluck('id')->push($user->id))
            ->overlapping($date, $date)
            ->get()
            ->keyBy('user_id');

        $rows = $members
            ->filter(fn ($m) => $m->level !== User::LEVEL_MANAGER)
            ->map(fn ($member) => (object) [
                'user' => $member,
                'report' => $member->dailyReports->first(),
                'leave' => $leaveByUser->get($member->id),
            ]);

        if ($user->level === User::LEVEL_LEADER) {
            $selfReport = DailyReport::where('user_id', $user->id)->whereDate('report_date', $date)->first();
            $rows = collect([(object) [
                'user' => $user,
                'report' => $selfReport,
                'leave' => $leaveByUser->get($user->id),
            ]])->merge($rows);
        }

        $byDivision = $rows->groupBy(fn ($row) => $row->user->division ?? 'Tanpa Divisi');

        $managerRows = $this->managerRowsQuery($user, $date);

        $allRows = $rows->values()->merge($managerRows);
        $totalStats = [
            'total' => $allRows->count(),
            'submitted' => $allRows->filter(fn ($r) => $r->report)->count(),
            'missing' => $allRows->filter(fn ($r) => ! $r->report && ! ($r->leave ?? null))->count(),
            'leave' => $allRows->filter(fn ($r) => ! $r->report && ($r->leave ?? null))->count(),
            'overtime' => $allRows->filter(fn ($r) => $r->report?->overtime_status)->count(),
            'help' => $allRows->filter(fn ($r) => $r->report?->need_leader_help)->count(),
            'late' => $allRows->filter(fn ($r) => $r->report?->is_late)->count(),
        ];

        return view('daily-reports.rangkuman-cetak', [
            'byDivision' => $byDivision,
            'managerRows' => $managerRows,
            'totalStats' => $totalStats,
            'selectedDate' => $date,
            'generatedBy' => $user->name,
        ]);
    }

    private function buildBulananData(Request $request): array
    {
        $user = $request->user();

        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $year = max(2020, min((int) now()->year + 1, $year));
        $month = max(1, min(12, $month));

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
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

        // Jadwal security (jika ada member security) — untuk hitung hari kerja efektif & tampilan shift
        $securityMemberIds = $members
            ->filter(fn ($m) => $m->work_schedule === User::SCHEDULE_SECURITY)
            ->pluck('id');

        $securitySchedules = collect();
        if ($securityMemberIds->isNotEmpty()) {
            $securitySchedules = SecuritySchedule::query()
                ->whereIn('user_id', $securityMemberIds)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->groupBy('user_id')
                ->map(fn ($g) => $g->keyBy(fn ($s) => $s->date->toDateString()));
        }

        $holidays = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($h) => $h->date->toDateString());

        // Peta cuti/sakit per user: [user_id => ['Y-m-d' => Leave]].
        $leaveMap = Leave::dateMapForUsers($memberIds, $start->toDateString(), $end->toDateString());

        // Susun data per user
        $rows = $members->map(function ($member) use ($reports, $totalDays, $securitySchedules, $holidays, $leaveMap) {
            $userReports = $reports->get($member->id, collect());

            $isSecurity = $member->work_schedule === User::SCHEDULE_SECURITY;
            $effectiveDays = $totalDays;
            $overtimeCount = $userReports->where('overtime_status', true)->count();
            $overtimeHours = null;

            $memberLeaves = $leaveMap->get($member->id, []);

            if ($isSecurity) {
                $sched = $securitySchedules->get($member->id);
                if ($sched) {
                    // Hari kerja efektif = total hari − hari libur terjadwal.
                    $effectiveDays = max(0, $totalDays - $sched->where('is_off', true)->count());

                    // Kurangi hari cuti/sakit yang jatuh pada hari kerja terjadwal.
                    $leaveWorkdays = 0;
                    foreach ($memberLeaves as $dateStr => $lv) {
                        $s = $sched->get($dateStr);
                        if (! $s || ! $s->is_off) {
                            $leaveWorkdays++;
                        }
                    }
                    $effectiveDays = max(0, $effectiveDays - $leaveWorkdays);

                    // Lembur dihitung otomatis dari shift 12 jam (kelebihan di atas 8 jam).
                    $overtimeCount = $sched->filter(fn ($s) => $s->overtimeHours() > 0)->count();
                    $overtimeHours = $sched->sum(fn ($s) => $s->overtimeHours());
                } else {
                    $effectiveDays = $totalDays;
                    $overtimeCount = 0;
                    $overtimeHours = 0;
                }
            } else {
                // Kurangi hari cuti/sakit yang jatuh pada hari kerja (bukan akhir pekan/libur nasional).
                $holidayDays = $member->work_schedule === User::SCHEDULE_6DAYS ? [0] : [0, 6];
                $leaveWorkdays = 0;
                foreach ($memberLeaves as $dateStr => $lv) {
                    $d = Carbon::parse($dateStr);
                    $isWeekend = in_array($d->dayOfWeek, $holidayDays, true);
                    $isNational = $holidays->get($dateStr) !== null;
                    if (! $isWeekend && ! $isNational) {
                        $leaveWorkdays++;
                    }
                }
                $effectiveDays = max(0, $totalDays - $leaveWorkdays);
            }

            return (object) [
                'user' => $member,
                'reports' => $userReports,
                'submitted' => $userReports->count(),
                'total_days' => $effectiveDays,
                'overtime' => $overtimeCount,
                'overtime_hours' => $overtimeHours,
                'need_help' => $userReports->where('need_leader_help', true)->count(),
                'late' => $userReports->where('is_late', true)->count(),
                'leave' => count($memberLeaves),
                'missing' => max(0, $effectiveDays - $userReports->count()),
            ];
        });

        $byDivision = $rows->groupBy(fn ($r) => $r->user->division ?? 'Tanpa Divisi');

        $totalStats = [
            'members' => $rows->count(),
            'submitted' => $rows->sum('submitted'),
            'overtime' => $rows->sum('overtime'),
            'need_help' => $rows->sum('need_help'),
            'late' => $rows->sum('late'),
            'leave' => $rows->sum('leave'),
        ];

        return [
            'byDivision' => $byDivision,
            'totalStats' => $totalStats,
            'year' => $year,
            'month' => $month,
            'monthLabel' => $start->translatedFormat('F Y'),
            'totalDays' => $totalDays,
            'startDate' => $start,
            'endDate' => $end,
            'holidays' => $holidays,
            'securitySchedules' => $securitySchedules,
            'leaves' => $leaveMap,
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
        $userId = (int) $request->input('user_id');
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $targetUser = User::findOrFail($userId);

        $allowed = $authUser->isSuperAdmin()
            || $authUser->id === $userId
            || DailyReport::query()->visibleTo($authUser)->where('user_id', $userId)->exists();
        abort_unless($allowed, 403);

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $reports = DailyReport::query()
            ->where('user_id', $userId)
            ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('report_date')
            ->get()
            ->keyBy(fn ($r) => $r->report_date->toDateString());

        // Data pendukung agar isi unduhan sama dengan tabel di web.
        $holidays = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($h) => $h->date->toDateString());

        $schedule = $targetUser->work_schedule ?? User::SCHEDULE_5DAYS;
        $isSecurity = $schedule === User::SCHEDULE_SECURITY;
        $holidayDays = $schedule === User::SCHEDULE_6DAYS ? [0] : [0, 6];

        $secSchedule = collect();
        if ($isSecurity) {
            $secSchedule = SecuritySchedule::query()
                ->where('user_id', $userId)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->keyBy(fn ($s) => $s->date->toDateString());
        }

        // Cuti/sakit user — agar hari tersebut tidak tampil sebagai "belum kirim".
        $memberLeaves = Leave::dateMapForUsers([$userId], $start->toDateString(), $end->toDateString())
            ->get($userId, []);

        $today = Carbon::today();

        // Pisahkan teks multi-baris jadi daftar butir, seperti tampilan tabel.
        $splitCell = function (?string $text): string {
            if ($text === null || trim($text) === '') {
                return '';
            }
            $result = [];
            foreach (preg_split('/\r\n|\n|\r/', $text) as $line) {
                foreach (preg_split('/\s+-\s+/', $line) as $part) {
                    $part = trim($part, " \t-•·");
                    if ($part !== '') {
                        $result[] = $part;
                    }
                }
            }
            if (empty($result)) {
                return trim($text);
            }

            return count($result) > 1
                ? implode("\n", array_map(fn ($p) => '• '.$p, $result))
                : $result[0];
        };

        $fmtOt = fn (float $h) => rtrim(rtrim(number_format($h, 1), '0'), '.').' jam';

        $monthLabel = $start->translatedFormat('F_Y');
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $targetUser->name);
        $filename = "laporan_{$safeName}_{$monthLabel}.xlsx";

        $xlsx = new SimpleXlsx;

        $xlsx->addRow(['Nama',    $targetUser->name], true);
        $xlsx->addRow(['Level',   $targetUser->level_name], true);
        $xlsx->addRow(['Divisi',  $targetUser->division ?? '-'], true);
        $xlsx->addRow(['Periode', $start->translatedFormat('F Y')], true);
        $xlsx->addEmpty();
        $xlsx->addRow([
            'Tanggal',
            'Hari',
            'Pekerjaan Diselesaikan',
            'Belum Selesai',
            'Hambatan',
            'Jam Selesai',
            'Lembur',
            'Sanksi',
        ], ['bold' => true, 'border' => true]);

        $iter = $start->copy();
        while ($iter->lte($end)) {
            $dateStr = $iter->toDateString();
            $r = $reports->get($dateStr);
            $nationalHoliday = $holidays->get($dateStr);
            $secRow = $isSecurity ? $secSchedule->get($dateStr) : null;

            // Lembur otomatis security: kelebihan durasi shift 12 jam di atas 8 jam.
            $autoOt = ($isSecurity && $secRow && ! $secRow->is_off) ? $secRow->overtimeHours() : 0;
            $autoOtLabel = $autoOt > 0 ? $fmtOt($autoOt) : '';

            if ($isSecurity) {
                $isHoliday = $secRow ? $secRow->is_off : false;
            } else {
                $isHoliday = in_array($iter->dayOfWeek, $holidayDays, true) || $nationalHoliday !== null;
            }
            $isFuture = $iter->gt($today);

            // Kolom "Hari": nama hari + shift security + nama libur nasional.
            $dayParts = [$iter->translatedFormat('l')];
            if ($isSecurity && $secRow && ! $secRow->is_off) {
                $dayParts[] = $secRow->shift_label;
            }
            if ($nationalHoliday) {
                $dayParts[] = $nationalHoliday->name;
            }
            $dayCell = implode(' — ', $dayParts);

            $tanggal = $iter->translatedFormat('d M Y');

            $leave = $memberLeaves[$dateStr] ?? null;
            $isLeaveDay = $leave && ! $r && ! $isHoliday;

            // Baris libur/hari besar diberi latar; baris cuti/sakit latar tersendiri.
            $rowStyle = $isLeaveDay
                ? ['wrap' => true, 'border' => true, 'leave' => true]
                : ['wrap' => true, 'border' => true, 'fill' => $isHoliday];

            if ($isHoliday && ! $r) {
                $label = $nationalHoliday ? $nationalHoliday->name : 'Libur';
                $xlsx->addRow([$tanggal, $dayCell, $label, '', '', '', '', ''], $rowStyle);
            } elseif ($isLeaveDay) {
                $label = $leave->type_label.($leave->reason ? ' — '.$leave->reason : '');
                $xlsx->addRow([$tanggal, $dayCell, $label, '', '', '', '', ''], $rowStyle);
            } elseif (! $r) {
                $xlsx->addRow([
                    $tanggal,
                    $dayCell,
                    $isFuture ? '' : '—',
                    '', '', '',
                    $autoOtLabel,
                    '',
                ], $rowStyle);
            } else {
                // Kolom Lembur — security: otomatis dari shift; lainnya: rentang jam.
                if ($isSecurity) {
                    $lembur = $autoOtLabel;
                } elseif ($r->overtime_status) {
                    $lembur = $r->overtime_start
                        ? substr($r->overtime_start, 0, 5).' - '.substr($r->overtime_end, 0, 5)
                        : 'Ya';
                } else {
                    $lembur = '';
                }

                $xlsx->addRow([
                    $tanggal,
                    $dayCell,
                    $splitCell($r->completed_work),
                    $splitCell($r->unfinished_work),
                    $splitCell($r->obstacles),
                    substr((string) $r->work_finished_at, 0, 5),
                    $lembur,
                    $r->is_late ? 'Sanksi' : '',
                ], $rowStyle);
            }

            $iter->addDay();
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
            ->withCount('comments')
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
        $data['is_late'] = false;

        // Tidak ada sanksi keterlambatan bila tanggal laporan adalah hari cuti/sakit.
        $reportDate = Carbon::parse($data['report_date'])->toDateString();
        $onLeave = Leave::where('user_id', $user->id)
            ->overlapping($reportDate, $reportDate)
            ->exists();

        if (! $onLeave
            && in_array($user->level, [User::LEVEL_LEADER, User::LEVEL_STAFF], true)
            && now()->hour >= 21
            && ! $this->overtimeCoversLateCutoff($data)) {
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

        $dailyReport->load(['user', 'comments.author']);

        // Tandai notifikasi komentar pada laporan ini sebagai sudah dibaca.
        if ($dailyReport->comments->isNotEmpty()) {
            CommentNotification::where('user_id', $request->user()->id)
                ->whereNull('read_at')
                ->whereIn('report_comment_id', $dailyReport->comments->pluck('id'))
                ->update(['read_at' => now()]);
        }

        return view('daily-reports.show', ['report' => $dailyReport]);
    }

    private function overtimeCoversLateCutoff(array $data): bool
    {
        if (! filter_var($data['overtime_status'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $overtimeEnd = $data['overtime_end'] ?? null;
        if (! $overtimeEnd) {
            return false;
        }

        try {
            return Carbon::createFromFormat('H:i', substr((string) $overtimeEnd, 0, 5))
                ->greaterThanOrEqualTo(Carbon::createFromTime(21, 0));
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Komentar/masukan atasan pada laporan. Boleh ditulis oleh siapa pun yang
     * berhak melihat laporan tersebut (atasan dari pemilik laporan, atau pemilik
     * sebagai balasan).
     */
    public function storeComment(Request $request, DailyReport $dailyReport): RedirectResponse
    {
        abort_unless($dailyReport->isVisibleTo($request->user()), 403, 'Anda tidak berhak mengomentari laporan ini.');

        $data = $request->validate(
            ['body' => ['required', 'string', 'max:2000']],
            ['body.required' => 'Komentar tidak boleh kosong.']
        );

        $authorId = $request->user()->id;

        $comment = $dailyReport->comments()->create([
            'user_id' => $authorId,
            'body' => $data['body'],
        ]);

        // Kirim notifikasi ke pemilik laporan & peserta diskusi lain (selain penulis).
        $recipientIds = collect([$dailyReport->user_id])
            ->merge($dailyReport->comments()->pluck('user_id'))
            ->unique()
            ->reject(fn ($id) => (int) $id === (int) $authorId)
            ->values();

        foreach ($recipientIds as $recipientId) {
            CommentNotification::create([
                'report_comment_id' => $comment->id,
                'user_id' => $recipientId,
            ]);
        }

        return redirect()->route('daily-reports.show', $dailyReport)
            ->with('success', 'Komentar berhasil ditambahkan.');
    }

    public function destroyComment(Request $request, ReportComment $comment): RedirectResponse
    {
        abort_unless(
            $request->user()->isSuperAdmin() || $comment->user_id === $request->user()->id,
            403,
            'Anda hanya bisa menghapus komentar sendiri.'
        );

        $reportId = $comment->daily_report_id;
        $comment->delete();

        return redirect()->route('daily-reports.show', $reportId)
            ->with('success', 'Komentar dihapus.');
    }

    /** Tandai semua notifikasi komentar milik user sebagai sudah dibaca. */
    public function markNotificationsRead(Request $request): RedirectResponse
    {
        CommentNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
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
