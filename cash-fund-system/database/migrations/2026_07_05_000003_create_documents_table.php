<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders_fund')->restrictOnDelete();
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->string('file_type', 50);
            $table->integer('file_size');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
