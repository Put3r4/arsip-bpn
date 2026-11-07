<?php

namespace App\Models;

use Database\Factories\LandRightTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandRightType extends Model
{
    /** @use HasFactory<LandRightTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Dokumen dengan jenis hak ini (HM, HGU, HGB, HP, dll).
     */
    public function archiveDocuments(): HasMany
    {
        return $this->hasMany(ArchiveDocument::class);
    }
}
