<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\KardexSchedule;
use App\Models\User;

final class KardexSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->clinic_id !== null;
    }

    public function administer(User $user, KardexSchedule $schedule): bool
    {
        return $user->belongsToClinic((int) $schedule->clinic_id)
            && $user->role->canAdministerMedication();
    }
}
