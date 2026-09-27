<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Binds the authenticated user's clinic as the active tenant.
 *
 * Registered with higher priority than SubstituteBindings (bootstrap/app.php)
 * so that implicit route-model binding — e.g. /kardex/{schedule} — is already
 * resolved through the ClinicScope and a foreign id yields a 404, not a leak.
 * Also registered as Livewire persistent middleware so every /livewire/update
 * round-trip re-establishes the tenant.
 */
final class IdentifyClinic
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if($user === null, Response::HTTP_UNAUTHORIZED);
        abort_if($user->clinic_id === null, Response::HTTP_FORBIDDEN, 'El usuario no está asociado a ninguna clínica.');

        $clinic = $user->clinic;
        abort_if($clinic === null || ! $clinic->is_active, Response::HTTP_FORBIDDEN, 'Clínica inactiva.');

        $this->tenant->set($clinic);

        return $next($request);
    }
}
