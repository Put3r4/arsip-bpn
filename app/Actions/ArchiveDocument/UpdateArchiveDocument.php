<?php

namespace App\Actions\ArchiveDocument;

use App\Models\ArchiveDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateArchiveDocument
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(ArchiveDocument $document, array $data, ?UploadedFile $file = null): ArchiveDocument
    {
        $year = isset($data['year']) && $data['year'] !== '' && $data['year'] !== '-'
            ? (string) $data['year']
            : null;

        $updateData = [
            'document_number' => trim((string) $data['document_number']),
            'year' => $year,
            'document_type_id' => $data['document_type_id'],
            'land_right_type_id' => $data['land_right_type_id'],
            'storage_rack_id' => $data['storage_rack_id'],
            'range_number' => isset($data['range_number']) && trim((string) $data['range_number']) !== ''
                ? trim((string) $data['range_number'])
                : null,
            'district_id' => $data['district_id'],
            'village_id' => $data['village_id'],
        ];

        if ($file instanceof UploadedFile) {
            // Bersihkan file lama jika ada
            if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
                Storage::disk('local')->delete($document->file_path);
            }

            $yearFolder = now()->format('Y');
            $monthFolder = now()->format('m');
            $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin';
            $filename = (string) Str::uuid() . '.' . strtolower($extension);
            $directory = "documents/{$yearFolder}/{$monthFolder}";

            $updateData['file_path'] = $file->storeAs($directory, $filename, 'local');
            $updateData['original_filename'] = $file->getClientOriginalName();
            $updateData['file_size'] = $file->getSize();
            $updateData['mime_type'] = $file->getMimeType();
        }

        $document->update($updateData);

        return $document;
    }
}
