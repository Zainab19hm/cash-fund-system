<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('APPROVED', 'REJECTED', 'EXECUTED', 'NEW_ORDER') NOT NULL");
        }
    }

    public function down()
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('APPROVED', 'REJECTED', 'EXECUTED') NOT NULL");
        }
    }
};
