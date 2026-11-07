<?php

namespace App\Actions\ArchiveDocument;

use App\Enums\DocumentStatus;
use App\Models\ArchiveDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateArchiveDocument
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(array $data, ?UploadedFile $file = null): ArchiveDocument
    {
        $year = isset($data['year']) && $data['year'] !== '' && $data['year'] !== '-'
            ? (string) $data['year']
            : null;

        $filePath = null;
        $originalFilename = null;
        $fileSize = null;
        $mimeType = null;

        if ($file instanceof UploadedFile) {
            $yearFolder = now()->format('Y');
            $monthFolder = now()->format('m');
            $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin';
            $filename = (string) Str::uuid() . '.' . strtolower($extension);
            $directory = "documents/{$yearFolder}/{$monthFolder}";

            $filePath = $file->storeAs($directory, $filename, 'local');
            $originalFilename = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();
        }

        return ArchiveDocument::create([
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
            'status' => DocumentStatus::AVAILABLE,
            'file_path' => $filePath,
            'original_filename' => $originalFilename,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
        ]);
    }
}
