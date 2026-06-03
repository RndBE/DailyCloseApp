<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Pindah perusahaan aktif (hanya untuk super-admin global).
     */
    public function switch(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isGlobalAdmin(), 403);

        $validated = $request->validate([
            'company_id' => ['required', 'integer'],
        ]);

        $company = Company::where('is_active', true)->find($validated['company_id']);
        abort_unless($company !== null, 404);

        $request->session()->put('active_company_id', $company->id);

        return redirect()->back()->with('success', 'Perusahaan aktif: ' . $company->name);
    }
}
