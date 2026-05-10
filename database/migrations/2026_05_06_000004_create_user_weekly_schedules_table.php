<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_weekly_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('address_id')->nullable()->constrained('user_addresses')->nullOnDelete();
            $table->enum('day_of_week', ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
            $table->enum('meal_time', ['lunch', 'dinner']);
            $table->boolean('is_off')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'day_of_week', 'meal_time'], 'user_weekly_schedules_unique_slot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_weekly_schedules');
    }
};
