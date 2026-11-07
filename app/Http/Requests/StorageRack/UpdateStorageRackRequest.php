<?php

namespace App\Http\Requests\StorageRack;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStorageRackRequest extends FormRequest
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
        $rackId = $this->route('storage_rack')?->id ?? $this->route('storage_rack');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('storage_racks', 'name')->ignore($rackId)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama rak penyimpanan',
        ];
    }
}
