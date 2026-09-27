<?php

declare(strict_types=1);

namespace App\Exceptions\Tenancy;

use RuntimeException;

final class TenantMismatchException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Attempted to access or write a record that belongs to another clinic.');
    }
}
