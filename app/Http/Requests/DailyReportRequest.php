<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DailyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'overtime_status' => $this->boolean('overtime_status'),
            'need_leader_help' => $this->boolean('need_leader_help'),
        ]);
    }

    public function rules(): array
    {
        $reportId = $this->route('daily_report')?->id;

        return [
            'report_date' => [
                'required',
                'date',
                Rule::unique('daily_reports', 'report_date')
                    ->where(fn ($q) => $q->where('user_id', $this->user()->id))
                    ->ignore($reportId),
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
        ];
    }

    public function messages(): array
    {
        return [
            'report_date.unique' => 'Anda sudah membuat laporan untuk tanggal ini.',
            'overtime_start.required_if' => 'Jam mulai lembur wajib diisi jika status lembur Ya.',
            'overtime_end.required_if' => 'Jam selesai lembur wajib diisi jika status lembur Ya.',
            'overtime_end.after' => 'Jam selesai lembur harus setelah jam mulai lembur.',
            'leader_help_description.required_if' => 'Penjelasan bantuan wajib diisi jika butuh bantuan leader.',
        ];
    }
}
