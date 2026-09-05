<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // doctrine/dbal is not installed, so we use raw ALTER TABLE statements.
        DB::statement('ALTER TABLE users MODIFY national_id VARCHAR(20) NULL');
        DB::statement('ALTER TABLE users MODIFY employee_number VARCHAR(20) NULL');
    }

    public function down(): void
    {
        // Restore NOT NULL (existing rows will need values — set empty string as default).
        DB::statement("ALTER TABLE users MODIFY national_id VARCHAR(20) NOT NULL DEFAULT ''");
        DB::statement("ALTER TABLE users MODIFY employee_number VARCHAR(20) NOT NULL DEFAULT ''");
    }
};
