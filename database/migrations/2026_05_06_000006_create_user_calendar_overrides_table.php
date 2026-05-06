<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_calendar_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('schedule_date');
            $table->enum('meal_time', ['lunch', 'dinner']);
            $table->boolean('is_off')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'schedule_date', 'meal_time'], 'user_calendar_overrides_unique_slot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_calendar_overrides');
    }
};
