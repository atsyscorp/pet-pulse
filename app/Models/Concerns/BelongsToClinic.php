<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Tenancy\TenantMismatchException;
use App\Models\Clinic;
use App\Models\Scopes\ClinicScope;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait BelongsToClinic
{
    public static function bootBelongsToClinic(): void
    {
        static::addGlobalScope(new ClinicScope);

        static::creating(function (Model $model): void {
            $clinicId = app(TenantContext::class)->id();

            if ($model->getAttribute('clinic_id') === null && $clinicId !== null) {
                $model->setAttribute('clinic_id', $clinicId);
            }

            // Defence in depth: never allow writing a row into a foreign tenant.
            if ($clinicId !== null && (int) $model->getAttribute('clinic_id') !== $clinicId) {
                throw new TenantMismatchException;
            }
        });
    }

    /** @return BelongsTo<Clinic, $this> */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
