<?php

namespace App\Enums;

enum LoanStatus: string
{
    case PENDING = 'pending';
    case BORROWED = 'borrowed';
    case RETURNED = 'returned';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Persetujuan',
            self::BORROWED => 'Sedang Dipinjam',
            self::RETURNED => 'Sudah Dikembalikan',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::PENDING => 'bg-amber-100 text-amber-800 border-amber-300',
            self::BORROWED => 'bg-rose-100 text-rose-800 border-rose-300',
            self::RETURNED => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            self::CANCELLED => 'bg-slate-100 text-slate-700 border-slate-300',
        };
    }
}
