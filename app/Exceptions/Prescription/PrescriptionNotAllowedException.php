<?php

declare(strict_types=1);

namespace App\Exceptions\Prescription;

use DomainException;

final class PrescriptionNotAllowedException extends DomainException
{
    public static function stayClosed(): self
    {
        return new self('La hospitalización ya no está activa; no se pueden agregar prescripciones.');
    }

    public static function drugInactive(): self
    {
        return new self('El medicamento está inactivo en el catálogo.');
    }

    public static function notActive(): self
    {
        return new self('La prescripción ya no está activa.');
    }
}
