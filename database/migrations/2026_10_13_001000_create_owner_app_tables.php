<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pet owners and what they can see.
 *
 * Owners are NOT tenant-owned: one person can have pets in several clinics.
 * Their access is granted pet by pet, by the clinic, through a single-use
 * link code (owner_patient_links). Nothing reaches the owner app unless a
 * clinic linked it and has not revoked the link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owners', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 190)->unique();
            $table->string('phone', 32)->nullable();
            $table->string('password');
            // Consent to the data-processing policy (Ley 1581 de 2012).
            $table->timestamp('terms_accepted_at');
            $table->timestamps();
        });

        Schema::create('patient_link_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            // SHA-256 of the normalised code: a database leak does not leak usable codes.
            $table->char('code_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_by_owner_id')->nullable()->constrained('owners')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'used_at', 'revoked_at']);
        });

        Schema::create('owner_patient_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->timestamp('linked_at');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['owner_id', 'patient_id']);
            $table->index(['patient_id', 'revoked_at']);
        });

        // What the clinic chooses to tell the owner. The internal triage level
        // is never sent to the app; this simplified status is.
        Schema::table('hospitalizations', function (Blueprint $table): void {
            $table->string('owner_status', 16)->nullable()->after('triage_level');
            $table->timestamp('owner_status_updated_at')->nullable()->after('owner_status');
        });

        // "Partes médicos": short notes written for the owner.
        Schema::create('owner_updates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hospitalization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['hospitalization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_updates');
        Schema::table('hospitalizations', function (Blueprint $table): void {
            $table->dropColumn(['owner_status', 'owner_status_updated_at']);
        });
        Schema::dropIfExists('owner_patient_links');
        Schema::dropIfExists('patient_link_codes');
        Schema::dropIfExists('owners');
    }
};
