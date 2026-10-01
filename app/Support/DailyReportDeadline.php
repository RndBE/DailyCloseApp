<?php

namespace App\Support;

use App\Models\Leave;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Aturan sanksi laporan harian (berlaku untuk laporan mulai 1 September 2026).
 *
 * - Leader & Staff: laporan tanggal D telat bila dikirim mulai D pukul 21:00 WIB, termasuk
 *   laporan susulan di hari-hari berikutnya. Lembur yang selesai pukul 21:00 atau lebih
 *   memberi kelonggaran sampai akhir hari D.
 * - Manager: laporan tanggal D paling lambat D+1 pukul 10:00 WIB.
 *
 * Hari kerja tanpa laporan dihitung tidak diisi setelah batasnya lewat. Telat dan tidak
 * diisi sama-sama kena sanksi; security dan Owner tidak pernah kena.
 */
class DailyReportDeadline
{
    public const TIMEZONE = 'Asia/Jakarta';

    /** Leader & Staff: mulai jam ini laporan hari itu dianggap telat. */
    public const STAFF_CUTOFF = '21:00';

    /** Manager: batas kirim laporan pada hari berikutnya. */
    public const MANAGER_DEADLINE = '10:00';

    /** Level yang kena sanksi laporan harian (security selalu dikecualikan). */
    public const SANCTIONED_LEVELS = [User::LEVEL_MANAGER, User::LEVEL_LEADER, User::LEVEL_STAFF];

    public static function appliesTo(User $user): bool
    {
        return ! $user->isSecurity() && in_array($user->level, self::SANCTIONED_LEVELS, true);
    }

    /** Teks batas untuk pesan ke pengguna. */
    public static function describe(User $user): string
    {
        return $user->level === User::LEVEL_MANAGER
            ? 'pukul '.self::MANAGER_DEADLINE.' WIB hari berikutnya'
            : 'pukul '.self::STAFF_CUTOFF.' WIB';
    }

    /**
     * Saat terakhir laporan tanggal $reportDate masih dianggap tepat waktu.
     * $overtimeEnd (H:i) hanya berpengaruh untuk Leader & Staff.
     */
    public static function deadlineFor(User $user, CarbonInterface|string $reportDate, ?string $overtimeEnd = null): Carbon
    {
        $day = Carbon::parse(self::dateString($reportDate), self::TIMEZONE);

        if ($user->level === User::LEVEL_MANAGER) {
            return $day->addDay()->setTimeFromTimeString(self::MANAGER_DEADLINE);
        }

        if (self::overtimePastCutoff($overtimeEnd)) {
            return $day->endOfDay();
        }

        return $day->setTimeFromTimeString(self::STAFF_CUTOFF)->subMicrosecond();
    }

    /**
     * Apakah laporan milik $user untuk $reportDate yang dikirim pada $submittedAt kena sanksi telat.
     * Hari cuti/sakit tidak kena sanksi.
     */
    public static function isLate(User $user, CarbonInterface|string $reportDate, CarbonInterface $submittedAt, ?string $overtimeEnd = null): bool
    {
        if (! self::appliesTo($user) || ! $submittedAt->greaterThan(self::deadlineFor($user, $reportDate, $overtimeEnd))) {
            return false;
        }

        $date = self::dateString($reportDate);

        return ! Leave::where('user_id', $user->id)
            ->overlapping($date, $date)
            ->exists();
    }

    /**
     * Tanggal laporan terakhir yang sudah bisa dihitung tidak diisi pada saat $now.
     * Leader & Staff: sampai kemarin. Manager: sampai tanggal yang batas D+1 10:00-nya sudah lewat.
     */
    public static function lastDueDate(User $user, ?CarbonInterface $now = null): Carbon
    {
        $now = Carbon::instance($now ?? Carbon::now())->setTimezone(self::TIMEZONE);
        $due = $now->copy()->subDay()->startOfDay();

        if ($user->level === User::LEVEL_MANAGER && ! $now->greaterThan(self::deadlineFor($user, $due))) {
            $due->subDay();
        }

        // Kembalikan sebagai tanggal di zona aplikasi supaya bisa dibandingkan langsung
        // dengan tanggal laporan (yang disimpan tanpa zona waktu).
        return Carbon::parse($due->toDateString());
    }

    private static function overtimePastCutoff(?string $overtimeEnd): bool
    {
        if (! $overtimeEnd) {
            return false;
        }

        return substr($overtimeEnd, 0, 5) >= self::STAFF_CUTOFF;
    }

    private static function dateString(CarbonInterface|string $date): string
    {
        return $date instanceof CarbonInterface ? $date->toDateString() : Carbon::parse($date)->toDateString();
    }
}
