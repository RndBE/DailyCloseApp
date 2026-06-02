<?php

namespace App\Http\Requests;

use App\Models\Leave;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type'       => ['required', Rule::in(array_keys(Leave::TYPES))],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'reason'     => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'           => 'Jenis ketidakhadiran wajib dipilih.',
            'type.in'                 => 'Jenis ketidakhadiran tidak valid.',
            'start_date.required'     => 'Tanggal mulai wajib diisi.',
            'end_date.required'       => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ];
    }
}
