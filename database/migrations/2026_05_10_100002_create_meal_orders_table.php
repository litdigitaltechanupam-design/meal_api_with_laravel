<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('address_id')->constrained('user_addresses')->restrictOnDelete();
            $table->date('schedule_date');
            $table->enum('meal_time', ['lunch', 'dinner']);
            $table->enum('status', ['confirmed', 'prepared', 'out_for_delivery', 'delivered', 'failed', 'cancelled'])->default('confirmed');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('delivery_charge', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->foreignId('wallet_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->boolean('is_wallet_deducted')->default(false);
            $table->boolean('is_refunded')->default(false);
            $table->timestamp('deducted_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'schedule_date', 'meal_time'], 'meal_orders_unique_slot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_orders');
    }
};
