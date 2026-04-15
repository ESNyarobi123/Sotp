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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['time', 'data', 'unlimited']);
            $table->unsignedInteger('value')->nullable()->comment('Minutes for time, MB for data, null for unlimited');
            $table->unsignedInteger('duration_minutes')->nullable()->comment('Duration in minutes for unlimited plans');
            $table->decimal('price', 12, 2)->comment('Price in TZS');
            $table->unsignedInteger('validity_days')->default(1)->comment('How many days the plan access lasts');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
