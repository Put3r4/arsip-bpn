<?php

namespace App\Models;

use Database\Factories\VillageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Village extends Model
{
    /** @use HasFactory<VillageFactory> */
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'district_id',
        'name',
    ];

    /**
     * Kecamatan induk desa ini.
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id', 'id');
    }

    /**
     * Dokumen arsip di wilayah desa ini.
     */
    public function archiveDocuments(): HasMany
    {
        return $this->hasMany(ArchiveDocument::class, 'village_id', 'id');
    }
}
