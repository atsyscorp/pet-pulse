<?php

declare(strict_types=1);

namespace App\Http\Resources\Owner;

use App\Models\KardexSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A dose already given, as the owner sees it: what, how and when. No amounts
 * (the clinic explains dosing in person) and no inventory data.
 *
 * @mixin KardexSchedule
 */
final class MedicationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'drug' => $this->drug?->name,
            'active_ingredient' => $this->drug?->active_ingredient,
            'route' => $this->route->value,
            'route_label' => $this->route->label(),
            'administered_at' => $this->administered_at?->toIso8601String(),
        ];
    }
}
