<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Support\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menetapkan "perusahaan aktif" untuk request yang sudah terautentikasi.
 *
 * - User biasa            : terkunci ke company_id miliknya.
 * - Super-admin global     : memakai pilihan dari session (switcher),
 *   default ke perusahaan aktif pertama.
 *
 * Harus berjalan SETELAH middleware 'auth'.
 */
class SetActiveCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            if ($user->isGlobalAdmin()) {
                CompanyContext::set($this->resolveForGlobalAdmin($request));
            } else {
                // Terkunci ke perusahaannya. Jika (anomali) null, kunci ke perusahaan
                // yang tidak ada (-1) agar tidak melihat data perusahaan mana pun.
                CompanyContext::set($user->company_id ?? -1);
            }
        }

        return $next($request);
    }

    private function resolveForGlobalAdmin(Request $request): int
    {
        $validIds = Company::where('is_active', true)->orderBy('id')->pluck('id');

        $selected = (int) $request->session()->get('active_company_id');

        if (! $validIds->contains($selected)) {
            $selected = (int) $validIds->first();
            $request->session()->put('active_company_id', $selected);
        }

        return $selected;
    }
}
