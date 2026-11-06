<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A prescription is the veterinarian's order ("ceftriaxona 25 mg/kg IV cada
 * 12 h por 5 días"). Prescribing expands it into one kardex_schedules row per
 * planned dose, linked back through kardex_schedules.prescription_id, so the
 * board and the administration service keep working on individual doses.
 *
 * The dose is stored twice on purpose: the clinical intent (dose_per_kg and
 * the weight it was computed with) and the administrable amount in the drug's
 * fraction_unit (dose_amount), rounded once to 4 decimals at prescription time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hospitalization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('drug_id')->constrained()->restrictOnDelete();
            $table->foreignId('prescribed_by')->nullable()->constrained('users')->nullOnDelete();

            // per_kg | fixed — see App\Enums\DoseMode.
            $table->string('dose_mode', 8);
            $table->decimal('dose_per_kg', 10, 4)->nullable();      // in drugs.concentration_unit per kg
            $table->decimal('weight_kg', 8, 3)->nullable();         // weight used for the calculation
            $table->decimal('dose_amount', 10, 4);                  // per dose, in drugs.fraction_unit
            $table->string('route', 8);

            // NULL = single dose.
            $table->unsignedSmallInteger('frequency_hours')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedSmallInteger('doses_planned');
            $table->text('instructions')->nullable();

            // active | suspended | completed — see App\Enums\PrescriptionStatus.
            $table->string('status', 16)->default('active');
            $table->timestamp('suspended_at')->nullable();
            $table->foreignId('suspended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('suspension_reason')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'status']);
            $table->index(['hospitalization_id', 'status']);
        });

        Schema::table('kardex_schedules', function (Blueprint $table): void {
            $table->foreignId('prescription_id')->nullable()->after('hospitalization_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kardex_schedules', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('prescription_id');
        });

        Schema::dropIfExists('prescriptions');
    }
};
