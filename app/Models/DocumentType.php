<?php

namespace App\Models;

use Database\Factories\DocumentTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    /** @use HasFactory<DocumentTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Dokumen dengan tipe ini (Buku Tanah, Surat Ukur, Warkah, dll).
     */
    public function archiveDocuments(): HasMany
    {
        return $this->hasMany(ArchiveDocument::class);
    }
}
