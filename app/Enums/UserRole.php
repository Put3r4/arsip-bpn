<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ARCHIVIST = 'archivist';
    case EMPLOYEE = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ARCHIVIST => 'Petugas Arsip',
            self::EMPLOYEE => 'Pegawai',
        };
    }

    public function isSuperAdmin(): bool
    {
        return $this === self::SUPER_ADMIN;
    }

    public function isArchivist(): bool
    {
        return $this === self::ARCHIVIST;
    }

    public function isEmployee(): bool
    {
        return $this === self::EMPLOYEE;
    }

    /**
     * Super admin is a superset of archivist.
     */
    public function canManageArchives(): bool
    {
        return in_array($this, [self::SUPER_ADMIN, self::ARCHIVIST], true);
    }
}
