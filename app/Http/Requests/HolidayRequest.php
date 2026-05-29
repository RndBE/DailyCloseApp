<?php

namespace App\Http\Requests;

use App\Models\Holiday;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        $holidayId = $this->route('holiday')?->id;

        return [
            'date' => ['required', 'date', Rule::unique('holidays', 'date')->ignore($holidayId)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(Holiday::TYPES))],
        ];
    }

    public function messages(): array
    {
        return [
            'date.unique' => 'Tanggal tersebut sudah terdaftar sebagai hari libur.',
        ];
    }
}
