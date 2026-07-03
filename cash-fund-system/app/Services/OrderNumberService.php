<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class OrderNumberService
{
    /**
     * Generate the next order number in ORD-YYYY-NNN format.
     *
     * Uses a separate counter table (order_number_sequences) with lockForUpdate()
     * to prevent race conditions. insertOrIgnore ensures the first call of each
     * year works even under concurrent requests — if two requests hit the empty
     * year simultaneously, one inserts and the other silently ignores the unique
     * conflict, then both proceed to lockForUpdate() on the now-existing row.
     *
     * @return string Order number like ORD-2026-001
     */
    public function generate(): string
    {
        return DB::transaction(function () {
            $year = (int) now()->year;

            // Ensure the sequence row exists — insertOrIgnore handles concurrent
            // first-insert race: if another request created it simultaneously,
            // this one silently ignores the unique key conflict.
            DB::table('order_number_sequences')->insertOrIgnore([
                'year'         => $year,
                'last_number'  => 0,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // Now the row is guaranteed to exist — lockForUpdate is effective.
            $sequence = DB::table('order_number_sequences')
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            $nextNumber = $sequence->last_number + 1;

            DB::table('order_number_sequences')
                ->where('year', $year)
                ->update(['last_number' => $nextNumber, 'updated_at' => now()]);

            return sprintf('ORD-%d-%03d', $year, $nextNumber);
        });
    }
}
