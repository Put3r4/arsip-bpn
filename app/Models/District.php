<?php

namespace App\Models;

use Database\Factories\DistrictFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    /** @use HasFactory<DistrictFactory> */
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'regency_id',
        'name',
    ];

    /**
     * Desa-desa di bawah kecamatan ini.
     */
    public function villages(): HasMany
    {
        return $this->hasMany(Village::class, 'district_id', 'id');
    }

    /**
     * Dokumen arsip di wilayah kecamatan ini.
     */
    public function archiveDocuments(): HasMany
    {
        return $this->hasMany(ArchiveDocument::class, 'district_id', 'id');
    }
}
