<?php

namespace App\Support;

use App\Models\Leave;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Aturan sanksi laporan harian (berlaku untuk laporan mulai 1 September 2026).
 *
 * Laporan tanggal D wajib terkirim paling lambat D+1 pukul 10:00 WIB. Lewat dari itu
 * laporan ditandai telat; hari kerja yang sampai batas itu belum punya laporan dihitung
 * tidak diisi. Keduanya kena sanksi untuk Manager, Leader, dan Staff non-security.
 */
class DailyReportDeadline
{
    public const DEADLINE_TIME = '10:00';

    public const TIMEZONE = 'Asia/Jakarta';

    /** Level yang kena sanksi laporan harian (security selalu dikecualikan). */
    public const SANCTIONED_LEVELS = [User::LEVEL_MANAGER, User::LEVEL_LEADER, User::LEVEL_STAFF];

    public static function appliesTo(User $user): bool
    {
        return ! $user->isSecurity() && in_array($user->level, self::SANCTIONED_LEVELS, true);
    }

    /** Batas kirim laporan untuk tanggal laporan $reportDate. */
    public static function deadlineFor(CarbonInterface|string $reportDate): Carbon
    {
        [$hour, $minute] = array_map('intval', explode(':', self::DEADLINE_TIME));
        $date = $reportDate instanceof CarbonInterface ? $reportDate->toDateString() : Carbon::parse($reportDate)->toDateString();

        return Carbon::parse($date, self::TIMEZONE)->addDay()->setTime($hour, $minute);
    }

    public static function isPastDeadline(CarbonInterface|string $reportDate, CarbonInterface $submittedAt): bool
    {
        return $submittedAt->greaterThan(self::deadlineFor($reportDate));
    }

    /** Tanggal laporan terakhir yang batas kirimnya sudah lewat pada saat $now. */
    public static function lastDueDate(?CarbonInterface $now = null): Carbon
    {
        $now = Carbon::instance($now ?? Carbon::now())->setTimezone(self::TIMEZONE);
        $candidate = $now->copy()->subDay()->startOfDay();
        $due = self::isPastDeadline($candidate, $now) ? $candidate : $candidate->subDay();

        // Kembalikan sebagai tanggal di zona aplikasi supaya bisa dibandingkan langsung
        // dengan tanggal laporan (yang disimpan tanpa zona waktu).
        return Carbon::parse($due->toDateString());
    }

    /**
     * Apakah laporan milik $user untuk $reportDate yang dikirim pada $submittedAt kena sanksi telat.
     * Hari cuti/sakit tidak kena sanksi.
     */
    public static function isLate(User $user, CarbonInterface|string $reportDate, CarbonInterface $submittedAt): bool
    {
        if (! self::appliesTo($user) || ! self::isPastDeadline($reportDate, $submittedAt)) {
            return false;
        }

        $date = $reportDate instanceof CarbonInterface ? $reportDate->toDateString() : Carbon::parse($reportDate)->toDateString();

        return ! Leave::where('user_id', $user->id)
            ->overlapping($date, $date)
            ->exists();
    }
}
