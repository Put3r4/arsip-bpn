<?php

namespace App\Http\Requests\ServiceActivity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageArchives() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim((string) $this->name),
            ]);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $activityId = $this->route('service_activity')?->id ?? $this->route('service_activity');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('service_activities', 'name')->ignore($activityId)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama kegiatan pelayanan',
        ];
    }
}
