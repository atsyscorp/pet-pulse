<?php

declare(strict_types=1);

use App\Exceptions\Kardex\KardexException;
use App\Http\Middleware\IdentifyClinic;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['clinic' => IdentifyClinic::class]);

        // Tenant must be bound before implicit route-model binding runs.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: IdentifyClinic::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Business-rule violations are expected outcomes, not server errors.
        $exceptions->dontReport(KardexException::class);
        $exceptions->render(function (KardexException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 409);
            }

            return back()->withErrors(['kardex' => $e->getMessage()]);
        });
    })->create();
