<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'name',
        'nip',
        'email',
        'password',
        'role',
        'section_id',
        'is_active',
        'theme',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Seksi / unit kerja pegawai.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Seluruh transaksi peminjaman oleh user ini.
     */
    public function loans(): HasMany
    {
        return $this->hasMany(DocumentLoan::class, 'borrower_id');
    }

    /**
     * Transaksi peminjaman yang disetujui oleh user ini.
     */
    public function approvedLoans(): HasMany
    {
        return $this->hasMany(DocumentLoan::class, 'approved_by');
    }

    /**
     * Transaksi pengembalian yang diterima oleh user ini.
     */
    public function receivedLoans(): HasMany
    {
        return $this->hasMany(DocumentLoan::class, 'received_by');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPER_ADMIN;
    }

    public function isArchivist(): bool
    {
        return $this->role === UserRole::ARCHIVIST;
    }

    public function isEmployee(): bool
    {
        return $this->role === UserRole::EMPLOYEE;
    }

    /**
     * Helper apakah pengguna adalah staf arsip (super_admin atau archivist).
     */
    public function isStaff(): bool
    {
        return $this->canManageArchives();
    }

    /**
     * Super admin is a superset of archivist.
     */
    public function canManageArchives(): bool
    {
        return $this->role?->canManageArchives() ?? false;
    }

    /**
     * Scope query untuk user yang aktif.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
