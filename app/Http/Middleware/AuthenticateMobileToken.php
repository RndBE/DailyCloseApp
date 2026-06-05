<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileToken
{
    public function handle(Request $request, Closure $next): Response
    {
        CompanyContext::clear();

        $token = $request->bearerToken();
        if (! $token) {
            return $this->unauthorized();
        }

        $user = User::withoutGlobalScopes()
            ->where('api_token_hash', hash('sha256', $token))
            ->first();

        if (! $user || ! $user->is_active) {
            return $this->unauthorized();
        }

        CompanyContext::set($user->company_id);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function unauthorized(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Token mobile tidak valid atau sudah kedaluwarsa.',
        ], 401);
    }
}
