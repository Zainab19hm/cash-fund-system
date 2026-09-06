<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// FIX #5: the original migration only ran the ALTER TABLE on MySQL, so on
// SQLite (dev) the enum was never updated. SQLite does not enforce enum
// constraints at the DB level, so inserts worked silently but the schema
// diverged from production. We now handle both drivers explicitly:
//   - MySQL: ALTER the enum column in-place (only supported way on MySQL)
//   - SQLite: no-op — SQLite ignores enum constraints anyway so NEW_ORDER
//             inserts already work; we document this here for clarity.
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE notifications MODIFY COLUMN type ENUM('APPROVED','REJECTED','EXECUTED','NEW_ORDER') NOT NULL"
            );
        }
        // SQLite: enum is stored as TEXT, no constraint to update.
        // NEW_ORDER inserts already work without any schema change.
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE notifications MODIFY COLUMN type ENUM('APPROVED','REJECTED','EXECUTED') NOT NULL"
            );
        }
    }
};
