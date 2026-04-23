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
            $table->unsignedInteger('provisioning_attempts')->default(0)->after('provisioning_error');
            $table->timestamp('provisioning_last_attempted_at')->nullable()->after('provisioning_attempts');
            $table->timestamp('provisioning_next_retry_at')->nullable()->after('provisioning_last_attempted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn([
                'provisioning_attempts',
                'provisioning_last_attempted_at',
                'provisioning_next_retry_at',
            ]);
        });
    }
};
