<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A clinic asking to use PetPulse. Not tenant-owned: it exists before the
 * clinic does, so it deliberately has no ClinicScope.
 */
#[Fillable(['clinic_name', 'tax_id', 'city', 'beds', 'contact_name', 'email', 'phone', 'message', 'status', 'consented_at'])]
class AccessRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    protected function casts(): array
    {
        return [
            'beds' => 'integer',
            'consented_at' => 'datetime',
        ];
    }
}
