<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InternalMobileTokenController extends Controller
{
    public function issue(Request $request): JsonResponse
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
            'email' => ['required', 'email'],
        ]);

        $user = User::withoutGlobalScopes()
            ->where('email', $data['email'])
            ->where('is_active', true)
            ->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Daily dengan email tersebut belum terdaftar.',
            ], 404);
        }

        $token = Str::random(80);
        $user->forceFill([
            'api_token_hash' => hash('sha256', $token),
        ])->save();

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'division' => $user->division,
                'position' => $user->position,
                'level' => $user->level,
                'level_name' => $user->level_name,
                'company_id' => $user->company_id,
            ],
        ]);
    }
}
