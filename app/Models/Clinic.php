<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ClinicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'tax_id', 'country_code', 'timezone', 'is_active', 'settings'])]
class Clinic extends Model
{
    /** @use HasFactory<ClinicFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Clinic $clinic): void {
            $clinic->uuid ??= (string) Str::uuid7();
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    /** Private Reverb channel name for the ICU wall display / nursing board. */
    public function icuChannelName(): string
    {
        return "clinic.{$this->getKey()}.icu";
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
