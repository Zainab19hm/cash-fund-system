<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders_fund', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();
            $table->enum('type', ['payment', 'receipt']);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->enum('status', ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED', 'EXECUTED', 'CANCELLED'])->default('DRAFT');
            $table->date('order_date');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('users');
            $table->timestamp('executed_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders_fund');
    }
};
