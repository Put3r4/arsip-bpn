<?php

namespace App\Http\Requests\DocumentLoan;

use Illuminate\Foundation\Http\FormRequest;

class BookDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Prepare the data for validation.
     * Sesuai A4: Pegawai hanya boleh booking untuk dirinya sendiri.
     * Staf arsip boleh booking atas nama pegawai lain atau dirinya sendiri.
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();

        if (! $user->canManageArchives()) {
            // Pegawai biasa selalu terkunci ke ID dirinya sendiri
            $this->merge([
                'borrower_id' => $user->id,
            ]);
        } else {
            // Staf arsip: jika borrower_id tidak diisi, default ke dirinya sendiri
            if (! $this->filled('borrower_id')) {
                $this->merge([
                    'borrower_id' => $user->id,
                ]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'archive_document_id' => ['required', 'integer', 'exists:archive_documents,id'],
            'service_activity_id' => ['required', 'integer', 'exists:service_activities,id'],
            'borrower_id' => ['required', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Pesan validasi dalam bahasa Indonesia (A2).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'archive_document_id.required' => 'Dokumen arsip wajib dipilih.',
            'archive_document_id.exists' => 'Dokumen arsip tidak ditemukan.',
            'service_activity_id.required' => 'Kegiatan pelayanan wajib dipilih.',
            'service_activity_id.exists' => 'Kegiatan pelayanan tidak valid.',
            'borrower_id.required' => 'Pegawai peminjam wajib ditentukan.',
            'borrower_id.exists' => 'Pegawai peminjam tidak ditemukan.',
            'notes.max' => 'Keterangan tidak boleh lebih dari 500 karakter.',
        ];
    }
}
