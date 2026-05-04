<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facility_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_tier_id')->constrained()->cascadeOnDelete();
            $table->enum('package_type', ['hourly', 'half_day', 'full_day', 'multi_day']);
            $table->unsignedInteger('hours')->default(1);   // for half/full-day = 4/8; multi-day = 1 (per-day)
            $table->unsignedInteger('price_aed_cents');
            $table->timestamps();

            $table->unique(['facility_id', 'service_tier_id', 'package_type'], 'fp_facility_tier_package_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_pricing');
    }
};
