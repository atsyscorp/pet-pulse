<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hospital\AdministerDoseRequest;
use App\Models\KardexSchedule;
use App\Services\Hospitalization\KardexAdministrationService;
use Illuminate\Http\JsonResponse;

final class AdministerDoseController extends Controller
{
    /**
     * $schedule is resolved by implicit binding *after* IdentifyClinic, so the
     * ClinicScope applies and another clinic's id yields a 404.
     */
    public function __invoke(AdministerDoseRequest $request, KardexSchedule $schedule, KardexAdministrationService $service): JsonResponse
    {

        // Domain exceptions (KardexException) render themselves as 409 — see bootstrap/app.php.
        $service->administerDose($schedule, (int) $request->user()->getKey(), $request->validated('notes'));

        return response()->json([
            'id' => $schedule->getKey(),
            'status' => $schedule->status->value,
            'administered_at' => $schedule->administered_at?->toIso8601String(),
        ]);
    }
}
