<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveryman_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deliveryman_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['deliveryman_id', 'area_id'], 'deliveryman_areas_unique_map');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveryman_areas');
    }
};
