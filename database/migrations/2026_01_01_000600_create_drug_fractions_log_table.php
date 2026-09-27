<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only pharmacological ledger.
 *
 * Every mutation of drugs.stock_packages / drugs.open_fraction_balance writes
 * exactly one row here *inside the same transaction*, including the resulting
 * balances. This gives:
 *   - a replayable ledger (sum of deltas == current stock) for reconciliation,
 *   - the "libro de control" trail required for controlled substances,
 *   - a polymorphic link (`source`) to whatever caused the movement
 *     (KardexSchedule, a purchase order, a manual waste record...).
 *
 * Rows are never updated or deleted (enforced in App\Models\DrugFractionLog).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drug_fractions_log', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('drug_id')->constrained()->restrictOnDelete();
            $table->nullableMorphs('source');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // package_received | package_opened | dose_administered | waste | expired_waste | adjustment
            $table->string('movement_type', 24);
            // Signed deltas: packages is an integer count, fraction is in drugs.fraction_unit.
            $table->integer('packages_delta')->default(0);
            $table->decimal('fraction_delta', 12, 4)->default(0);
            // Snapshot after the movement — makes audits O(1) and detects drift.
            $table->unsignedInteger('stock_packages_after');
            $table->decimal('open_fraction_balance_after', 10, 4);

            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['clinic_id', 'drug_id', 'created_at']);
            $table->index(['clinic_id', 'movement_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drug_fractions_log');
    }
};
