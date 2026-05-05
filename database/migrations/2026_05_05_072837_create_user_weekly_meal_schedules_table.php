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
        Schema::create('user_weekly_meal_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('day_of_week', ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
            $table->enum('meal_time', ['lunch', 'dinner']);
            $table->foreignId('meal_package_id')->nullable()->constrained('meal_packages')->nullOnDelete();
            $table->boolean('is_off')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'day_of_week', 'meal_time'], 'user_weekly_schedule_unique_slot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_weekly_meal_schedules');
    }
};
