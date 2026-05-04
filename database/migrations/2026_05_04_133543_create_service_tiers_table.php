<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('description')->nullable();
            $table->unsignedInteger('base_hourly_rate_aed_cents');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['facility_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_tiers');
    }
};
