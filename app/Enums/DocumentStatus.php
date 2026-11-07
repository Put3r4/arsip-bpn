<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case AVAILABLE = 'available';
    case BOOKED = 'booked';
    case BORROWED = 'borrowed';
    case LOST = 'lost';
    case INVALID = 'invalid';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Tersedia',
            self::BOOKED => 'Booking',
            self::BORROWED => 'Dipinjam',
            self::LOST => 'Hilang',
            self::INVALID => 'Tidak Berlaku',
        };
    }

    /**
     * Functional colors for status badges
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::AVAILABLE => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            self::BOOKED => 'bg-amber-100 text-amber-800 border-amber-300',
            self::BORROWED => 'bg-rose-100 text-rose-800 border-rose-300',
            self::LOST => 'bg-slate-100 text-slate-800 border-slate-300',
            self::INVALID => 'bg-zinc-100 text-zinc-700 border-zinc-300',
        };
    }
}
