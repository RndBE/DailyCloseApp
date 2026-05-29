<?php

namespace App\Http\Controllers;

use App\Models\SecuritySchedule;
use App\Models\User;
use App\Services\SecurityScheduleGenerator;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SecurityScheduleController extends Controller
{
    private function authorize(Request $request): void
    {
        abort_unless(
            $request->user()->canManageSecuritySchedule(),
            403,
            'Anda tidak berhak mengakses halaman ini.'
        );
    }

    private function resolvePeriod(Request $request): array
    {
        $year  = (int) $request->input('year',  now()->year);
        $month = (int) $request->input('month', now()->month);
        $year  = max(2020, min((int) now()->year + 1, $year));
        $month = max(1, min(12, $month));

        return [$year, $month];
    }

    /**
     * User security aktif, terurut → slot P0,P1,P2 untuk generator.
     * Dibatasi ke divisi yang dibawahi user (Super Admin/Owner: semua).
     */
    private function securityUsers(Request $request)
    {
        $query = User::where('is_active', true)
            ->where('work_schedule', User::SCHEDULE_SECURITY)
            ->orderBy('id');

        $visible = $request->user()->visibleDivisions(); // null = semua divisi
        if ($visible !== null) {
            $query->whereIn('division', $visible);
        }

        return $query->get();
    }

    public function index(Request $request): View
    {
        $this->authorize($request);

        [$year, $month] = $this->resolvePeriod($request);

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $users = $this->securityUsers($request);

        $schedules = SecuritySchedule::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($s) => $s->user_id . '|' . $s->date->toDateString());

        // Daftar tanggal dalam bulan tersebut.
        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days[] = $d->copy();
        }

        return view('security-schedules.index', [
            'users'        => $users,
            'schedules'    => $schedules,
            'days'         => $days,
            'year'         => $year,
            'month'        => $month,
            'monthLabel'   => $start->translatedFormat('F Y'),
            'requiredStaff' => SecurityScheduleGenerator::REQUIRED_STAFF,
        ]);
    }

    public function generate(Request $request, SecurityScheduleGenerator $generator): RedirectResponse
    {
        $this->authorize($request);

        [$year, $month] = $this->resolvePeriod($request);
        $redirect = redirect()->route('security-schedule.index', ['year' => $year, 'month' => $month]);

        $users = $this->securityUsers($request);

        if ($users->count() !== SecurityScheduleGenerator::REQUIRED_STAFF) {
            return $redirect->with('error',
                'Generator membutuhkan tepat ' . SecurityScheduleGenerator::REQUIRED_STAFF
                . ' personel security aktif. Saat ini ada ' . $users->count() . '.');
        }

        $rows = $generator->generateForMonth($year, $month, $users->pluck('id')->all());

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        // Jangan timpa sel yang sudah diedit manual.
        $manualKeys = SecuritySchedule::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('is_manual', true)
            ->get()
            ->map(fn ($s) => $s->user_id . '|' . $s->date->toDateString())
            ->flip();

        $kept = 0;
        DB::transaction(function () use ($rows, $manualKeys, &$kept) {
            foreach ($rows as $row) {
                if ($manualKeys->has($row['user_id'] . '|' . $row['date'])) {
                    $kept++;
                    continue;
                }

                SecuritySchedule::updateOrCreate(
                    ['user_id' => $row['user_id'], 'date' => $row['date']],
                    [
                        'start_time' => $row['start_time'],
                        'end_time'   => $row['end_time'],
                        'is_off'     => $row['is_off'],
                        'is_manual'  => false,
                    ]
                );
            }
        });

        $msg = 'Jadwal berhasil di-generate dari pola rotasi.';
        if ($kept > 0) {
            $msg .= " {$kept} sel manual dipertahankan (tidak ditimpa).";
        }

        return $redirect->with('success', $msg);
    }

    public function saveAll(Request $request): RedirectResponse
    {
        $this->authorize($request);

        [$year, $month] = $this->resolvePeriod($request);

        $validCodes = array_keys(SecuritySchedule::SHIFT_OPTIONS);
        $validUserIds = $this->securityUsers($request)->pluck('id')->flip();

        $cells   = (array) $request->input('cells', []);
        $origs   = (array) $request->input('orig', []);
        $manuals = (array) $request->input('manual', []);

        DB::transaction(function () use ($cells, $origs, $manuals, $validCodes, $validUserIds) {
            foreach ($cells as $userId => $dates) {
                $userId = (int) $userId;
                if (! $validUserIds->has($userId) || ! is_array($dates)) {
                    continue;
                }

                foreach ($dates as $date => $code) {
                    if (! in_array($code, $validCodes, true)) {
                        continue;
                    }

                    $opt      = SecuritySchedule::SHIFT_OPTIONS[$code];
                    $origCode = $origs[$userId][$date] ?? null;
                    // Sel dianggap manual bila admin mengubahnya dari nilai awal,
                    // atau memang sudah berstatus manual sebelumnya.
                    $isManual = ($origCode !== null && $origCode !== $code)
                        || ! empty($manuals[$userId][$date]);

                    SecuritySchedule::updateOrCreate(
                        ['user_id' => $userId, 'date' => $date],
                        [
                            'start_time' => $opt['start'],
                            'end_time'   => $opt['end'],
                            'is_off'     => $opt['is_off'],
                            'is_manual'  => $isManual,
                        ]
                    );
                }
            }
        });

        return redirect()
            ->route('security-schedule.index', ['year' => $year, 'month' => $month])
            ->with('success', 'Perubahan jadwal security berhasil disimpan.');
    }
}
