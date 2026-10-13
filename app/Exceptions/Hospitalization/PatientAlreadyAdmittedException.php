<?php

declare(strict_types=1);

namespace App\Exceptions\Hospitalization;

use App\Models\Patient;
use DomainException;

final class PatientAlreadyAdmittedException extends DomainException
{
    public static function for(Patient $patient): self
    {
        return new self(sprintf('%s ya tiene una hospitalización activa; dele de alta antes de registrar un nuevo ingreso.', $patient->name));
    }
}
