<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class InternalPayrollDailyReportController extends Controller
{
    /**
     * Rekap hari kena sanksi laporan harian untuk kebutuhan payroll.
     *
     * Sanksi dihitung dari dua sumber:
     *  1. Laporan terkirim tetapi telat (kolom is_late).
     *  2. Hari kerja yang sama sekali tidak diisi laporan (bolong).
     *
     * Keduanya digabung ke late_days/late_dates supaya sisi payroll tidak
     * perlu berubah; rincian hari bolong tetap tersedia di missing_dates.
     */
    public function lateCounts(Request $request): JsonResponse
    {
        CompanyContext::clear();

        $secret = (string) config('services.absensi_bridge.secret');
        if ($secret === '' || ! hash_equals($secret, (string) $request->header('X-Internal-Secret'))) {
            return response()->json([
                'success' => false,
                'message' => 'Request internal tidak valid.',
            ], 403);
        }

        $data = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
            'emails' => ['required', 'array', 'min:1'],
            'emails.*' => ['required', 'email'],
        ]);

        $start = Carbon::parse($data['start'])->startOfDay();
        $end = Carbon::parse($data['end'])->startOfDay();
        // Batas atas memakai akhir hari agar kolom tanggal yang disimpan
        // dengan komponen jam (mis. SQLite) tetap ikut terambil.
        $endBound = $end->copy()->endOfDay()->toDateTimeString();
        $emails = collect($data['emails'])
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values();

        $users = User::withoutGlobalScopes()
            ->whereIn('email', $emails)
            ->with([
                'dailyReports' => fn ($query) => $query
                    ->withoutGlobalScopes()
                    ->whereBetween('report_date', [$start->toDateString(), $endBound])
                    ->orderBy('report_date')
                    ->select(['id', 'user_id', 'report_date', 'is_late']),
            ])
            ->get(['id', 'email', 'level', 'work_schedule', 'created_at']);

        // Hari bolong hanya dihitung sampai kemarin — hari berjalan belum jatuh tempo.
        $missingEnd = $end->copy()->min(Carbon::yesterday());

        $holidays = [];
        foreach (Holiday::query()->whereBetween('date', [$start->toDateString(), $endBound])->get(['date']) as $holiday) {
            $holidays[$holiday->date->toDateString()] = true;
        }

        $leaveMap = Leave::dateMapForUsers(
            $users->pluck('id'),
            $start->toDateString(),
            $end->toDateString()
        );

        $sanctions = $users->mapWithKeys(function (User $user) use ($start, $missingEnd, $holidays, $leaveMap) {
            $reported = [];
            $lateDates = [];
            foreach ($user->dailyReports as $report) {
                $dateStr = $report->report_date->toDateString();
                $reported[$dateStr] = true;
                if ($report->is_late) {
                    $lateDates[] = $dateStr;
                }
            }

            $missingDates = $this->missingWorkdays(
                $user,
                $start,
                $missingEnd,
                $reported,
                $holidays,
                $leaveMap->get($user->id, [])
            );

            $sanctionDates = array_values(array_unique(array_merge($lateDates, $missingDates)));
            sort($sanctionDates);

            return [strtolower($user->email) => [
                'sanction' => $sanctionDates,
                'missing' => $missingDates,
            ]];
        });

        return response()->json([
            'success' => true,
            'data' => $emails
                ->map(function ($email) use ($sanctions) {
                    $row = $sanctions[$email] ?? ['sanction' => [], 'missing' => []];

                    return [
                        'email' => $email,
                        'late_days' => count($row['sanction']),
                        'late_dates' => $row['sanction'],
                        'missing_days' => count($row['missing']),
                        'missing_dates' => $row['missing'],
                    ];
                })
                ->values()
                ->all(),
        ]);
    }

    /**
     * Hari kerja dalam rentang yang tidak punya laporan sama sekali.
     *
     * Akhir pekan (sesuai jadwal kerja user), libur nasional, dan hari
     * cuti/sakit tidak dihitung. Hari sebelum akun dibuat juga dilewati.
     *
     * @param  array<string, true>  $reported  tanggal laporan sebagai kunci
     * @param  array<string, true>  $holidays  tanggal libur nasional sebagai kunci
     * @param  array<string, Leave>  $leaves
     * @return list<string>
     */
    private function missingWorkdays(
        User $user,
        Carbon $start,
        Carbon $end,
        array $reported,
        array $holidays,
        array $leaves
    ): array {
        // Sama dengan aturan sanksi keterlambatan saat laporan disimpan:
        // hanya Leader & Staff non-security yang terkena.
        if ($user->isSecurity() || ! in_array($user->level, [User::LEVEL_LEADER, User::LEVEL_STAFF], true)) {
            return [];
        }

        $from = $start->copy();
        $joined = $user->created_at?->copy()->startOfDay();
        if ($joined && $joined->greaterThan($from)) {
            $from = $joined;
        }

        $weekend = $user->work_schedule === User::SCHEDULE_6DAYS ? [0] : [0, 6];

        $missing = [];
        for ($date = $from->copy(); $date->lte($end); $date->addDay()) {
            $dateStr = $date->toDateString();

            if (in_array($date->dayOfWeek, $weekend, true)) {
                continue;
            }
            if (isset($holidays[$dateStr]) || isset($leaves[$dateStr]) || isset($reported[$dateStr])) {
                continue;
            }

            $missing[] = $dateStr;
        }

        return $missing;
    }
}
