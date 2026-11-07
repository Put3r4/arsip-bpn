<?php

namespace App\Models;

use Database\Factories\ServiceActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceActivity extends Model
{
    /** @use HasFactory<ServiceActivityFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Transaksi peminjaman yang memakai jenis kegiatan ini.
     */
    public function loans(): HasMany
    {
        return $this->hasMany(DocumentLoan::class);
    }
}
