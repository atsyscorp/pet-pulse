<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'clinic_id', 'name', 'species', 'breed', 'sex', 'birth_date', 'microchip',
    'owner_name', 'owner_document', 'owner_phone',
])]
class Patient extends Model
{
    use BelongsToClinic;

    /** @use HasFactory<PatientFactory> */
    use HasFactory;

    use SoftDeletes;

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    /** @return HasMany<Hospitalization, $this> */
    public function hospitalizations(): HasMany
    {
        return $this->hasMany(Hospitalization::class);
    }
}
