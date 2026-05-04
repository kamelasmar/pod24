<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->enum('type', ['corporate'])->default('corporate');
            $table->enum('status', ['new', 'contacted', 'quoted', 'won', 'lost'])->default('new');
            $table->string('event_type');
            $table->string('attendees_estimate')->nullable();
            $table->string('days_estimate')->nullable();
            $table->string('location_preference')->nullable();
            $table->json('service_interests')->nullable();
            $table->string('preferred_dates')->nullable();
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            $table->string('contact_company')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
            $table->index(['type', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
