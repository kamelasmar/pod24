<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('facility_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('service_tier_id')->constrained()->restrictOnDelete();
            $table->enum('package_type', ['hourly', 'half_day', 'full_day', 'multi_day']);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedInteger('total_hours');
            $table->enum('status', ['hold', 'pending_payment', 'confirmed', 'completed', 'cancelled']);

            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            $table->json('address');

            $table->unsignedInteger('subtotal_aed_cents');
            $table->unsignedInteger('weekend_markup_aed_cents')->default(0);
            $table->unsignedInteger('after_hours_markup_aed_cents')->default(0);
            $table->unsignedInteger('addons_aed_cents')->default(0);
            $table->unsignedInteger('hour_pack_credits_used')->default(0);
            $table->unsignedInteger('hour_pack_credit_value_aed_cents')->default(0);
            $table->unsignedInteger('vat_aed_cents');
            $table->unsignedInteger('total_aed_cents');

            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->timestamp('hold_expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->enum('cancelled_by', ['customer', 'admin'])->nullable();
            $table->unsignedInteger('refund_amount_aed_cents')->nullable();
            $table->timestamp('marketing_consent_at')->nullable();

            $table->timestamps();

            $table->index(['facility_id', 'starts_at', 'status']);
            $table->index('hold_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
