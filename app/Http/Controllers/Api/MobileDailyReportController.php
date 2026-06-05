<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyReport;
use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobileDailyReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reports = DailyReport::query()
            ->where('user_id', $request->user()->id)
            ->latest('report_date')
            ->latest('id')
            ->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => collect($reports->items())->map(fn (DailyReport $report) => $this->formatReport($report)),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    public function today(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());

        try {
            $date = Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            $date = now()->toDateString();
        }

        $report = DailyReport::query()
            ->where('user_id', $request->user()->id)
            ->whereDate('report_date', $date)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $report ? $this->formatReport($report) : null,
        ]);
    }

    public function team(Request $request): JsonResponse
    {
        $user = $request->user();
        $date = $this->parseReportDate($request->input('date'));

        $reports = DailyReport::query()
            ->with('user:id,name,email,division,position,level')
            ->whereDate('report_date', $date)
            ->whereHas('user', fn ($query) => $this->applyVisibleLowerLevelFilter($query, $user))
            ->latest('report_date')
            ->latest('id')
            ->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => collect($reports->items())->map(fn (DailyReport $report) => $this->formatReport($report)),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    public function teamAccess(Request $request): JsonResponse
    {
        $user = $request->user();
        $canViewTeam = User::query()
            ->whereKeyNot($user->id)
            ->where(fn ($query) => $this->applyVisibleLowerLevelFilter($query, $user))
            ->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'can_view_team' => $canViewTeam,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $this->validatedData($request);
        $data['user_id'] = $user->id;
        $data['company_id'] = $user->company_id;

        $this->ensureDateIsAvailable($user, $data['report_date']);
        $this->normalizeOptionalFields($data);
        $this->applyLateFlag($data, $user);

        $report = DailyReport::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Laporan harian berhasil disimpan.',
            'data' => $this->formatReport($report),
        ], 201);
    }

    public function update(Request $request, DailyReport $dailyReport): JsonResponse
    {
        abort_unless($dailyReport->user_id === $request->user()->id, 403, 'Anda hanya bisa mengedit laporan sendiri.');

        $data = $this->validatedData($request, $dailyReport);
        $this->ensureDateIsAvailable($request->user(), $data['report_date'], $dailyReport);
        $this->normalizeOptionalFields($data);

        $dailyReport->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Laporan harian berhasil diperbarui.',
            'data' => $this->formatReport($dailyReport->fresh()),
        ]);
    }

    private function validatedData(Request $request, ?DailyReport $report = null): array
    {
        $user = $request->user();

        return $request->validate([
            'report_date' => [
                'required',
                'date',
                Rule::unique('daily_reports', 'report_date')
                    ->where(fn ($q) => $q->where('user_id', $user->id))
                    ->ignore($report?->id),
            ],
            'overtime_status' => ['required', 'boolean'],
            'overtime_start' => ['nullable', 'required_if:overtime_status,1', 'date_format:H:i'],
            'overtime_end' => ['nullable', 'required_if:overtime_status,1', 'date_format:H:i', 'after:overtime_start'],
            'completed_work' => ['required', 'string', 'max:5000'],
            'unfinished_work' => ['nullable', 'string', 'max:5000'],
            'obstacles' => ['nullable', 'string', 'max:5000'],
            'need_leader_help' => ['required', 'boolean'],
            'leader_help_description' => ['nullable', 'required_if:need_leader_help,1', 'string', 'max:5000'],
            'tomorrow_plan' => ['required', 'string', 'max:5000'],
            'work_finished_at' => ['required', 'date_format:H:i'],
            'additional_notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'report_date.unique' => 'Anda sudah membuat laporan untuk tanggal ini.',
            'overtime_start.required_if' => 'Jam mulai lembur wajib diisi jika status lembur Ya.',
            'overtime_end.required_if' => 'Jam selesai lembur wajib diisi jika status lembur Ya.',
            'overtime_end.after' => 'Jam selesai lembur harus setelah jam mulai lembur.',
            'leader_help_description.required_if' => 'Penjelasan bantuan wajib diisi jika butuh bantuan leader.',
        ]);
    }

    private function normalizeOptionalFields(array &$data): void
    {
        if (! $data['overtime_status']) {
            $data['overtime_start'] = null;
            $data['overtime_end'] = null;
        }

        if (! $data['need_leader_help']) {
            $data['leader_help_description'] = null;
        }
    }

    private function ensureDateIsAvailable(User $user, string $reportDate, ?DailyReport $ignore = null): void
    {
        $exists = DailyReport::query()
            ->where('user_id', $user->id)
            ->whereDate('report_date', Carbon::parse($reportDate)->toDateString())
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'report_date' => ['Anda sudah membuat laporan untuk tanggal ini.'],
            ]);
        }
    }

    private function applyLateFlag(array &$data, User $user): void
    {
        $data['is_late'] = false;
        $reportDate = Carbon::parse($data['report_date'])->toDateString();
        $onLeave = Leave::where('user_id', $user->id)
            ->overlapping($reportDate, $reportDate)
            ->exists();

        if (! $onLeave
            && in_array($user->level, [User::LEVEL_LEADER, User::LEVEL_STAFF], true)
            && now()->hour >= 21
            && ! $this->overtimeCoversLateCutoff($data)) {
            $data['is_late'] = true;
        }
    }

    private function overtimeCoversLateCutoff(array $data): bool
    {
        if (! filter_var($data['overtime_status'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $overtimeEnd = $data['overtime_end'] ?? null;
        if (! $overtimeEnd) {
            return false;
        }

        try {
            return Carbon::createFromFormat('H:i', substr((string) $overtimeEnd, 0, 5))
                ->greaterThanOrEqualTo(Carbon::createFromTime(21, 0));
        } catch (\Throwable) {
            return false;
        }
    }

    private function formatReport(DailyReport $report): array
    {
        $report->loadMissing('user:id,name,email,division,position,level');

        return [
            'id' => $report->id,
            'report_date' => $report->report_date?->toDateString(),
            'user' => $report->user ? [
                'id' => $report->user->id,
                'name' => $report->user->name,
                'email' => $report->user->email,
                'division' => $report->user->division,
                'position' => $report->user->position,
                'level' => $report->user->level,
            ] : null,
            'overtime_status' => $report->overtime_status,
            'overtime_start' => $this->formatTime($report->overtime_start),
            'overtime_end' => $this->formatTime($report->overtime_end),
            'completed_work' => $report->completed_work,
            'unfinished_work' => $report->unfinished_work,
            'obstacles' => $report->obstacles,
            'need_leader_help' => $report->need_leader_help,
            'leader_help_description' => $report->leader_help_description,
            'tomorrow_plan' => $report->tomorrow_plan,
            'work_finished_at' => $this->formatTime($report->work_finished_at),
            'additional_notes' => $report->additional_notes,
            'is_late' => $report->is_late,
            'created_at' => $report->created_at?->toISOString(),
            'updated_at' => $report->updated_at?->toISOString(),
        ];
    }

    private function formatTime(?string $time): ?string
    {
        return $time ? substr($time, 0, 5) : null;
    }

    private function parseReportDate(mixed $date): string
    {
        try {
            return Carbon::parse($date ?: now())->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }

    private function applyVisibleLowerLevelFilter($query, User $user): void
    {
        $divisions = $user->visibleDivisions();

        $query
            ->where('is_active', true)
            ->where('level', '>', $user->level);

        if (is_array($divisions)) {
            if (empty($divisions)) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->whereIn('division', $divisions);
        }
    }
}
