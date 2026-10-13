<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

final class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->clinic_id !== null;
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->belongsToClinic((int) $patient->clinic_id);
    }

    public function create(User $user): bool
    {
        return $user->clinic_id !== null && $user->role->canManagePatients();
    }

    public function update(User $user, Patient $patient): bool
    {
        return $this->view($user, $patient) && $user->role->canManagePatients();
    }

    /** Handing out app codes and revoking access: the whole front desk and ward. */
    public function manageOwnerAccess(User $user, Patient $patient): bool
    {
        return $this->view($user, $patient) && $user->role->canManagePatients();
    }
}
