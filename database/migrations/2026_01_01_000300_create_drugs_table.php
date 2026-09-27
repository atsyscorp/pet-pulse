<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drug master catalog with dual-state stock.
 *
 * Stock lives in two buckets that are always mutated together under a row lock:
 *   - stock_packages:        sealed, countable units (vials, ampoules, bottles, blisters).
 *   - open_fraction_balance: what is left of the *currently opened* units, expressed
 *                            in `fraction_unit` (mL, tablet, g, mg...).
 *
 * Total available = stock_packages * volume_per_presentation + open_fraction_balance.
 *
 * All quantities are DECIMAL(10,4) — never FLOAT — so 0.0125 mL micro-doses
 * aggregate exactly. Arithmetic in PHP is done with brick/math BigDecimal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drugs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('active_ingredient')->nullable();
            // Colombian INVIMA / ICA sanitary registration number.
            $table->string('sanitary_registry', 64)->nullable();

            // Concentration, e.g. 50.0000 mg per 1 mL => used by the dose calculator.
            $table->decimal('concentration_amount', 10, 4)->nullable();
            $table->string('concentration_unit', 16)->nullable(); // mg, mcg, UI
            $table->string('presentation_unit', 32);                // vial, ampoule, bottle, blister
            $table->decimal('volume_per_presentation', 10, 4);      // e.g. 10.0000 (mL per vial)
            $table->string('fraction_unit', 16);                    // mL, tablet, g

            $table->unsignedInteger('stock_packages')->default(0);
            $table->decimal('open_fraction_balance', 10, 4)->default(0);
            $table->unsignedInteger('reorder_level_packages')->default(0);

            // In-use stability once unsealed (multi-dose vials). NULL = no limit.
            $table->unsignedInteger('stability_hours_after_opening')->nullable();
            $table->timestamp('opened_at')->nullable();

            // "Medicamento de control especial" (Res. 1478/2006 – FNE). Enforces
            // mandatory notes and a stricter audit trail on administration.
            $table->boolean('is_controlled')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['clinic_id', 'name']);
            $table->index(['clinic_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drugs');
    }
};
