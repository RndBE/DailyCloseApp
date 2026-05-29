<?php

namespace App\Http\Controllers;

use App\Http\Requests\HolidayRequest;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HolidayController extends Controller
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

        $year = (int) $request->input('year', now()->year);
        $year = max(2020, min((int) now()->year + 2, $year));

        $holidays = Holiday::whereYear('date', $year)
            ->orderBy('date')
            ->get();

        return view('holidays.index', compact('holidays', 'year'));
    }

    public function store(HolidayRequest $request): RedirectResponse
    {
        Holiday::create($request->validated());

        return redirect()->route('holidays.index', ['year' => date('Y', strtotime($request->date))])
            ->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function update(HolidayRequest $request, Holiday $holiday): RedirectResponse
    {
        $holiday->update($request->validated());

        return redirect()->route('holidays.index', ['year' => $holiday->date->year])
            ->with('success', 'Hari libur berhasil diperbarui.');
    }

    public function destroy(Request $request, Holiday $holiday): RedirectResponse
    {
        $this->authorize($request);

        $year = $holiday->date->year;
        $holiday->delete();

        return redirect()->route('holidays.index', ['year' => $year])
            ->with('success', 'Hari libur berhasil dihapus.');
    }
}
