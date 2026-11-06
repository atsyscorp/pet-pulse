<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;

final class PrescriptionPolicy
{
    public function suspend(User $user, Prescription $prescription): bool
    {
        return $user->belongsToClinic((int) $prescription->clinic_id)
            && $user->role->canPrescribe()
            && $prescription->isActive();
    }
}
