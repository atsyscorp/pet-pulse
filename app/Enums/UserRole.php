<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Veterinarian = 'veterinarian';
    case Nurse = 'nurse';
    case Receptionist = 'receptionist';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Veterinarian => 'Veterinario',
            self::Nurse => 'Auxiliar de enfermería',
            self::Receptionist => 'Recepción',
        };
    }

    public function canAdministerMedication(): bool
    {
        return in_array($this, [self::Admin, self::Veterinarian, self::Nurse], true);
    }

    /** Everyone at the front desk or on the ward can register and edit patients. */
    public function canManagePatients(): bool
    {
        return true;
    }

    /** Prescribing is a veterinary act (Ley 576 de 2000); admins keep it for small clinics run by a vet-owner. */
    public function canPrescribe(): bool
    {
        return in_array($this, [self::Admin, self::Veterinarian], true);
    }

    /** Creating drugs and receiving stock: the people accountable for the pharmacy. */
    public function canManageDrugs(): bool
    {
        return in_array($this, [self::Admin, self::Veterinarian], true);
    }

    /** Admitting and discharging is a clinical decision. */
    public function canManageAdmissions(): bool
    {
        return in_array($this, [self::Admin, self::Veterinarian, self::Nurse], true);
    }
}
