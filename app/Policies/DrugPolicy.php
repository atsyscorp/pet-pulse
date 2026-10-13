<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Drug;
use App\Models\User;

final class DrugPolicy
{
    /** The whole team can consult the catalog and stock. */
    public function viewAny(User $user): bool
    {
        return $user->clinic_id !== null;
    }

    public function view(User $user, Drug $drug): bool
    {
        return $user->belongsToClinic((int) $drug->clinic_id);
    }

    public function create(User $user): bool
    {
        return $user->clinic_id !== null && $user->role->canManageDrugs();
    }

    public function update(User $user, Drug $drug): bool
    {
        return $this->view($user, $drug) && $user->role->canManageDrugs();
    }

    public function receiveStock(User $user, Drug $drug): bool
    {
        return $this->update($user, $drug);
    }
}
