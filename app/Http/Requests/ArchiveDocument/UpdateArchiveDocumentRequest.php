<?php

namespace App\Http\Requests\ArchiveDocument;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArchiveDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageArchives() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $normalizedYear = $this->has('year') ? trim((string) $this->year) : null;
        if ($normalizedYear === '-' || $normalizedYear === '') {
            $normalizedYear = null;
        }

        $this->merge([
            'document_number' => $this->has('document_number') ? trim((string) $this->document_number) : null,
            'year' => $normalizedYear,
            'range_number' => $this->has('range_number') && trim((string) $this->range_number) !== ''
                ? trim((string) $this->range_number)
                : null,
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_number' => ['required', 'string', 'max:50'],
            'year' => ['nullable', 'integer', 'between:1900,2100'],
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'land_right_type_id' => ['required', 'integer', 'exists:land_right_types,id'],
            'storage_rack_id' => ['required', 'integer', 'exists:storage_racks,id'],
            'range_number' => ['nullable', 'string', 'max:50'],
            'district_id' => ['required', 'string', 'size:7', 'exists:districts,id'],
            'village_id' => [
                'required',
                'string',
                'size:10',
                'exists:villages,id',
                Rule::exists('villages', 'id')->where('district_id', $this->district_id),
            ],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'document_number' => 'nomor berkas',
            'year' => 'tahun',
            'document_type_id' => 'jenis berkas',
            'land_right_type_id' => 'jenis hak',
            'storage_rack_id' => 'rak penyimpanan',
            'range_number' => 'nomor urut/range',
            'district_id' => 'kecamatan',
            'village_id' => 'desa/kelurahan',
            'file' => 'berkas pindaian',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.max' => 'Ukuran berkas pindaian tidak boleh lebih dari 20 MB.',
            'file.mimes' => 'Format berkas pindaian harus berupa PDF, JPG, JPEG, atau PNG.',
            'village_id.exists' => 'Desa/kelurahan yang dipilih tidak berada di bawah kecamatan yang bersangkutan.',
        ];
    }
}
