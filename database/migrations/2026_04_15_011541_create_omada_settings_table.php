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
        Schema::create('omada_settings', function (Blueprint $table) {
            $table->id();
            $table->string('controller_url');
            $table->text('username')->comment('Encrypted');
            $table->text('password')->comment('Encrypted');
            $table->text('api_key')->nullable()->comment('Encrypted');
            $table->string('hotspot_operator_name')->nullable();
            $table->text('hotspot_operator_password')->nullable()->comment('Encrypted');
            $table->string('external_portal_url')->nullable();
            $table->string('site_id')->nullable();
            $table->string('omada_id')->nullable();
            $table->boolean('is_connected')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('omada_settings');
    }
};
