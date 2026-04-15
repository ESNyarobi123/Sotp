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
        Schema::create('guest_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('client_mac', 17)->index();
            $table->string('ap_mac', 17)->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('ssid')->nullable();
            $table->string('username')->nullable();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->decimal('data_used_mb', 12, 2)->default(0);
            $table->decimal('data_limit_mb', 12, 2)->nullable()->comment('Null = unlimited');
            $table->timestamp('time_started')->nullable();
            $table->timestamp('time_expires')->nullable();
            $table->timestamp('time_ended')->nullable();
            $table->enum('status', ['active', 'expired', 'disconnected'])->default('active');
            $table->string('omada_auth_id')->nullable()->comment('Tracking ID from Omada controller');
            $table->timestamps();

            $table->index('status');
            $table->index(['client_mac', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_sessions');
    }
};
