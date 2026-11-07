<?php

namespace App\Http\Requests\ArchiveDocument;

use App\Enums\DocumentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageArchives() ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    DocumentStatus::AVAILABLE->value,
                    DocumentStatus::LOST->value,
                    DocumentStatus::INVALID->value,
                ]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'status fisik dokumen',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Status fisik dokumen hanya boleh diubah ke: tersedia (available), hilang (lost), atau rusak/tidak sah (invalid).',
        ];
    }
}
