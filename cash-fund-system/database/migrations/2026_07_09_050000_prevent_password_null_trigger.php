<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('
            CREATE TRIGGER prevent_password_null_before_update
            BEFORE UPDATE ON `users`
            FOR EACH ROW
            BEGIN
                IF NEW.`password` IS NULL OR NEW.`password` = \'\' THEN
                    SIGNAL SQLSTATE \'45000\'
                    SET MESSAGE_TEXT = \'Password cannot be NULL or empty\';
                END IF;
            END
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS `prevent_password_null_before_update`');
    }
};