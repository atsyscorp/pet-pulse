<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restricts every query on tenant-owned models to the active clinic.
 *
 * When no tenant is resolved (console, seeders, system jobs) the scope is a
 * no-op; HTTP entry points are protected by the IdentifyClinic middleware,
 * which refuses to continue without a tenant.
 */
final class ClinicScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $clinicId = app(TenantContext::class)->id();

        if ($clinicId !== null) {
            $builder->where($model->qualifyColumn('clinic_id'), $clinicId);
        }
    }
}
