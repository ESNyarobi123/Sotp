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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ap_mac', 17)->unique();
            $table->string('site_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('model')->nullable();
            $table->enum('status', ['online', 'offline', 'unknown'])->default('unknown');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('site_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
