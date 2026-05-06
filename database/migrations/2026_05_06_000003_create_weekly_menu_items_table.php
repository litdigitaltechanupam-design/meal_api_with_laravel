<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_menu_id')->constrained('weekly_menus')->cascadeOnDelete();
            $table->foreignId('meal_package_id')->constrained('meal_packages')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['weekly_menu_id', 'meal_package_id'], 'weekly_menu_items_unique_package');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_menu_items');
    }
};
