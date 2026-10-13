<?php

declare(strict_types=1);

namespace App\Http\Resources\Owner;

use App\Enums\HospitalizationStatus;
use App\Models\Hospitalization;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A pet as its owner sees it. Deliberately leaves out internal data: triage
 * level, bed, attending staff notes, doses, stock. The owner-facing status is
 * the one the clinic set for them.
 *
 * @mixin Patient
 */
final class PetResource extends JsonResource
{
    /** Eager loads every endpoint needs to render this resource. */
    public const array RELATIONS = [
        'clinic:id,name',
        'latestHospitalization',
    ];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Hospitalization|null $stay */
        $stay = $this->latestHospitalization;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'species' => $this->species,
            'breed' => $this->breed,
            'sex' => $this->sex?->label(),
            'birth_date' => $this->birth_date?->toDateString(),
            'age' => $this->age(),
            'microchip' => $this->microchip,
            'clinic' => ['id' => $this->clinic?->id, 'name' => $this->clinic?->name],
            'hospitalization' => $stay === null ? null : [
                'id' => $stay->id,
                'active' => $stay->status === HospitalizationStatus::Admitted,
                'status' => $stay->status === HospitalizationStatus::Admitted ? 'hospitalized' : 'closed',
                'status_label' => $stay->status === HospitalizationStatus::Admitted ? 'Hospitalizado' : $stay->status->label(),
                'owner_status' => $stay->owner_status?->value,
                'owner_status_label' => $stay->owner_status?->label(),
                'owner_status_updated_at' => $stay->owner_status_updated_at?->toIso8601String(),
                'admitted_at' => $stay->admitted_at->toIso8601String(),
                'discharged_at' => $stay->discharged_at?->toIso8601String(),
                'weight_kg' => (float) $stay->current_weight_kg,
                'discharge_notes' => $stay->status !== HospitalizationStatus::Admitted ? $stay->discharge_notes : null,
            ],
        ];
    }
}
