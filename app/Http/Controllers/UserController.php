<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Company;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        // Admin global melihat user perusahaan aktif + semua admin global (company_id null).
        // Admin per-perusahaan hanya melihat user perusahaannya (sudah via global scope,
        // tapi kita samakan jalurnya agar eksplisit).
        $activeId = CompanyContext::id();
        $query = User::withoutGlobalScopes();

        if ($request->user()->isGlobalAdmin()) {
            $query->where(function ($q) use ($activeId) {
                $q->where('company_id', $activeId)->orWhereNull('company_id');
            });
        } else {
            $query->where('company_id', $activeId);
        }

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('division', 'like', "%{$q}%");
            });
        }
        if ($request->filled('level')) {
            $query->where('level', $request->integer('level'));
        }

        $users = $query->orderBy('level')->orderBy('name')->paginate(15)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.create', [
            'user' => new User(['level' => User::LEVEL_STAFF, 'is_active' => true]),
            'companies' => $this->companyOptions(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['company_id'] = $this->resolveCompanyId($request);

        User::create($data);

        return redirect()->route('users.index')->with('success', 'User berhasil dibuat.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->assertCanManage($request, $user);

        return view('users.edit', [
            'user' => $user,
            'companies' => $this->companyOptions(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->assertCanManage($request, $user);

        $data = $request->validated();
        $data['company_id'] = $this->resolveCompanyId($request, $user);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Pastikan actor boleh mengelola user target. Karena route-binding User tidak
     * lagi ber-scope perusahaan, otorisasi ditegakkan di sini.
     * - Admin global       : user perusahaan aktif ATAU admin global lain (null).
     * - Admin per-perusahaan : hanya user di perusahaannya.
     */
    private function assertCanManage(Request $request, User $user): void
    {
        $activeId = CompanyContext::id();
        $sameCompany = (int) $user->company_id === (int) $activeId;

        $allowed = $request->user()->isGlobalAdmin()
            ? ($sameCompany || is_null($user->company_id))
            : $sameCompany;

        abort_unless($allowed, 404);
    }

    /** Daftar perusahaan untuk dropdown (hanya relevan bagi super-admin global). */
    private function companyOptions()
    {
        return Company::where('is_active', true)->orderBy('id')->get();
    }

    /**
     * Tentukan company_id user yang disimpan.
     * - Super-admin global : ikut pilihan dari form — boleh salah satu perusahaan
     *   atau null ("Global", lintas perusahaan).
     * - Admin per-perusahaan : dikunci ke perusahaan aktifnya (input diabaikan).
     */
    private function resolveCompanyId(Request $request, ?User $user = null): ?int
    {
        if ($request->user()->isGlobalAdmin()) {
            // company_id sudah dinormalkan di UserRequest (kosong → null = Global).
            $input = $request->input('company_id');
            return is_null($input) ? null : (int) $input;
        }

        return (int) ($request->user()->company_id ?: CompanyContext::id());
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->assertCanManage($request, $user);

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'Anda tidak bisa menghapus akun sendiri.']);
        }

        $user->update(['is_active' => false]);

        return redirect()->route('users.index')->with('success', 'User berhasil dinonaktifkan.');
    }
}
