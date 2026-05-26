<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query();

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
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        User::create($data);

        return redirect()->route('users.index')->with('success', 'User berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'Anda tidak bisa menghapus akun sendiri.']);
        }

        $user->update(['is_active' => false]);

        return redirect()->route('users.index')->with('success', 'User berhasil dinonaktifkan.');
    }
}
