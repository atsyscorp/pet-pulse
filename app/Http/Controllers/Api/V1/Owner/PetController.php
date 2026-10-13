<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Owner;

use App\Enums\KardexStatus;
use App\Exceptions\OwnerApp\InvalidLinkCodeException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Owner\MedicationResource;
use App\Http\Resources\Owner\OwnerUpdateResource;
use App\Http\Resources\Owner\PetResource;
use App\Models\KardexSchedule;
use App\Models\Owner;
use App\Models\OwnerUpdate;
use App\Models\Patient;
use App\Services\OwnerApp\OwnerAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Everything here is read through $owner->pets(), so a pet the owner is not
 * linked to (or whose link was revoked) is a 404, whatever id is requested.
 */
final class PetController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $pets = $this->owner($request)->pets()
            ->withoutGlobalScopes()
            ->whereNull('patients.deleted_at')
            ->with(PetResource::RELATIONS)
            ->orderBy('patients.name')
            ->get();

        return PetResource::collection($pets);
    }

    public function show(Request $request, int $pet): PetResource
    {
        return new PetResource($this->findPet($request, $pet));
    }

    public function link(Request $request, OwnerAccessService $access): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40']], [], ['code' => 'código']);

        try {
            $patient = $access->redeem($this->owner($request), $data['code']);
        } catch (InvalidLinkCodeException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => ['code' => [$e->getMessage()]]], 422);
        }

        $patient->load(PetResource::RELATIONS);

        return (new PetResource($patient))->response()->setStatusCode(201);
    }

    /** Written updates ("partes médicos") from every stay, newest first. */
    public function updates(Request $request, int $pet): AnonymousResourceCollection
    {
        $patient = $this->findPet($request, $pet);

        $updates = OwnerUpdate::query()->withoutGlobalScopes()
            ->whereIn('hospitalization_id', $patient->hospitalizations()->withoutGlobalScopes()->select('id'))
            ->with(['author:id,name,role'])
            ->latest('id')
            ->paginate(20);

        return OwnerUpdateResource::collection($updates);
    }

    /** Doses already given, newest first. Pending or cancelled doses are not shown. */
    public function medications(Request $request, int $pet): AnonymousResourceCollection
    {
        $patient = $this->findPet($request, $pet);

        $doses = KardexSchedule::query()->withoutGlobalScopes()
            ->whereIn('hospitalization_id', $patient->hospitalizations()->withoutGlobalScopes()->select('id'))
            ->where('status', KardexStatus::Administered)
            ->with(['drug' => fn ($q) => $q->withoutGlobalScopes()->select('id', 'name', 'active_ingredient')])
            ->latest('administered_at')
            ->paginate(30);

        return MedicationResource::collection($doses);
    }

    private function owner(Request $request): Owner
    {
        /** @var Owner */
        return $request->user();
    }

    private function findPet(Request $request, int $id): Patient
    {
        return $this->owner($request)->pets()
            ->withoutGlobalScopes()
            ->whereNull('patients.deleted_at')
            ->with(PetResource::RELATIONS)
            ->where('patients.id', $id)
            ->firstOrFail();
    }
}
