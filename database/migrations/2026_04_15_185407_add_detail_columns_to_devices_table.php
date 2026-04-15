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
        Schema::table('devices', function (Blueprint $table) {
            $table->string('firmware_version')->nullable()->after('model');
            $table->unsignedInteger('clients_count')->default(0)->after('firmware_version');
            $table->unsignedBigInteger('uptime_seconds')->default(0)->after('clients_count');
            $table->string('channel_2g')->nullable()->after('uptime_seconds');
            $table->string('channel_5g')->nullable()->after('channel_2g');
            $table->unsignedSmallInteger('tx_power_2g')->nullable()->after('channel_5g');
            $table->unsignedSmallInteger('tx_power_5g')->nullable()->after('tx_power_2g');
            $table->string('omada_device_id')->nullable()->after('ap_mac');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'firmware_version', 'clients_count', 'uptime_seconds',
                'channel_2g', 'channel_5g', 'tx_power_2g', 'tx_power_5g',
                'omada_device_id',
            ]);
        });
    }
};
