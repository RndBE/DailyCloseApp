<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Sinkron cuti/sakit dari HRIS (backend_absensi) ke Daily.
 *
 * HRIS memanggil `sync` saat pengajuan berubah menjadi ACC, dan `revoke`
 * saat pengajuan yang sudah ACC dibatalkan/ditolak. Baris hasil sinkron
 * ditandai source=absensi + external_id sehingga:
 *   - approve ulang memperbarui baris yang sama (idempotent),
 *   - karyawan tidak bisa menghapusnya dari halaman Cuti.
 *
 * Dokumentasi kontrak: docs/api-internal-sinkron-cuti.md
 */
class InternalLeaveSyncController extends Controller
{
    public function sync(Request $request): JsonResponse
    {
        CompanyContext::clear();

        if ($denied = $this->rejectInvalidSecret($request)) {
            return $denied;
        }

        $data = $request->validate([
            'external_id' => ['required', 'string', 'max:64'],
            'email' => ['required', 'email'],
            'type' => ['required', Rule::in(array_keys(Leave::TYPES))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user = User::withoutGlobalScopes()
            ->where('email', strtolower(trim($data['email'])))
            ->where('is_active', true)
            ->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Daily dengan email tersebut belum terdaftar atau sudah nonaktif.',
            ], 404);
        }

        $start = Carbon::parse($data['start_date'])->toDateString();
        $end = Carbon::parse($data['end_date'])->toDateString();

        $leave = Leave::withoutGlobalScopes()->firstOrNew([
            'source' => Leave::SOURCE_ABSENSI,
            'external_id' => $data['external_id'],
        ]);

        $created = ! $leave->exists;

        $leave->fill([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'type' => $data['type'],
            'start_date' => $start,
            'end_date' => $end,
            'reason' => $data['reason'] ?? null,
        ])->save();

        // Catatan manual yang beririsan dipisah dua.
        //
        // Yang SELURUH rentangnya tercakup baris HRIS ini dihapus: isinya duplikat dan
        // barisnya membuat halaman Cuti tampak dobel, sementara hari-harinya tetap
        // terjamin oleh baris HRIS yang lebih kuat (sudah di-ACC, tidak bisa dihapus
        // karyawan). Tidak ada informasi yang hilang.
        //
        // Yang menonjol KELUAR rentang HRIS tidak disentuh. Di luar rentang itu tidak
        // ada yang menggantikannya, jadi menghapusnya akan diam-diam mencabut
        // pengecualian "belum lapor" pada tanggal yang tidak diputuskan HRIS.
        $manual = Leave::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('source', Leave::SOURCE_MANUAL)
            ->overlapping($start, $end)
            ->get();

        [$covered, $kept] = $manual->partition(
            fn (Leave $row) => $row->start_date->toDateString() >= $start
                && $row->end_date->toDateString() <= $end
        );

        // Keterangan yang sudah diisi karyawan jangan ikut hilang kalau HRIS tidak
        // mengirim alasan apa pun.
        if (blank($leave->reason)) {
            $inherited = $covered->first(fn (Leave $row) => filled($row->reason));

            if ($inherited) {
                $leave->reason = $inherited->reason;
                $leave->save();
            }
        }

        if ($covered->isNotEmpty()) {
            Leave::withoutGlobalScopes()->whereIn('id', $covered->pluck('id'))->delete();
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'message' => $created
                ? 'Cuti/sakit berhasil dicatat di Daily.'
                : 'Cuti/sakit yang sudah ada berhasil diperbarui.',
            'data' => [
                'id' => $leave->id,
                'external_id' => $leave->external_id,
                'email' => $user->email,
                'type' => $leave->type,
                'start_date' => $start,
                'end_date' => $end,
                'days_count' => $leave->days_count,
            ],
            // Dihapus karena duplikat penuh.
            'absorbed_manual_ids' => $covered->pluck('id')->values()->all(),
            // Beririsan tapi sengaja dibiarkan karena keluar dari rentang HRIS.
            'overlapping_manual_ids' => $kept->pluck('id')->values()->all(),
        ], $created ? 201 : 200);
    }

    public function revoke(Request $request, string $externalId): JsonResponse
    {
        CompanyContext::clear();

        if ($denied = $this->rejectInvalidSecret($request)) {
            return $denied;
        }

        $deleted = Leave::withoutGlobalScopes()
            ->where('source', Leave::SOURCE_ABSENSI)
            ->where('external_id', $externalId)
            ->delete();

        // Sengaja 200 walau tidak ada yang terhapus: pembatalan yang dikirim dua
        // kali harus berakhir sama, bukan error.
        return response()->json([
            'success' => true,
            'deleted' => $deleted > 0,
            'message' => $deleted > 0
                ? 'Catatan cuti/sakit di Daily berhasil dihapus.'
                : 'Tidak ada catatan cuti/sakit dengan external_id tersebut.',
        ]);
    }

    private function rejectInvalidSecret(Request $request): ?JsonResponse
    {
        $secret = (string) config('services.absensi_bridge.secret');

        if ($secret === '' || ! hash_equals($secret, (string) $request->header('X-Internal-Secret'))) {
            return response()->json([
                'success' => false,
                'message' => 'Request internal tidak valid.',
            ], 403);
        }

        return null;
    }
}
