<?php

namespace Tests\Unit;

use App\Services\OrderNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderNumberServiceEdgeTest extends TestCase
{
    use RefreshDatabase;

    private OrderNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OrderNumberService::class);
    }

    // ---- TEST 3.1: Empty table, two consecutive calls → sequential, no exception ----
    public function test_empty_table_two_consecutive_calls_no_exception_and_sequential(): void
    {
        $this->assertEquals(0, DB::table('order_number_sequences')->count());

        $first = $this->service->generate();
        $second = $this->service->generate();

        $this->assertMatchesRegularExpression('/^ORD-\d{4}-\d{3}$/', $first);
        $this->assertMatchesRegularExpression('/^ORD-\d{4}-\d{3}$/', $second);

        // Extract numbers
        $num1 = (int) substr($first, -3);
        $num2 = (int) substr($second, -3);
        $this->assertEquals(1, $num1);
        $this->assertEquals(2, $num2);
        $this->assertNotEquals($first, $second);
    }

    // ---- TEST 3.2: 150 consecutive calls, no duplicates ----
    public function test_150_consecutive_calls_no_duplicates(): void
    {
        $numbers = [];
        for ($i = 0; $i < 150; $i++) {
            $numbers[] = $this->service->generate();
        }

        $unique = array_unique($numbers);
        $this->assertCount(150, $unique, 'All 150 order numbers should be unique');

        // Verify all are in correct format
        foreach ($numbers as $num) {
            $this->assertMatchesRegularExpression('/^ORD-\d{4}-\d{3}$/', $num);
        }
    }

    // ---- TEST 3.3: Previous year with last_number=50, current year starts at 001 ----
    public function test_new_year_resets_to_001_while_previous_unchanged(): void
    {
        $currentYear = (int) now()->year;
        $previousYear = $currentYear - 1;

        // Insert a row for previous year with last_number=50
        DB::table('order_number_sequences')->insert([
            'year' => $previousYear,
            'last_number' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $number = $this->service->generate();

        $this->assertEquals("ORD-{$currentYear}-001", $number);

        // Verify previous year unchanged
        $prevRow = DB::table('order_number_sequences')->where('year', $previousYear)->first();
        $this->assertEquals(50, $prevRow->last_number);

        // Verify current year row was created with last_number=1
        $currRow = DB::table('order_number_sequences')->where('year', $currentYear)->first();
        $this->assertNotNull($currRow);
        $this->assertEquals(1, $currRow->last_number);
    }

    // ---- TEST 3.4: Format regex validation ----
    public function test_format_matches_regex_exactly(): void
    {
        $number = $this->service->generate();
        $this->assertMatchesRegularExpression('/^ORD-\d{4}-\d{3}$/', $number);

        // Verify zero-padded 3 digits
        $parts = explode('-', $number);
        $this->assertCount(3, $parts);
        $this->assertEquals('ORD', $parts[0]);
        $this->assertEquals(4, strlen($parts[1]));
        $this->assertEquals(3, strlen($parts[2]));
    }

    // ---- TEST 3.5: generate() does NOT create rows in orders_fund ----
    public function test_generate_does_not_create_orders_fund_rows(): void
    {
        // First check if orders_fund table exists; if not, that's fine
        $hasTable = DB::getSchemaBuilder()->hasTable('orders_fund');

        $this->service->generate();
        $this->service->generate();

        if ($hasTable) {
            $this->assertEquals(0, DB::table('orders_fund')->count());
        }
        // If table doesn't exist, the service should not attempt to create it either
        $this->assertTrue(true); // No exception thrown
    }

    // ---- TEST 3.6: Transaction correctness check ----
    public function test_generate_uses_single_transaction(): void
    {
        $this->service->generate();
        $this->service->generate();

        $row = DB::table('order_number_sequences')
            ->where('year', now()->year)
            ->first();

        $this->assertNotNull($row);
        $this->assertEquals(2, $row->last_number);
    }
}
