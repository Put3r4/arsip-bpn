<?php

namespace App\Models;

use Database\Factories\StorageRackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageRack extends Model
{
    /** @use HasFactory<StorageRackFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Dokumen yang tersimpan di rak ini.
     */
    public function archiveDocuments(): HasMany
    {
        return $this->hasMany(ArchiveDocument::class);
    }
}
