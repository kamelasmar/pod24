<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pricing_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['weekend', 'after_hours']);
            $table->unsignedInteger('percentage');             // e.g., 25 = +25%
            $table->time('after_hours_start')->nullable();
            $table->time('after_hours_end')->nullable();
            $table->timestamps();

            $table->unique(['facility_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_modifiers');
    }
};
