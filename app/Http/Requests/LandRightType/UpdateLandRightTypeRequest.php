<?php

namespace App\Http\Requests\LandRightType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLandRightTypeRequest extends FormRequest
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
        $rightId = $this->route('land_right_type')?->id ?? $this->route('land_right_type');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('land_right_types', 'name')->ignore($rightId)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama jenis hak tanah',
        ];
    }
}
