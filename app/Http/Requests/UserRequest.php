<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageUsers() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $managed = $this->input('managed_divisions');
        if (is_array($managed)) {
            $managed = array_values(array_filter(array_map('trim', $managed), fn ($d) => $d !== ''));
        } else {
            $managed = null;
        }

        $supportsManaged = in_array(
            $this->integer('level'),
            [User::LEVEL_MANAGER, User::LEVEL_LEADER],
            true
        );

        // "Global" dikirim sebagai string kosong → normalkan jadi null.
        $companyId = $this->input('company_id');
        $companyId = ($companyId === '' || $companyId === null) ? null : (int) $companyId;

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_super_admin' => $this->boolean('is_super_admin'),
            'managed_divisions' => $supportsManaged ? $managed : null,
            'company_id' => $companyId,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // "Global" (company_id null) hanya boleh untuk Super Admin, agar tidak
            // tercipta user lintas perusahaan yang justru terkunci dari semua data.
            if (is_null($this->input('company_id')) && ! $this->boolean('is_super_admin')) {
                $validator->errors()->add(
                    'company_id',
                    'Perusahaan "Global" hanya untuk Super Admin. Centang Peran Super Admin atau pilih salah satu perusahaan.'
                );
            }
        });
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');
        $passwordRule = $isUpdate
            ? ['nullable', 'confirmed', Password::min(6)]
            : ['required', 'confirmed', Password::min(6)];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            // Hanya super-admin global yang memilih perusahaan; lainnya dikunci di controller.
            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')],
            'password' => $passwordRule,
            'level' => ['required', 'integer', Rule::in([1, 2, 3, 4])],
            'is_super_admin' => ['required', 'boolean'],
            'division' => ['nullable', Rule::in(User::DIVISIONS)],
            'managed_divisions' => ['nullable', 'array'],
            'managed_divisions.*' => [Rule::in(User::DIVISIONS)],
            'position' => ['nullable', Rule::in(User::POSITIONS)],
            'is_active'     => ['required', 'boolean'],
            'work_schedule' => ['nullable', Rule::in(array_keys(User::WORK_SCHEDULES))],
        ];
    }
}
