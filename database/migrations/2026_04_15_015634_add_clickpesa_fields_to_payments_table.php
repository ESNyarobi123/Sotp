<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('clickpesa_order_id')->nullable()->after('mpesa_receipt_number');
            $table->string('clickpesa_payment_reference')->nullable()->after('clickpesa_order_id');
            $table->string('clickpesa_channel')->nullable()->after('clickpesa_payment_reference');
        });

        // Expand payment_method enum to include clickpesa channels
        DB::statement("ALTER TABLE payments MODIFY payment_method ENUM('mpesa', 'airtel', 'tigo', 'card', 'halopesa') DEFAULT 'mpesa'");

        // Expand gateway_settings enum to include clickpesa
        DB::statement("ALTER TABLE payment_gateway_settings MODIFY gateway ENUM('mpesa', 'airtel', 'tigo', 'card', 'clickpesa') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['clickpesa_order_id', 'clickpesa_payment_reference', 'clickpesa_channel']);
        });

        DB::statement("ALTER TABLE payments MODIFY payment_method ENUM('mpesa', 'airtel', 'tigo', 'card') DEFAULT 'mpesa'");
        DB::statement("ALTER TABLE payment_gateway_settings MODIFY gateway ENUM('mpesa', 'airtel', 'tigo', 'card') NOT NULL");
    }
};
