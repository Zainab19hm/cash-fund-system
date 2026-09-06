<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // MySQL: ALTER TABLE MODIFY to make columns nullable.
            // doctrine/dbal is not installed so we use raw SQL.
            DB::statement('ALTER TABLE users MODIFY national_id VARCHAR(20) NULL');
            DB::statement('ALTER TABLE users MODIFY employee_number VARCHAR(20) NULL');
        }
        // SQLite (used by tests): the original migration already defines these
        // as nullable() so no action needed here.
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY national_id VARCHAR(20) NOT NULL DEFAULT ''");
            DB::statement("ALTER TABLE users MODIFY employee_number VARCHAR(20) NOT NULL DEFAULT ''");
        }
    }
};
