<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row = one planned administration (a cell on the nursing board).
 *
 * `dose_amount` is always expressed in the drug's `fraction_unit` so that the
 * administration service can deplete stock without any unit conversion inside
 * the critical section. The prescribing context (mg/kg, weight used) is kept
 * for clinical audit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kardex_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hospitalization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('drug_id')->constrained()->restrictOnDelete();
            $table->foreignId('prescribed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('dose_amount', 10, 4);                 // in drugs.fraction_unit
            $table->decimal('dose_per_kg', 10, 4)->nullable();     // prescribing context, e.g. 0.2000 mg/kg
            $table->decimal('weight_kg_at_prescription', 8, 3)->nullable();
            // IV | IM | SC | PO | IO | TOP | INH | REC | OPH | OT — see App\Enums\AdministrationRoute.
            $table->string('route', 8);

            $table->timestamp('scheduled_at');
            $table->timestamp('administered_at')->nullable();
            $table->foreignId('administered_by')->nullable()->constrained('users')->nullOnDelete();

            // pending | administered | omitted | cancelled
            $table->string('status', 16)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Board query: "all schedules of clinic X on day D, by status".
            $table->index(['clinic_id', 'scheduled_at', 'status']);
            $table->index(['hospitalization_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kardex_schedules');
    }
};
