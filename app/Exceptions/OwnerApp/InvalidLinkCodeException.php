<?php

declare(strict_types=1);

namespace App\Exceptions\OwnerApp;

use DomainException;

/**
 * One message for unknown, expired, used and revoked codes alike: telling
 * them apart would help someone guessing codes.
 */
final class InvalidLinkCodeException extends DomainException
{
    public function __construct()
    {
        parent::__construct('El código no es válido o ya venció. Pida uno nuevo en su clínica.');
    }
}
