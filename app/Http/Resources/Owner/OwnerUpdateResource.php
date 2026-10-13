<?php

declare(strict_types=1);

namespace App\Http\Resources\Owner;

use App\Models\OwnerUpdate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OwnerUpdate */
final class OwnerUpdateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hospitalization_id' => $this->hospitalization_id,
            'body' => $this->body,
            'author' => $this->author?->name,
            'author_role' => $this->author?->role->label(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
