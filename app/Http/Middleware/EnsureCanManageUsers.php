<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanManageUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->canManageUsers()) {
            abort(403, 'Anda tidak memiliki akses ke modul manajemen user.');
        }

        return $next($request);
    }
}
