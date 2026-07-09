<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders_fund', function (Blueprint $table) {
            $table->string('payer_name', 255)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('orders_fund', function (Blueprint $table) {
            $table->dropColumn('payer_name');
        });
    }
};
