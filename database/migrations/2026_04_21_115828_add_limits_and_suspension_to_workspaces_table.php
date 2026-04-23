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
        Schema::table('workspaces', function (Blueprint $table) {
            $table->unsignedInteger('max_devices')->default(10)->after('devices_last_synced_at');
            $table->unsignedInteger('max_plans')->default(20)->after('max_devices');
            $table->unsignedInteger('max_sessions')->default(0)->after('max_plans'); // 0 = unlimited
            $table->boolean('is_suspended')->default(false)->after('max_sessions');
            $table->string('suspension_reason')->nullable()->after('is_suspended');
            $table->timestamp('suspended_at')->nullable()->after('suspension_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn(['max_devices', 'max_plans', 'max_sessions', 'is_suspended', 'suspension_reason', 'suspended_at']);
        });
    }
};
