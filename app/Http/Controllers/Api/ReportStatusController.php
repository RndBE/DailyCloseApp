<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyReport;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class ReportStatusController extends Controller
{
    /** Divisi yang dipantau (untuk saat ini hanya Software). */
    private const DIVISION = 'Software';

    /**
     * Daftar karyawan divisi Software yang BELUM mengirim laporan harian
     * pada hari ketika API ini diakses.
     *
     * Pengecualian ("tidak wajib lapor" → tidak dianggap belum lapor):
     * - Sedang cuti / sakit pada hari ini.
     * - Hari ini bukan hari kerja sesuai jadwal user (5 hari: Sabtu+Minggu libur,
     *   6 hari: Minggu libur).
     * - Hari ini libur nasional / cuti bersama (tabel holidays).
     *
     * Catatan: query sengaja tanpa company scope (endpoint publik tanpa login).
     * Saat ini divisi Software hanya ada di satu perusahaan.
     */
    public function softwareNotSubmitted(): JsonResponse
    {
        $today   = Carbon::today();         // zona waktu app: Asia/Jakarta
        $dateStr = $today->toDateString();
        $dow     = $today->dayOfWeek;       // 0=Minggu .. 6=Sabtu

        // 1) Libur nasional / cuti bersama → tidak ada kewajiban lapor.
        $holiday = Holiday::whereDate('date', $dateStr)->first();
        if ($holiday) {
            return response()->json([
                'date'       => $dateStr,
                'keterangan' => 'Hari libur (' . $holiday->name . ') — tidak ada kewajiban lapor.',
                'data'       => [],
            ]);
        }

        // 2) Kandidat: user aktif divisi Software.
        $users = User::withoutGlobalScopes()
            ->where('is_active', true)
            ->where('division', self::DIVISION)
            ->orderBy('name')
            ->get();

        // 3) Yang sudah lapor hari ini & yang sedang cuti/sakit hari ini.
        $submittedIds = DailyReport::withoutGlobalScopes()
            ->whereDate('report_date', $dateStr)
            ->pluck('user_id')
            ->flip();

        $onLeaveIds = Leave::withoutGlobalScopes()
            ->overlapping($dateStr, $dateStr)
            ->pluck('user_id')
            ->flip();

        $data = [];

        foreach ($users as $u) {
            // Hari libur sesuai jadwal kerja user.
            $offDays = $u->work_schedule === User::SCHEDULE_6DAYS ? [0] : [0, 6];
            if (in_array($dow, $offDays, true)) {
                continue; // bukan hari kerja user
            }
            if ($onLeaveIds->has($u->id)) {
                continue; // cuti / sakit → tidak wajib lapor
            }

            $data[] = [
                'nama'         => $u->name,
                'status_kirim' => $submittedIds->has($u->id) ? 'sudah' : 'belum',
            ];
        }

        return response()->json([
            'date' => $dateStr,
            'data' => $data,
        ]);
    }
}
