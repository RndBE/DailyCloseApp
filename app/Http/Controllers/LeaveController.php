<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveRequest;
use App\Models\Leave;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $leaves = Leave::where('user_id', $user->id)
            ->orderByDesc('start_date')
            ->get();

        return view('leaves.index', compact('leaves'));
    }

    public function store(LeaveRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Cegah rentang yang beririsan dengan pengajuan yang sudah ada.
        $overlap = Leave::where('user_id', $user->id)
            ->overlapping($data['start_date'], $data['end_date'])
            ->exists();

        if ($overlap) {
            return back()
                ->withInput()
                ->with('error', 'Rentang tanggal beririsan dengan pengajuan cuti/sakit yang sudah ada.');
        }

        $user->leaves()->create($data);

        return redirect()->route('leaves.index')
            ->with('success', 'Pengajuan ' . (Leave::TYPES[$data['type']] ?? $data['type']) . ' berhasil dicatat.');
    }

    public function destroy(Request $request, Leave $leave): RedirectResponse
    {
        // Hanya pemilik (atau super admin) yang boleh menghapus.
        abort_unless(
            $request->user()->isSuperAdmin() || $leave->user_id === $request->user()->id,
            403
        );

        $leave->delete();

        return redirect()->route('leaves.index')
            ->with('success', 'Catatan cuti/sakit berhasil dihapus.');
    }
}
