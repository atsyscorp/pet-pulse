<?php

declare(strict_types=1);

namespace App\Exceptions\Tenancy;

use RuntimeException;

final class MissingTenantException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No clinic (tenant) is bound to the current execution context.');
    }
}
