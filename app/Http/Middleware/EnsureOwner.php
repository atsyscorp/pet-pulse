<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Owner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Owner endpoints accept only owner tokens, never a staff account. */
final class EnsureOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() instanceof Owner, Response::HTTP_FORBIDDEN, 'Esta sección es solo para propietarios.');

        return $next($request);
    }
}
