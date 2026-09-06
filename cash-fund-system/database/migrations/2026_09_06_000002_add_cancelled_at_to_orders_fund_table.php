<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// FIX #9: add cancelled_at timestamp so cancellation events have a full
// audit trail alongside cancelled_by (which already existed).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders_fund', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
        });
    }

    public function down(): void
    {
        Schema::table('orders_fund', function (Blueprint $table) {
            $table->dropColumn('cancelled_at');
        });
    }
};
