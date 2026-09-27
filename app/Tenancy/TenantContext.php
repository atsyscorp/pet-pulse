<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Exceptions\Tenancy\MissingTenantException;
use App\Models\Clinic;

/**
 * Request-scoped holder of the active clinic (tenant).
 *
 * Registered as a scoped singleton so it is reset between Octane requests and
 * queued jobs. Populated by the IdentifyClinic middleware for HTTP/Livewire
 * requests, and explicitly via run() for jobs, commands and tests.
 */
final class TenantContext
{
    private ?int $clinicId = null;

    public function set(Clinic|int $clinic): void
    {
        $this->clinicId = $clinic instanceof Clinic ? $clinic->getKey() : $clinic;
    }

    public function forget(): void
    {
        $this->clinicId = null;
    }

    public function id(): ?int
    {
        return $this->clinicId;
    }

    public function hasTenant(): bool
    {
        return $this->clinicId !== null;
    }

    /** @throws MissingTenantException */
    public function idOrFail(): int
    {
        return $this->clinicId ?? throw new MissingTenantException;
    }

    /**
     * Execute a callback within a given tenant, restoring the previous one afterwards.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function run(Clinic|int $clinic, callable $callback): mixed
    {
        $previous = $this->clinicId;
        $this->set($clinic);

        try {
            return $callback();
        } finally {
            $this->clinicId = $previous;
        }
    }
}
