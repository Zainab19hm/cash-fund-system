<?php

namespace Tests\Unit;

use App\Services\OrderNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OrderNumberService::class);
    }

    public function test_first_number_of_year_is_001()
    {
        $number = $this->service->generate();

        $expected = 'ORD-' . now()->year . '-001';
        $this->assertEquals($expected, $number);
    }

    public function test_numbers_increase_sequentially()
    {
        $first = $this->service->generate();
        $second = $this->service->generate();
        $third = $this->service->generate();

        $year = now()->year;
        $this->assertEquals("ORD-{$year}-001", $first);
        $this->assertEquals("ORD-{$year}-002", $second);
        $this->assertEquals("ORD-{$year}-003", $third);
    }

    public function test_format_is_correct()
    {
        $number = $this->service->generate();

        $this->assertMatchesRegularExpression('/^ORD-\d{4}-\d{3}$/', $number);
    }

    public function test_resets_to_001_when_year_changes()
    {
        $first = $this->service->generate();
        $this->assertEquals('ORD-' . now()->year . '-001', $first);

        $second = $this->service->generate();
        $this->assertEquals('ORD-' . now()->year . '-002', $second);

        $this->travelTo(now()->addYear()->startOfYear());
        $this->service = app(OrderNumberService::class);

        $firstOfNewYear = $this->service->generate();
        $this->assertEquals('ORD-' . now()->year . '-001', $firstOfNewYear);
    }
}
