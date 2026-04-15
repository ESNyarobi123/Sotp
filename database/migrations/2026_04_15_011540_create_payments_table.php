<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->string('phone_number', 20);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('TZS');
            $table->enum('payment_method', ['mpesa', 'airtel', 'tigo', 'card'])->default('mpesa');
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignId('guest_session_id')->nullable()->constrained('guest_sessions')->nullOnDelete();
            $table->string('client_mac', 17)->nullable()->index();
            $table->string('ap_mac', 17)->nullable();
            $table->string('mpesa_checkout_request_id')->nullable()->index();
            $table->string('mpesa_receipt_number')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('payment_method');
            $table->index('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
