<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_weekly_schedule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_weekly_schedule_id')->constrained('user_weekly_schedules')->cascadeOnDelete();
            $table->foreignId('meal_package_id')->constrained('meal_packages')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['user_weekly_schedule_id', 'meal_package_id'], 'user_weekly_schedule_items_unique_package');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_weekly_schedule_items');
    }
};
