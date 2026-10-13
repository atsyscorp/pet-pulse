<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A "parte médico": a note written by the clinic for the owner. */
#[Fillable(['clinic_id', 'hospitalization_id', 'author_id', 'body'])]
class OwnerUpdate extends Model
{
    use BelongsToClinic;

    /** @return BelongsTo<Hospitalization, $this> */
    public function hospitalization(): BelongsTo
    {
        return $this->belongsTo(Hospitalization::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
