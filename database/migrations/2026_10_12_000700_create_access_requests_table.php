<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('clinic_name', 150);
            $table->string('tax_id', 30)->nullable();
            $table->string('city', 100);
            $table->unsignedSmallInteger('beds')->nullable();
            $table->string('contact_name', 120);
            $table->string('email', 190);
            $table->string('phone', 30);
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('consented_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_requests');
    }
};
