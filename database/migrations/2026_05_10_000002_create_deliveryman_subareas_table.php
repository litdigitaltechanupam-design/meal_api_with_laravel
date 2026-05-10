<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveryman_subareas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deliveryman_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subarea_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['deliveryman_id', 'subarea_id'], 'deliveryman_subareas_unique_map');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveryman_subareas');
    }
};
