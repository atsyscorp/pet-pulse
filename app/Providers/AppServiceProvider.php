<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\IdentifyClinic;
use App\Models\Drug;
use App\Models\Hospitalization;
use App\Models\KardexSchedule;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Scoped: flushed between Octane requests and queued jobs, so a tenant
        // can never bleed into the next unit of work on a long-lived worker.
        $this->app->scoped(TenantContext::class);
    }

    public function boot(): void
    {
        // Stable, refactor-proof values in drug_fractions_log.source_type.
        Relation::enforceMorphMap([
            'kardex_schedule' => KardexSchedule::class,
            'hospitalization' => Hospitalization::class,
            'drug' => Drug::class,
        ]);

        // Surface lazy loading / silently discarded attributes during development.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Re-apply tenant resolution on every Livewire round-trip.
        Livewire::addPersistentMiddleware([IdentifyClinic::class]);
    }
}
