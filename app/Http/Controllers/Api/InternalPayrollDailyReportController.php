<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class InternalPayrollDailyReportController extends Controller
{
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

        $start = Carbon::parse($data['start'])->toDateString();
        $end = Carbon::parse($data['end'])->toDateString();
        $emails = collect($data['emails'])
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values();

        $users = User::withoutGlobalScopes()
            ->whereIn('email', $emails)
            ->withCount([
                'dailyReports as late_reports_count' => fn ($query) => $query
                    ->withoutGlobalScopes()
                    ->whereBetween('report_date', [$start, $end])
                    ->where('is_late', true),
            ])
            ->get(['id', 'email']);

        $counts = $users->mapWithKeys(
            fn (User $user) => [strtolower($user->email) => (int) $user->late_reports_count]
        );

        return response()->json([
            'success' => true,
            'data' => $emails
                ->map(fn ($email) => [
                    'email' => $email,
                    'late_days' => (int) ($counts[$email] ?? 0),
                ])
                ->values()
                ->all(),
        ]);
    }
}
