<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Hospitalization;
use App\Models\User;

final class HospitalizationPolicy
{
    public function create(User $user): bool
    {
        return $user->clinic_id !== null && $user->role->canManageAdmissions();
    }

    public function discharge(User $user, Hospitalization $hospitalization): bool
    {
        return $user->belongsToClinic((int) $hospitalization->clinic_id)
            && $user->role->canManageAdmissions()
            && $hospitalization->isActive();
    }
}
