<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospitalizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('attending_vet_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('bed_label', 32)->nullable();
            // critical | intermediate | observation — see App\Enums\TriageLevel.
            $table->string('triage_level', 16)->default('observation');
            $table->decimal('current_weight_kg', 8, 3);
            $table->timestamp('weight_recorded_at')->nullable();

            $table->text('admission_reason');
            $table->text('diagnosis')->nullable();
            $table->timestamp('admitted_at');

            // admitted | discharged | transferred | deceased | voluntary_discharge
            $table->string('status', 24)->default('admitted');
            $table->timestamp('discharged_at')->nullable();
            $table->text('discharge_notes')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'status', 'triage_level']);
            $table->index(['clinic_id', 'bed_label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospitalizations');
    }
};
