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

        // Catatan manual yang beririsan tidak dihapus — data karyawan bukan milik
        // sinkron ini. Cukup dilaporkan supaya HRIS/HRD bisa merapikan bila perlu.
        $overlappingManual = Leave::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('source', Leave::SOURCE_MANUAL)
            ->overlapping($start, $end)
            ->pluck('id')
            ->all();

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
            'overlapping_manual_ids' => $overlappingManual,
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
