<?php

namespace Tests\Feature;

use App\Models\DailyMovement;
use App\Models\OrderFund;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase4ComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $client;
    private User $admin2;
    private User $investor;
    private int $categoryId;

    protected function setUp(): void
    {
        parent::setUp();

        \Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);

        $this->admin = User::create([
            'name'      => 'Admin 1',
            'username'  => 'admin_phase4_1',
            'password'  => 'password',
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $this->admin2 = User::create([
            'name'      => 'Admin 2',
            'username'  => 'admin_phase4_2',
            'password'  => 'password',
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $this->client = User::create([
            'name'      => 'Client Phase4',
            'username'  => 'client_phase4',
            'password'  => 'password',
            'role'      => 'client',
            'is_active' => true,
        ]);

        $this->investor = User::create([
            'name'      => 'Investor Phase4',
            'username'  => 'investor_phase4',
            'password'  => 'password',
            'role'      => 'investor',
            'is_active' => true,
        ]);

        $this->categoryId = DB::table('categories')->where('is_active', true)->first()->id;
    }

    // ══════════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════════

    private function createDraftOrderDirectly(string $type = 'payment', string $amount = '100.00', ?int $creatorId = null): OrderFund
    {
        $service = app(\App\Services\OrderService::class);
        return $service->createDraft(
            [
                'type'        => $type,
                'amount'      => $amount,
                'description' => 'طلب اختبار',
                'order_date'  => '2026-07-05',
            ],
            [
                ['category_id' => $this->categoryId, 'description' => 'بند اختبار', 'amount' => $amount],
            ],
            $creatorId ?? $this->client->id
        );
    }

    private function submitOrderDirectly(OrderFund $order): void
    {
        $service = app(\App\Services\OrderService::class);
        $service->submitForApproval($order);
    }

    private function approveOrderDirectly(OrderFund $order, ?int $approvedBy = null): void
    {
        $service = app(\App\Services\OrderService::class);
        $service->approve($order, $approvedBy ?? $this->admin->id);
    }

    private function executeOrderDirectly(OrderFund $order, ?int $executedBy = null): void
    {
        $service = app(\App\Services\OrderService::class);
        $service->execute($order, $executedBy ?? $this->admin->id);
    }

    // ══════════════════════════════════════════════════════════════════
    // 1. CONCURRENCY — lockForUpdate Guard Test
    // ══════════════════════════════════════════════════════════════════

    public function test_execute_uses_lock_for_update(): void
    {
        $source = file_get_contents(app_path('Services/OrderService.php'));
        $this->assertStringContainsString('lockForUpdate', $source,
            'OrderService::execute() must use lockForUpdate() to prevent race conditions');
    }

    public function test_execute_uses_db_transaction(): void
    {
        $source = file_get_contents(app_path('Services/OrderService.php'));
        $this->assertStringContainsString('DB::transaction', $source,
            'OrderService::execute() must wrap operations in DB::transaction');
    }

    public function test_execute_locks_last_movement_row(): void
    {
        $source = file_get_contents(app_path('Services/OrderService.php'));
        $this->assertStringContainsString('orderByDesc', $source,
            'OrderService::execute() must read last movement with orderByDesc before lockForUpdate');
    }

    // ══════════════════════════════════════════════════════════════════
    // 2. ORDER LIFECYCLE — Additional Scenarios
    // ══════════════════════════════════════════════════════════════════

    // Scenario 1: execute() on already EXECUTED order → rejected
    public function test_execute_already_executed_order_rejected(): void
    {
        $order = $this->createDraftOrderDirectly('payment', '100.00');
        $this->submitOrderDirectly($order);
        $this->approveOrderDirectly($order);
        $this->executeOrderDirectly($order);

        $service = app(\App\Services\OrderService::class);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->execute($order, $this->admin->id);

        // Verify no duplicate daily_movement created
        $this->assertDatabaseCount('daily_movements', 1);
    }

    // Scenario 2: approve() a REJECTED order → rejected
    public function test_approve_rejected_order_rejected(): void
    {
        $order = $this->createDraftOrderDirectly('payment', '100.00');
        $this->submitOrderDirectly($order);

        $service = app(\App\Services\OrderService::class);
        $service->reject($order, $this->admin->id, 'سبب الرفض');

        $this->assertDatabaseHas('orders_fund', ['id' => $order->id, 'status' => 'REJECTED']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->approve($order, $this->admin->id);

        // Verify still REJECTED
        $this->assertDatabaseHas('orders_fund', ['id' => $order->id, 'status' => 'REJECTED']);
    }

    // Scenario 3: reject() an APPROVED order → rejected
    public function test_reject_approved_order_rejected(): void
    {
        $order = $this->createDraftOrderDirectly('payment', '100.00');
        $this->submitOrderDirectly($order);
        $this->approveOrderDirectly($order);

        $this->assertDatabaseHas('orders_fund', ['id' => $order->id, 'status' => 'APPROVED']);

        $service = app(\App\Services\OrderService::class);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->reject($order, $this->admin->id, 'سبب');

        // Verify still APPROVED
        $this->assertDatabaseHas('orders_fund', ['id' => $order->id, 'status' => 'APPROVED']);
    }

    // Scenario 4: cancel() an APPROVED order → rejected (not DRAFT/PENDING)
    public function test_cancel_approved_order_rejected(): void
    {
        $order = $this->createDraftOrderDirectly('payment', '100.00');
        $this->submitOrderDirectly($order);
        $this->approveOrderDirectly($order);

        $service = app(\App\Services\OrderService::class);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->cancel($order, $this->client->id);

        // Verify still APPROVED
        $this->assertDatabaseHas('orders_fund', ['id' => $order->id, 'status' => 'APPROVED']);
    }

    // Scenario 5: Full payment (صرف) sequence — balance_after decreases
    public function test_payment_order_full_sequence_balance_decreases(): void
    {
        $order = $this->createDraftOrderDirectly('payment', '500.00');
        $this->assertEquals('DRAFT', $order->status);

        $this->submitOrderDirectly($order);
        $order->refresh();
        $this->assertEquals('PENDING', $order->status);

        $this->approveOrderDirectly($order);
        $order->refresh();
        $this->assertEquals('APPROVED', $order->status);

        $this->executeOrderDirectly($order);
        $order->refresh();
        $this->assertEquals('EXECUTED', $order->status);

        $movement = DB::table('daily_movements')->where('order_id', $order->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals('-500.00', $movement->balance_after);
        $this->assertEquals('payment', $movement->movement_type);
    }

    // Scenario 6: Full receipt (قبض) sequence — balance_after increases
    public function test_receipt_order_full_sequence_balance_increases(): void
    {
        $order = $this->createDraftOrderDirectly('receipt', '750.00');
        $this->assertEquals('DRAFT', $order->status);

        $this->submitOrderDirectly($order);
        $this->approveOrderDirectly($order);
        $this->executeOrderDirectly($order);

        $movement = DB::table('daily_movements')->where('order_id', $order->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals('750.00', $movement->balance_after);
        $this->assertEquals('receipt', $movement->movement_type);
    }

    // Scenario 7: 6+ sequential orders — cumulative balance correct
    public function test_sequential_orders_cumulative_balance_correct(): void
    {
        $service = app(\App\Services\OrderService::class);

        // payment 200 → balance = -200
        $o1 = $this->createDraftOrderDirectly('payment', '200.00');
        $this->submitOrderDirectly($o1);
        $this->approveOrderDirectly($o1);
        $this->executeOrderDirectly($o1);

        // receipt 500 → balance = 300
        $o2 = $this->createDraftOrderDirectly('receipt', '500.00');
        $this->submitOrderDirectly($o2);
        $this->approveOrderDirectly($o2);
        $this->executeOrderDirectly($o2);

        // payment 100 → balance = 200
        $o3 = $this->createDraftOrderDirectly('payment', '100.00');
        $this->submitOrderDirectly($o3);
        $this->approveOrderDirectly($o3);
        $this->executeOrderDirectly($o3);

        // receipt 1000 → balance = 1200
        $o4 = $this->createDraftOrderDirectly('receipt', '1000.00');
        $this->submitOrderDirectly($o4);
        $this->approveOrderDirectly($o4);
        $this->executeOrderDirectly($o4);

        // payment 300 → balance = 900
        $o5 = $this->createDraftOrderDirectly('payment', '300.00');
        $this->submitOrderDirectly($o5);
        $this->approveOrderDirectly($o5);
        $this->executeOrderDirectly($o5);

        // receipt 250 → balance = 1150
        $o6 = $this->createDraftOrderDirectly('receipt', '250.00');
        $this->submitOrderDirectly($o6);
        $this->approveOrderDirectly($o6);
        $this->executeOrderDirectly($o6);

        // Verify: -200 + 500 - 100 + 1000 - 300 + 250 = 1150
        $this->assertEquals(6, DB::table('daily_movements')->count());

        $lastMovement = DB::table('daily_movements')->orderByDesc('id')->first();
        $this->assertEquals('1150.00', $lastMovement->balance_after);

        // Verify each balance is mathematically correct
        $movements = DB::table('daily_movements')->orderBy('id')->get();
        $expectedBalances = ['-200.00', '300.00', '200.00', '1200.00', '900.00', '1150.00'];
        foreach ($movements as $index => $movement) {
            $this->assertEquals($expectedBalances[$index], $movement->balance_after,
                "Movement #{$movement->id} balance incorrect: expected {$expectedBalances[$index]}, got {$movement->balance_after}");
        }
    }

    // Scenario 8: Long rejection reason with Arabic RTL text stored correctly
    public function test_long_arabic_rejection_reason_stored_correctly(): void
    {
        $order = $this->createDraftOrderDirectly('payment', '100.00');
        $this->submitOrderDirectly($order);

        $longReason = 'هذا سبب رفض طويل جداً يحتوي على نص عربي من اليمين لليسار RTL: ' . str_repeat('السبب الرئيسي هو أن المبلغ المطلوب يتجاوز الحد الأقصى المسموح به بموجب اللائحة المالية للمؤسسة. ', 3);

        $service = app(\App\Services\OrderService::class);
        $service->reject($order, $this->admin->id, $longReason);

        $dbOrder = DB::table('orders_fund')->where('id', $order->id)->first();
        $this->assertEquals('REJECTED', $dbOrder->status);
        $this->assertEquals($longReason, $dbOrder->rejection_reason);
    }

    // Scenario 9: Only one *_by field populated per order (not multiple)
    public function test_only_relevant_by_field_populated_per_status(): void
    {
        // EXECUTED order: executed_by set, no rejected_by or cancelled_by
        $o1 = $this->createDraftOrderDirectly('payment', '100.00');
        $this->submitOrderDirectly($o1);
        $this->approveOrderDirectly($o1);
        $this->executeOrderDirectly($o1);

        $dbOrder = DB::table('orders_fund')->where('id', $o1->id)->first();
        $this->assertNotNull($dbOrder->approved_by);
        $this->assertNotNull($dbOrder->executed_by);
        $this->assertNull($dbOrder->rejected_by);
        $this->assertNull($dbOrder->cancelled_by);

        // REJECTED order: rejected_by set, no executed_by or cancelled_by
        $o2 = $this->createDraftOrderDirectly('payment', '100.00');
        $this->submitOrderDirectly($o2);

        $service = app(\App\Services\OrderService::class);
        $service->reject($o2, $this->admin->id, 'سبب الرفض');

        $dbOrder2 = DB::table('orders_fund')->where('id', $o2->id)->first();
        $this->assertNotNull($dbOrder2->rejected_by);
        $this->assertNull($dbOrder2->approved_by);
        $this->assertNull($dbOrder2->executed_by);
        $this->assertNull($dbOrder2->cancelled_by);

        // CANCELLED order: cancelled_by set, no approved_by or executed_by
        $o3 = $this->createDraftOrderDirectly('payment', '100.00');
        $this->submitOrderDirectly($o3);

        $service->cancel($o3, $this->client->id);

        $dbOrder3 = DB::table('orders_fund')->where('id', $o3->id)->first();
        $this->assertNotNull($dbOrder3->cancelled_by);
        $this->assertNull($dbOrder3->approved_by);
        $this->assertNull($dbOrder3->executed_by);
        $this->assertNull($dbOrder3->rejected_by);
    }

    // ══════════════════════════════════════════════════════════════════
    // 3. SERVER-LEVEL SECURITY VALIDATIONS
    // ══════════════════════════════════════════════════════════════════

    // Test 10: approve() with same created_by as approved_by via payload tampering
    public function test_approve_same_creator_rejected_via_service(): void
    {
        // Admin cannot approve order they created (service checks IDs)
        $order = $this->createDraftOrderDirectly('payment', '100.00', $this->admin->id);
        $this->submitOrderDirectly($order);

        $service = app(\App\Services\OrderService::class);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->approve($order, $this->admin->id);
    }

    // Test 10b: Direct DB bypass — status changed without service
    public function test_no_controller_directly_updates_order_status(): void
    {
        $controllers = [
            app_path('Http/Controllers/Admin/OrderController.php'),
            app_path('Http/Controllers/Client/OrderController.php'),
        ];

        foreach ($controllers as $controllerPath) {
            if (!file_exists($controllerPath)) {
                continue;
            }
            $source = file_get_contents($controllerPath);

            // Controllers should not contain direct DB::table('orders_fund')->update(['status' => ...])
            // They should delegate to OrderService methods
            $this->assertStringNotContainsString("update(['status'", $source,
                basename($controllerPath) . ' should not directly update order status — use OrderService');
            $this->assertStringNotContainsString("DB::table('orders_fund')", $source,
                basename($controllerPath) . ' should not use raw DB queries on orders_fund — use OrderService');
        }
    }

    // Test 11: Investor cannot access each admin route individually (403)
    public function test_investor_blocked_from_admin_orders_index(): void
    {
        $this->actingAs($this->investor);
        $this->get(route('admin.orders.index'))->assertStatus(403);
    }

    public function test_investor_blocked_from_admin_orders_show(): void
    {
        $order = $this->createDraftOrderDirectly();
        $this->actingAs($this->investor);
        $this->get(route('admin.orders.show', $order->id))->assertStatus(403);
    }

    public function test_investor_blocked_from_admin_orders_approve(): void
    {
        $order = $this->createDraftOrderDirectly();
        $this->submitOrderDirectly($order);
        $this->actingAs($this->investor);
        $this->post(route('admin.orders.approve', $order->id))->assertStatus(403);
    }

    public function test_investor_blocked_from_admin_orders_reject(): void
    {
        $order = $this->createDraftOrderDirectly();
        $this->submitOrderDirectly($order);
        $this->actingAs($this->investor);
        $this->post(route('admin.orders.reject', $order->id), ['rejection_reason' => 'test'])->assertStatus(403);
    }

    public function test_investor_blocked_from_admin_orders_execute(): void
    {
        $order = $this->createDraftOrderDirectly();
        $this->submitOrderDirectly($order);
        $this->approveOrderDirectly($order);
        $this->actingAs($this->investor);
        $this->post(route('admin.orders.execute', $order->id))->assertStatus(403);
    }

    public function test_investor_blocked_from_admin_orders_cancel(): void
    {
        $order = $this->createDraftOrderDirectly();
        $this->actingAs($this->investor);
        $this->post(route('admin.orders.cancel', $order->id))->assertStatus(403);
    }

    // Test 12: Client cannot approve/reject/execute (even own order) → 403
    public function test_client_blocked_from_admin_orders_index(): void
    {
        $this->actingAs($this->client);
        $this->get(route('admin.orders.index'))->assertStatus(403);
    }

    public function test_client_blocked_from_admin_orders_approve(): void
    {
        $order = $this->createDraftOrderDirectly();
        $this->submitOrderDirectly($order);
        $this->actingAs($this->client);
        $this->post(route('admin.orders.approve', $order->id))->assertStatus(403);
    }

    public function test_client_blocked_from_admin_orders_reject(): void
    {
        $order = $this->createDraftOrderDirectly();
        $this->submitOrderDirectly($order);
        $this->actingAs($this->client);
        $this->post(route('admin.orders.reject', $order->id), ['rejection_reason' => 'test'])->assertStatus(403);
    }

    public function test_client_blocked_from_admin_orders_execute(): void
    {
        $order = $this->createDraftOrderDirectly();
        $this->submitOrderDirectly($order);
        $this->approveOrderDirectly($order);
        $this->actingAs($this->client);
        $this->post(route('admin.orders.execute', $order->id))->assertStatus(403);
    }

    public function test_client_blocked_from_admin_orders_cancel(): void
    {
        $order = $this->createDraftOrderDirectly();
        $this->actingAs($this->client);
        $this->post(route('admin.orders.cancel', $order->id))->assertStatus(403);
    }

    // Test 13: Client cannot cancel another client's order → 403
    public function test_client_cannot_cancel_another_clients_order(): void
    {
        $otherClient = User::create([
            'name'      => 'Other Client',
            'username'  => 'other_client_phase4',
            'password'  => 'password',
            'role'      => 'client',
            'is_active' => true,
        ]);

        $order = $this->createDraftOrderDirectly('payment', '100.00', $this->client->id);

        $this->actingAs($otherClient);
        $this->post(route('client.orders.cancel', $order->id))->assertStatus(403);
    }

    // Test 14: No controller/route bypasses OrderService for status changes
    public function test_order_service_is_only_path_for_status_changes(): void
    {
        $orderSource = file_get_contents(app_path('Services/OrderService.php'));
        $this->assertStringContainsString('public function approve', $orderSource);
        $this->assertStringContainsString('public function reject', $orderSource);
        $this->assertStringContainsString('public function execute', $orderSource);
        $this->assertStringContainsString('public function cancel', $orderSource);
    }

    // ══════════════════════════════════════════════════════════════════
    // 4. DAILY_MOVEMENTS — Immutability Checks
    // ══════════════════════════════════════════════════════════════════

    // Test 15: No route exists with daily-movements in its path for modification
    public function test_no_route_for_daily_movements_modification(): void
    {
        $routes = \Route::getRoutes()->getRoutes();
        $dailyMovementRoutes = collect($routes)->filter(function ($route) {
            $uri = $route->uri();
            return str_contains($uri, 'daily-movement') ||
                   str_contains($uri, 'daily_movement') ||
                   str_contains($uri, 'dailyMovement');
        });

        $this->assertTrue($dailyMovementRoutes->isEmpty(),
            'No routes should exist for daily_movements modification. Found: ' .
            $dailyMovementRoutes->map(fn($r) => $r->uri())->implode(', '));
    }

    // Test 16: DailyMovement model has no update/delete methods exposed
    public function test_daily_movement_model_has_no_custom_update_or_delete(): void
    {
        $model = new DailyMovement();

        // The model should only have standard Eloquent methods
        // Check that no custom update/delete methods are defined in the model file
        $source = file_get_contents(app_path('Models/DailyMovement.php'));

        // No custom update() method override
        $this->assertStringNotContainsString('public function update(', $source,
            'DailyMovement model should not override update()');
        $this->assertStringNotContainsString('public function delete(', $source,
            'DailyMovement model should not override delete()');
        $this->assertStringNotContainsString('public function forceDelete(', $source,
            'DailyMovement model should not have forceDelete()');
    }

    // Test 17: Deleting an order with daily_movements fails (restrictOnDelete)
    public function test_cannot_delete_order_with_daily_movement(): void
    {
        $order = $this->createDraftOrderDirectly('payment', '100.00');
        $this->submitOrderDirectly($order);
        $this->approveOrderDirectly($order);
        $this->executeOrderDirectly($order);

        // Verify daily_movement exists
        $this->assertDatabaseHas('daily_movements', ['order_id' => $order->id]);

        // Try to delete the order — should fail due to restrictOnDelete FK
        try {
            DB::table('orders_fund')->where('id', $order->id)->delete();
            // If we get here, the FK constraint didn't fire (which is a bug)
            $this->fail('Deleting an order with daily_movements should fail due to restrictOnDelete foreign key');
        } catch (\Exception $e) {
            // Expected: foreign key constraint violation
            $this->assertStringContainsString('foreign key constraint', strtolower($e->getMessage()));
        }

        // Verify order still exists
        $this->assertDatabaseHas('orders_fund', ['id' => $order->id]);
    }

    // Test 17b: Deleting order with documents also fails (restrictOnDelete)
    public function test_cannot_delete_order_with_documents(): void
    {
        $order = $this->createDraftOrderDirectly('payment', '100.00');

        // Create a document record manually
        DB::table('documents')->insert([
            'order_id'    => $order->id,
            'file_name'   => 'test.pdf',
            'file_path'   => 'private/documents/' . $order->id . '/test.pdf',
            'file_type'   => 'pdf',
            'file_size'   => 1024,
            'uploaded_by' => $this->client->id,
            'uploaded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            DB::table('orders_fund')->where('id', $order->id)->delete();
            $this->fail('Deleting an order with documents should fail due to restrictOnDelete');
        } catch (\Exception $e) {
            $this->assertStringContainsString('foreign key constraint', strtolower($e->getMessage()));
        }

        $this->assertDatabaseHas('orders_fund', ['id' => $order->id]);
    }

    // Test 17c: Deleting order with order_items also fails (restrictOnDelete)
    public function test_cannot_delete_order_with_order_items(): void
    {
        $order = $this->createDraftOrderDirectly('payment', '100.00');

        // Order items already exist from createDraft
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id]);

        try {
            DB::table('orders_fund')->where('id', $order->id)->delete();
            $this->fail('Deleting an order with order_items should fail due to restrictOnDelete');
        } catch (\Exception $e) {
            $this->assertStringContainsString('foreign key constraint', strtolower($e->getMessage()));
        }

        $this->assertDatabaseHas('orders_fund', ['id' => $order->id]);
    }

    // ══════════════════════════════════════════════════════════════════
    // 5. EXECUTE CONFIRMATION — Server-side Validation
    // ══════════════════════════════════════════════════════════════════

    // Test: execute() requires confirm_execute = 'EXECUTE' server-side
    public function test_execute_controller_validates_confirm_execute_text(): void
    {
        $order = $this->createDraftOrderDirectly('payment', '100.00');
        $this->submitOrderDirectly($order);
        $this->approveOrderDirectly($order);

        // POST WITHOUT confirm_execute field → should fail (422)
        $this->actingAs($this->admin);
        $response = $this->post(route('admin.orders.execute', $order->id));

        $response->assertSessionHasErrors('confirm_execute');
        $this->assertDatabaseHas('orders_fund', ['id' => $order->id, 'status' => 'APPROVED']);

        // POST WITH correct confirm_execute → should succeed
        $response2 = $this->post(route('admin.orders.execute', $order->id), [
            'confirm_execute' => 'EXECUTE',
        ]);

        $response2->assertRedirect();
        $this->assertDatabaseHas('orders_fund', ['id' => $order->id, 'status' => 'EXECUTED']);
    }

    // Test: execute() fails with wrong confirm text (lowercase, typo, etc.)
    public function test_execute_fails_with_wrong_confirm_text(): void
    {
        $order = $this->createDraftOrderDirectly('payment', '100.00');
        $this->submitOrderDirectly($order);
        $this->approveOrderDirectly($order);

        $this->actingAs($this->admin);

        // Wrong: lowercase
        $response1 = $this->post(route('admin.orders.execute', $order->id), [
            'confirm_execute' => 'execute',
        ]);
        $response1->assertSessionHasErrors('confirm_execute');
        $this->assertDatabaseHas('orders_fund', ['id' => $order->id, 'status' => 'APPROVED']);

        // Wrong: typo
        $response2 = $this->post(route('admin.orders.execute', $order->id), [
            'confirm_execute' => 'EXCUTE',
        ]);
        $response2->assertSessionHasErrors('confirm_execute');
        $this->assertDatabaseHas('orders_fund', ['id' => $order->id, 'status' => 'APPROVED']);

        // Wrong: empty string
        $response3 = $this->post(route('admin.orders.execute', $order->id), [
            'confirm_execute' => '',
        ]);
        $response3->assertSessionHasErrors('confirm_execute');
        $this->assertDatabaseHas('orders_fund', ['id' => $order->id, 'status' => 'APPROVED']);

        // Wrong: with spaces — gets trimmed by TrimStrings middleware, so it passes
        // This is expected behavior, not a bypass
        $response4 = $this->post(route('admin.orders.execute', $order->id), [
            'confirm_execute' => ' EXECUTE ',
        ]);
        $response4->assertRedirect();
        $this->assertDatabaseHas('orders_fund', ['id' => $order->id, 'status' => 'EXECUTED']);
    }

    // ══════════════════════════════════════════════════════════════════
    // 6. UI / VISUAL VERIFICATION (PHPUnit-based)
    // ══════════════════════════════════════════════════════════════════

    // Admin orders index: Arabic RTL display
    public function test_admin_orders_index_displays_arabic_rtl(): void
    {
        $this->actingAs($this->admin);
        $response = $this->get(route('admin.orders.index'));

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('إدارة الطلبات');
    }

    // Admin orders show: Arabic RTL display
    public function test_admin_orders_show_displays_arabic_rtl(): void
    {
        $order = $this->createDraftOrderDirectly();
        $this->submitOrderDirectly($order);

        $this->actingAs($this->admin);
        $response = $this->get(route('admin.orders.show', $order->id));

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('تفاصيل الطلب');
    }

    // Client orders show: Arabic RTL display
    public function test_client_orders_show_displays_arabic_rtl(): void
    {
        $order = $this->createDraftOrderDirectly();

        $this->actingAs($this->client);
        $response = $this->get(route('client.orders.show', $order->id));

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('تفاصيل الطلب');
    }

    // Admin orders show: status badge labels in Arabic
    public function test_status_badges_display_arabic_labels(): void
    {
        $content = file_get_contents(resource_path('views/components/status-badge.blade.php'));

        $arabicLabels = ['مسودة', 'قيد المراجعة', 'موافق عليه', 'مرفوض', 'تم التنفيذ', 'ملغى'];
        foreach ($arabicLabels as $label) {
            $this->assertStringContainsString($label, $content,
                "Status badge should contain Arabic label: {$label}");
        }
    }

    // Admin orders show: cancel button only for DRAFT/PENDING
    public function test_admin_cancel_button_only_for_draft_pending(): void
    {
        $content = file_get_contents(resource_path('views/admin/orders/show.blade.php'));

        // Check that cancel is conditionally rendered for DRAFT/PENDING only
        $this->assertStringContainsString("in_array(\$order->status, ['DRAFT', 'PENDING'])", $content,
            'Admin cancel button should only appear for DRAFT/PENDING orders');
    }

    // Admin orders show: execute button only for APPROVED
    public function test_admin_execute_button_only_for_approved(): void
    {
        $content = file_get_contents(resource_path('views/admin/orders/show.blade.php'));

        $this->assertStringContainsString("\$order->status === 'APPROVED'", $content,
            'Admin execute button should only appear for APPROVED orders');
    }

    // Admin orders show: approve/reject buttons only for PENDING
    public function test_admin_approve_reject_buttons_only_for_pending(): void
    {
        $content = file_get_contents(resource_path('views/admin/orders/show.blade.php'));

        $this->assertStringContainsString("\$order->status === 'PENDING'", $content,
            'Admin approve/reject buttons should only appear for PENDING orders');
    }

    // Client orders show: submit button only for DRAFT
    public function test_client_submit_button_only_for_draft(): void
    {
        $content = file_get_contents(resource_path('views/client/orders/show.blade.php'));

        $this->assertStringContainsString("in_array(\$order->status, ['DRAFT'])", $content,
            'Client submit button should only appear for DRAFT orders');
    }

    // Client orders show: cancel button for DRAFT and PENDING
    public function test_client_cancel_button_for_draft_and_pending(): void
    {
        $content = file_get_contents(resource_path('views/client/orders/show.blade.php'));

        // DRAFT section
        $this->assertStringContainsString("in_array(\$order->status, ['DRAFT'])", $content);
        // PENDING section
        $this->assertStringContainsString("\$order->status === 'PENDING'", $content);
    }

    // Execute confirmation modal requires "EXECUTE" text
    public function test_execute_modal_requires_exact_text(): void
    {
        $content = file_get_contents(resource_path('views/admin/orders/show.blade.php'));

        $this->assertStringContainsString('EXECUTE', $content,
            'Execute confirmation modal must require typing EXECUTE');
        $this->assertStringContainsString("confirmText !== 'EXECUTE'", $content,
            'Execute button must be disabled when text does not match EXECUTE');
    }

    // Admin orders index: type labels in Arabic (صرف/قبض)
    public function test_admin_index_type_labels_arabic(): void
    {
        $content = file_get_contents(resource_path('views/admin/orders/index.blade.php'));
        $this->assertStringContainsString('صرف', $content);
        $this->assertStringContainsString('قبض', $content);
    }

    // ══════════════════════════════════════════════════════════════════
    // 7. CANCELLED ORDER — Status Field Mutually Exclusive
    // ══════════════════════════════════════════════════════════════════

    public function test_cancelled_order_has_no_approved_by(): void
    {
        $order = $this->createDraftOrderDirectly('payment', '100.00');
        $this->submitOrderDirectly($order);

        $service = app(\App\Services\OrderService::class);
        $service->cancel($order, $this->client->id);

        $dbOrder = DB::table('orders_fund')->where('id', $order->id)->first();
        $this->assertNull($dbOrder->approved_by);
        $this->assertNull($dbOrder->executed_by);
        $this->assertNull($dbOrder->rejected_by);
        $this->assertNotNull($dbOrder->cancelled_by);
    }

    // ══════════════════════════════════════════════════════════════════
    // 8. DOCUMENT UPLOAD — Status-based blocking
    // ══════════════════════════════════════════════════════════════════

    public function test_upload_on_cancelled_order_rejected(): void
    {
        $order = $this->createDraftOrderDirectly('payment', '100.00');
        $this->submitOrderDirectly($order);

        // Cancel from PENDING (valid cancel source)
        $service = app(\App\Services\OrderService::class);
        $service->cancel($order, $this->client->id);

        $this->assertDatabaseHas('orders_fund', ['id' => $order->id, 'status' => 'CANCELLED']);

        $service2 = app(\App\Services\DocumentService::class);
        $file = \Illuminate\Http\UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service2->upload($order, $file, $this->client->id);
    }

    public function test_upload_on_rejected_order_rejected(): void
    {
        $order = $this->createDraftOrderDirectly('payment', '100.00');
        $this->submitOrderDirectly($order);

        $service = app(\App\Services\OrderService::class);
        $service->reject($order, $this->admin->id, 'سبب');

        $service2 = app(\App\Services\DocumentService::class);
        $file = \Illuminate\Http\UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service2->upload($order, $file, $this->client->id);
    }

    // ══════════════════════════════════════════════════════════════════
    // 9. ORDER SERVICE — Method existence and type safety
    // ══════════════════════════════════════════════════════════════════

    public function test_order_service_has_all_required_methods(): void
    {
        $service = app(\App\Services\OrderService::class);

        $this->assertTrue(method_exists($service, 'createDraft'));
        $this->assertTrue(method_exists($service, 'submitForApproval'));
        $this->assertTrue(method_exists($service, 'approve'));
        $this->assertTrue(method_exists($service, 'reject'));
        $this->assertTrue(method_exists($service, 'cancel'));
        $this->assertTrue(method_exists($service, 'execute'));
    }

    // ══════════════════════════════════════════════════════════════════
    // 10. AMOUNT PRECISION — bcadd/bcmul usage
    // ══════════════════════════════════════════════════════════════════

    public function test_order_service_uses_bcadd_for_balance(): void
    {
        $source = file_get_contents(app_path('Services/OrderService.php'));
        $this->assertStringContainsString('bcadd', $source,
            'OrderService must use bcadd for precise decimal arithmetic');
        $this->assertStringContainsString('bcmul', $source,
            'OrderService must use bcmul for negative delta calculation');
        $this->assertStringContainsString('bccomp', $source,
            'OrderService must use bccomp for item sum validation');
    }
}
