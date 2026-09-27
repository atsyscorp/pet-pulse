<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('species', 32);
            $table->string('breed')->nullable();
            $table->string('sex', 16)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('microchip', 32)->nullable();
            // Owner data is personal data under Colombian Ley 1581 de 2012 (habeas data).
            $table->string('owner_name');
            $table->string('owner_document', 32)->nullable();
            $table->string('owner_phone', 32)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['clinic_id', 'name']);
            $table->unique(['clinic_id', 'microchip']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
