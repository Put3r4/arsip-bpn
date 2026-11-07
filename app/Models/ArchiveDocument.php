<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\LoanStatus;
use Database\Factories\ArchiveDocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArchiveDocument extends Model
{
    /** @use HasFactory<ArchiveDocumentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'document_number',
        'year',
        'document_type_id',
        'land_right_type_id',
        'storage_rack_id',
        'range_number',
        'district_id',
        'village_id',
        'status',
        'file_path',
        'original_filename',
        'file_size',
        'mime_type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'file_size' => 'integer',
        ];
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function landRightType(): BelongsTo
    {
        return $this->belongsTo(LandRightType::class);
    }

    public function storageRack(): BelongsTo
    {
        return $this->belongsTo(StorageRack::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id', 'id');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'village_id', 'id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(DocumentLoan::class);
    }

    /**
     * Relasi pinjaman aktif (pending atau borrowed).
     */
    public function activeLoan(): HasOne
    {
        return $this->hasOne(DocumentLoan::class)->whereIn('status', [
            LoanStatus::PENDING->value,
            LoanStatus::BORROWED->value,
        ]);
    }

    public function latestLoan(): HasOne
    {
        return $this->hasOne(DocumentLoan::class)->latestOfMany();
    }

    public function isAvailable(): bool
    {
        return $this->status === DocumentStatus::AVAILABLE;
    }

    public function isBooked(): bool
    {
        return $this->status === DocumentStatus::BOOKED;
    }

    public function isBorrowed(): bool
    {
        return $this->status === DocumentStatus::BORROWED;
    }

    public function isLost(): bool
    {
        return $this->status === DocumentStatus::LOST;
    }

    public function isInvalid(): bool
    {
        return $this->status === DocumentStatus::INVALID;
    }

    public function hasActiveLoan(): bool
    {
        return $this->loans()
            ->whereIn('status', [LoanStatus::PENDING, LoanStatus::BORROWED])
            ->exists();
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', DocumentStatus::AVAILABLE);
    }

    public function scopeBooked(Builder $query): Builder
    {
        return $query->where('status', DocumentStatus::BOOKED);
    }

    public function scopeBorrowed(Builder $query): Builder
    {
        return $query->where('status', DocumentStatus::BORROWED);
    }
}
