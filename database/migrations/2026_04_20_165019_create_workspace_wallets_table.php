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
        Schema::create('workspace_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('currency', 3)->default('TZS');
            $table->decimal('available_balance', 14, 2)->default(0);
            $table->decimal('pending_withdrawal_balance', 14, 2)->default(0);
            $table->decimal('lifetime_credited', 14, 2)->default(0);
            $table->decimal('lifetime_withdrawn', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_wallets');
    }
};
