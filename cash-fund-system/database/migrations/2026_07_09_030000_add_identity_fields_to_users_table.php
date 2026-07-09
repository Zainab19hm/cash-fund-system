<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('national_id', 20)->unique()->after('name');
            $table->string('employee_number', 20)->unique()->after('national_id');
            $table->string('phone', 20)->nullable()->after('employee_number');
            $table->string('position', 100)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['national_id', 'employee_number', 'phone', 'position']);
        });
    }
};
