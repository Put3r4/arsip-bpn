<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $targetUser = $this->route('user');

        return $this->user()?->can('update', $targetUser) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $targetUser = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($targetUser),
            ],
            'nip' => ['nullable', 'string', 'max:50'],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($targetUser),
            ],
            'role' => ['required', new Enum(UserRole::class)],
            'section_id' => [
                Rule::requiredIf(fn () => in_array($this->input('role'), [
                    UserRole::EMPLOYEE->value,
                    UserRole::ARCHIVIST->value,
                    UserRole::EMPLOYEE,
                    UserRole::ARCHIVIST,
                ], true)),
                'nullable',
                'exists:sections,id',
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => $this->boolean('is_active'),
            ]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var \App\Models\User|null $targetUser */
            $targetUser = $this->route('user');
            if (! $targetUser) {
                return;
            }

            $isCurrentlyActiveSuperAdmin = $targetUser->isSuperAdmin() && $targetUser->is_active;

            $newRole = $this->input('role');
            $newIsActive = $this->boolean('is_active');
            $willRemainActiveSuperAdmin = in_array($newRole, [UserRole::SUPER_ADMIN->value, UserRole::SUPER_ADMIN], true) && $newIsActive;

            if ($isCurrentlyActiveSuperAdmin && ! $willRemainActiveSuperAdmin) {
                $activeSuperAdminCount = \App\Models\User::where('role', UserRole::SUPER_ADMIN)
                    ->where('is_active', true)
                    ->count();

                if ($activeSuperAdminCount <= 1) {
                    $validator->errors()->add(
                        'role',
                        'Tidak dapat menonaktifkan atau mengubah peran satu-satunya Super Admin aktif yang tersisa.'
                    );
                }
            }
        });
    }
}
