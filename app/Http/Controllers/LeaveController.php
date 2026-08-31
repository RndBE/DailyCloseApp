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
            ->first();

        if ($overlap) {
            return back()
                ->withInput()
                ->with('error', $overlap->isSynced()
                    ? 'Rentang tanggal beririsan dengan pengajuan yang sudah di-ACC di HRIS dan sudah tercatat otomatis di sini.'
                    : 'Rentang tanggal beririsan dengan catatan ketidakhadiran yang sudah ada.');
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

        // Catatan dari HRIS hanya boleh dibatalkan di HRIS, supaya kedua sistem
        // tidak berbeda isi.
        if ($leave->isSynced()) {
            return redirect()->route('leaves.index')
                ->with('error', 'Catatan ini berasal dari pengajuan yang sudah di-ACC di HRIS. Pembatalannya dilakukan di HRIS, nanti otomatis hilang dari sini.');
        }

        $leave->delete();

        return redirect()->route('leaves.index')
            ->with('success', 'Catatan ketidakhadiran berhasil dihapus.');
    }
}
