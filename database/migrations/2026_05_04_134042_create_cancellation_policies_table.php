<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cancellation_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('hours_before_min');
            $table->unsignedTinyInteger('refund_percentage');  // 0-100
            $table->timestamps();

            $table->index(['facility_id', 'hours_before_min']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellation_policies');
    }
};
