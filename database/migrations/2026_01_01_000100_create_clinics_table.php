<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant root.
 *
 * Trade-off: we use a single shared schema with a `clinic_id` discriminator
 * (row-level tenancy) rather than database-per-tenant. For a LATAM SMB market
 * (hundreds to low thousands of clinics, many with 1–3 ICU beds) this keeps
 * migrations, Reverb channels, backups and cross-tenant analytics simple.
 * Every tenant-owned table carries an indexed `clinic_id` FK and every model
 * uses the BelongsToClinic global scope, so a later move to schema-per-tenant
 * only requires swapping the connection resolver, not the domain code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinics', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            // Colombian tax identifier (NIT) incl. verification digit, e.g. 900123456-7.
            $table->string('tax_id', 20)->nullable()->unique();
            $table->string('country_code', 2)->default('CO');
            $table->string('timezone', 64)->default('America/Bogota');
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            // admin | veterinarian | nurse | receptionist — see App\Enums\UserRole.
            $table->string('role', 32)->default('nurse')->after('email');
            $table->string('professional_card', 32)->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('clinic_id');
            $table->dropColumn(['role', 'professional_card']);
        });

        Schema::dropIfExists('clinics');
    }
};
