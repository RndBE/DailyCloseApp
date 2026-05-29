<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkScheduleController extends Controller
{
    private function authorize(Request $request): void
    {
        abort_unless(
            $request->user()->isSuperAdmin(),
            403,
            'Anda tidak berhak mengakses halaman ini.'
        );
    }

    public function index(Request $request): View
    {
        $this->authorize($request);

        $users = User::where('is_active', true)
            ->orderBy('level')
            ->orderBy('division')
            ->orderBy('name')
            ->get();

        return view('work-schedules.index', [
            'users' => $users,
        ]);
    }

    public function saveAll(Request $request): RedirectResponse
    {
        $this->authorize($request);

        $keys = implode(',', array_keys(User::WORK_SCHEDULES));

        $validated = $request->validate([
            'schedules'   => ['required', 'array'],
            'schedules.*' => ['required', "in:{$keys}"],
        ]);

        foreach ($validated['schedules'] as $userId => $schedule) {
            User::where('id', (int) $userId)->update(['work_schedule' => $schedule]);
        }

        return redirect()->route('work-schedule.index')
            ->with('success', 'Jadwal kerja karyawan berhasil disimpan.');
    }
}
