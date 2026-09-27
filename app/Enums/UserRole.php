<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Veterinarian = 'veterinarian';
    case Nurse = 'nurse';
    case Receptionist = 'receptionist';

    public function canAdministerMedication(): bool
    {
        return in_array($this, [self::Admin, self::Veterinarian, self::Nurse], true);
    }
}
