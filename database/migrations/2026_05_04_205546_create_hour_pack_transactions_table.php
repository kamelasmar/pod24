<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hour_pack_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained()->restrictOnDelete();
            $table->integer('hours'); // signed: positive=credit, negative=debit
            $table->enum('type', ['purchase', 'redeem', 'expire', 'admin_adjust']);
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stripe_charge_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['customer_id', 'facility_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hour_pack_transactions');
    }
};
