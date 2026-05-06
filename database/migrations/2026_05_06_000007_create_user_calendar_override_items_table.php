<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_calendar_override_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_calendar_override_id')->constrained('user_calendar_overrides')->cascadeOnDelete();
            $table->foreignId('meal_package_id')->constrained('meal_packages')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['user_calendar_override_id', 'meal_package_id'], 'user_calendar_override_items_unique_package');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_calendar_override_items');
    }
};
