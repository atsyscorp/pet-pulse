<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
 * ICU / nursing board channel. Authorisation is evaluated against the user's
 * own clinic_id — never against request input — so a user can only subscribe
 * to their clinic's stream even if they tamper with the channel name.
 */
Broadcast::channel('clinic.{clinicId}.icu', function (User $user, int $clinicId): bool {
    return $user->belongsToClinic($clinicId);
});
