<?php

namespace App\Models;

use App\Enums\LoanStatus;
use Database\Factories\DocumentLoanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentLoan extends Model
{
    /** @use HasFactory<DocumentLoanFactory> */
    use HasFactory;

    protected $fillable = [
        'archive_document_id',
        'borrower_id',
        'service_activity_id',
        'approved_by',
        'received_by',
        'loan_date',
        'return_date',
        'status',
        'notes',
        'approved_at',
        'cancelled_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'loan_date' => 'date',
            'return_date' => 'date',
            'status' => LoanStatus::class,
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function archiveDocument(): BelongsTo
    {
        return $this->belongsTo(ArchiveDocument::class)->withTrashed();
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrower_id');
    }

    public function serviceActivity(): BelongsTo
    {
        return $this->belongsTo(ServiceActivity::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function isPending(): bool
    {
        return $this->status === LoanStatus::PENDING;
    }

    public function isBorrowed(): bool
    {
        return $this->status === LoanStatus::BORROWED;
    }

    public function isReturned(): bool
    {
        return $this->status === LoanStatus::RETURNED;
    }

    public function isCancelled(): bool
    {
        return $this->status === LoanStatus::CANCELLED;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', LoanStatus::PENDING);
    }

    public function scopeBorrowed(Builder $query): Builder
    {
        return $query->where('status', LoanStatus::BORROWED);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [LoanStatus::PENDING, LoanStatus::BORROWED]);
    }

    public function scopeReturned(Builder $query): Builder
    {
        return $query->where('status', LoanStatus::RETURNED);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', LoanStatus::CANCELLED);
    }

    public function scopeForBorrower(Builder $query, int $userId): Builder
    {
        return $query->where('borrower_id', $userId);
    }
}
