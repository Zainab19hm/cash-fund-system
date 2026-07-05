<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders_fund')->restrictOnDelete();
            $table->enum('movement_type', ['payment', 'receipt']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->date('movement_date');
            $table->timestamp('executed_at');
            $table->timestamps();

            $table->index('movement_date');
            $table->index('executed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_movements');
    }
};
