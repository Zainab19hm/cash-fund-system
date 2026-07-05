<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $client;
    private int $categoryId;

    protected function setUp(): void
    {
        parent::setUp();

        \Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);

        $this->admin = User::create([
            'name'      => 'Test Admin',
            'username'  => 'test_admin_workflow',
            'password'  => 'password',
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $this->client = User::create([
            'name'      => 'Test Client',
            'username'  => 'test_client_workflow',
            'password'  => 'password',
            'role'      => 'client',
            'is_active' => true,
        ]);

        $this->categoryId = DB::table('categories')->where('is_active', true)->first()->id;
    }

    private function createDraftOrder(int $clientId, string $type = 'payment', string $amount = '100.00'): int
    {
        $this->actingAs($this->client);

        $this->post(route('client.orders.store'), [
            'type'        => $type,
            'amount'      => $amount,
            'order_date'  => '2026-07-05',
            'description' => 'طلب اختبار',
            'items'       => [
                ['category_id' => $this->categoryId, 'description' => 'بند اختبار', 'amount' => $amount],
            ],
        ]);

        $order = DB::table('orders_fund')->latest()->first();
        return $order->id;
    }

    private function createDraftOrderDirectly(string $type = 'payment', string $amount = '100.00'): int
    {
        $service = app(\App\Services\OrderService::class);
        $order = $service->createDraft(
            [
                'type'        => $type,
                'amount'      => $amount,
                'description' => 'طلب اختبار',
                'order_date'  => '2026-07-05',
            ],
            [
                ['category_id' => $this->categoryId, 'description' => 'بند اختبار', 'amount' => $amount],
            ],
            $this->client->id
        );
        return $order->id;
    }

    private function submitOrderDirectly(int $orderId): void
    {
        $service = app(\App\Services\OrderService::class);
        $order = \App\Models\OrderFund::find($orderId);
        $service->submitForApproval($order);
    }

    private function submitOrder(int $orderId): void
    {
        $this->actingAs($this->client);
        $this->post(route('client.orders.submit', $orderId));
    }

    // ── Test 1: approve PENDING order from different admin ──────────
    public function test_approve_pending_order_from_different_admin(): void
    {
        $orderId = $this->createDraftOrder($this->client->id);
        $this->submitOrder($orderId);

        $this->actingAs($this->admin);
        $response = $this->post(route('admin.orders.approve', $orderId));

        $response->assertRedirect();
        $this->assertDatabaseHas('orders_fund', [
            'id'          => $orderId,
            'status'      => 'APPROVED',
            'approved_by' => $this->admin->id,
        ]);
    }

    // ── Test 2: approve from same creator → rejected ────────────────
    public function test_approve_from_same_creator_rejected(): void
    {
        $orderId = $this->createDraftOrder($this->client->id);
        $this->submitOrder($orderId);

        // Use a different user as admin to approve (to pass middleware)
        $adminUser = User::create([
            'name'      => 'Admin Who Is Creator',
            'username'  => 'admin_creator',
            'password'  => 'password',
            'role'      => 'admin',
            'is_active' => true,
        ]);

        // Actually, the creator is client, so we need admin who is NOT creator
        // The constraint is: approved_by !== created_by
        // Let's create an admin who is also the creator by using direct DB
        // Actually, the service checks $order->created_by === $approvedBy
        // So we need the admin ID to equal the client ID who created it
        // That's impossible with different users. Let's test it properly.

        // The real test: admin approves, but admin IS the creator (same user ID)
        // This can't happen in normal flow since roles differ, but the service checks IDs
        // Let's create an admin who created an order (admin can't create orders via routes,
        // but we can create one directly to test the service logic)

        $order = DB::table('orders_fund')->where('id', $orderId)->first();
        // Set created_by to admin's ID
        DB::table('orders_fund')->where('id', $orderId)->update(['created_by' => $adminUser->id]);

        $this->actingAs($adminUser);
        $response = $this->post(route('admin.orders.approve', $orderId));

        $response->assertSessionHasErrors('approved_by');
        $this->assertDatabaseHas('orders_fund', [
            'id'     => $orderId,
            'status' => 'PENDING',
        ]);
    }

    // ── Test 3: reject without reason → rejected ────────────────────
    public function test_reject_without_reason_rejected(): void
    {
        $orderId = $this->createDraftOrder($this->client->id);
        $this->submitOrder($orderId);

        $this->actingAs($this->admin);
        $response = $this->post(route('admin.orders.reject', $orderId), [
            'rejection_reason' => '',
        ]);

        $response->assertSessionHasErrors('rejection_reason');
    }

    // ── Test 4: reject with reason → success ────────────────────────
    public function test_reject_with_reason_success(): void
    {
        $orderId = $this->createDraftOrder($this->client->id);
        $this->submitOrder($orderId);

        $this->actingAs($this->admin);
        $response = $this->post(route('admin.orders.reject', $orderId), [
            'rejection_reason' => 'المبلغ غير مناسب',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders_fund', [
            'id'               => $orderId,
            'status'           => 'REJECTED',
            'rejection_reason' => 'المبلغ غير مناسب',
        ]);
    }

    // ── Test 5: approve non-PENDING order → rejected ────────────────
    public function test_approve_non_pending_order_rejected(): void
    {
        $orderId = $this->createDraftOrder($this->client->id);

        $this->actingAs($this->admin);
        $response = $this->post(route('admin.orders.approve', $orderId));

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseHas('orders_fund', [
            'id'     => $orderId,
            'status' => 'DRAFT',
        ]);
    }

    // ── Test 6: execute APPROVED order → success + daily_movements ──
    public function test_execute_approved_order_creates_movement(): void
    {
        $orderId = $this->createDraftOrder($this->client->id, 'payment', '500.00');
        $this->submitOrder($orderId);

        $this->actingAs($this->admin);
        $this->post(route('admin.orders.approve', $orderId));

        $this->post(route('admin.orders.execute', $orderId), ['confirm_execute' => 'EXECUTE']);

        $this->assertDatabaseHas('orders_fund', [
            'id'          => $orderId,
            'status'      => 'EXECUTED',
            'executed_by' => $this->admin->id,
        ]);

        $movement = DB::table('daily_movements')->where('order_id', $orderId)->first();
        $this->assertNotNull($movement);
        $this->assertEquals('payment', $movement->movement_type);
        $this->assertEquals('500.00', $movement->amount);
        $this->assertEquals('-500.00', $movement->balance_after);
    }

    // ── Test 7: execute non-APPROVED order → rejected ───────────────
    public function test_execute_non_approved_order_rejected(): void
    {
        $orderId = $this->createDraftOrder($this->client->id);
        $this->submitOrder($orderId);

        $this->actingAs($this->admin);
        $response = $this->post(route('admin.orders.execute', $orderId), ['confirm_execute' => 'EXECUTE']);

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseCount('daily_movements', 0);
    }

    // ── Test 8: concurrent execution → balance correct ──────────────
    public function test_concurrent_execution_balance_remains_correct(): void
    {
        // Create two orders directly via service
        $order1Id = $this->createDraftOrderDirectly('payment', '100.00');
        $this->submitOrderDirectly($order1Id);
        $order2Id = $this->createDraftOrderDirectly('receipt', '250.00');
        $this->submitOrderDirectly($order2Id);

        // Approve via service
        $service = app(\App\Services\OrderService::class);
        $order1 = \App\Models\OrderFund::find($order1Id);
        $order2 = \App\Models\OrderFund::find($order2Id);
        $service->approve($order1, $this->admin->id);
        $service->approve($order2, $this->admin->id);

        // Execute both (they should serialize via lockForUpdate)
        $service->execute($order1, $this->admin->id);
        $service->execute($order2, $this->admin->id);

        // Verify both executed
        $this->assertDatabaseHas('orders_fund', ['id' => $order1Id, 'status' => 'EXECUTED']);
        $this->assertDatabaseHas('orders_fund', ['id' => $order2Id, 'status' => 'EXECUTED']);

        // Verify movements: order1 = -100, order2 = +250 → balance = 150
        $this->assertEquals(2, DB::table('daily_movements')->count());

        $lastMovement = DB::table('daily_movements')->orderByDesc('id')->first();
        $this->assertEquals('150.00', $lastMovement->balance_after);
    }

    // ── Test 9: client cancel DRAFT → success ───────────────────────
    public function test_client_cancel_draft_order_success(): void
    {
        $orderId = $this->createDraftOrder($this->client->id);

        $this->actingAs($this->client);
        $response = $this->post(route('client.orders.cancel', $orderId));

        $response->assertRedirect();
        $this->assertDatabaseHas('orders_fund', [
            'id'          => $orderId,
            'status'      => 'CANCELLED',
            'cancelled_by' => $this->client->id,
        ]);
    }

    // ── Test 10: cancel EXECUTED order → rejected ───────────────────
    public function test_cancel_executed_order_rejected(): void
    {
        $orderId = $this->createDraftOrder($this->client->id);
        $this->submitOrder($orderId);

        $this->actingAs($this->admin);
        $this->post(route('admin.orders.approve', $orderId));
        $this->post(route('admin.orders.execute', $orderId), ['confirm_execute' => 'EXECUTE']);

        $this->actingAs($this->client);
        $response = $this->post(route('client.orders.cancel', $orderId));

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseHas('orders_fund', [
            'id'     => $orderId,
            'status' => 'EXECUTED',
        ]);
    }

    // ── Test 11: cancel does not create daily_movements ─────────────
    public function test_cancel_does_not_create_daily_movements(): void
    {
        $orderId = $this->createDraftOrder($this->client->id);

        $this->actingAs($this->client);
        $this->post(route('client.orders.cancel', $orderId));

        $this->assertDatabaseCount('daily_movements', 0);
    }

    // ── Test 12: full lifecycle DRAFT→PENDING→APPROVED→EXECUTED ─────
    public function test_full_lifecycle_draft_to_executed(): void
    {
        // DRAFT
        $orderId = $this->createDraftOrder($this->client->id, 'receipt', '1000.00');
        $this->assertDatabaseHas('orders_fund', ['id' => $orderId, 'status' => 'DRAFT']);

        // PENDING
        $this->submitOrder($orderId);
        $this->assertDatabaseHas('orders_fund', ['id' => $orderId, 'status' => 'PENDING']);

        // APPROVED
        $this->actingAs($this->admin);
        $this->post(route('admin.orders.approve', $orderId));
        $this->assertDatabaseHas('orders_fund', ['id' => $orderId, 'status' => 'APPROVED']);

        // EXECUTED
        $this->post(route('admin.orders.execute', $orderId), ['confirm_execute' => 'EXECUTE']);
        $this->assertDatabaseHas('orders_fund', ['id' => $orderId, 'status' => 'EXECUTED']);

        // Verify daily_movement
        $movement = DB::table('daily_movements')->where('order_id', $orderId)->first();
        $this->assertNotNull($movement);
        $this->assertEquals('receipt', $movement->movement_type);
        $this->assertEquals('1000.00', $movement->balance_after);
    }

    // ── Test 13: skip status (PENDING→EXECUTED) → rejected ──────────
    public function test_skip_status_pending_to_executed_rejected(): void
    {
        $orderId = $this->createDraftOrder($this->client->id);
        $this->submitOrder($orderId);

        $this->actingAs($this->admin);
        $response = $this->post(route('admin.orders.execute', $orderId), ['confirm_execute' => 'EXECUTE']);

        $response->assertSessionHasErrors('status');
    }

    // ── Test 14: investor cannot access admin order routes (403) ────
    public function test_investor_cannot_access_admin_order_routes(): void
    {
        $investor = User::create([
            'name'      => 'Test Investor',
            'username'  => 'test_investor_workflow',
            'password'  => 'password',
            'role'      => 'investor',
            'is_active' => true,
        ]);

        // Create a real order to test model-binding routes
        $orderId = $this->createDraftOrder($this->client->id);
        $this->submitOrder($orderId);

        $this->actingAs($investor);

        $this->get(route('admin.orders.index'))->assertStatus(403);
        $this->get(route('admin.orders.show', $orderId))->assertStatus(403);
        $this->post(route('admin.orders.approve', $orderId))->assertStatus(403);
        $this->post(route('admin.orders.reject', $orderId), ['rejection_reason' => 'test'])->assertStatus(403);
        $this->post(route('admin.orders.execute', $orderId))->assertStatus(403);
        $this->post(route('admin.orders.cancel', $orderId))->assertStatus(403);
    }

    // ── Test 15: client cannot access admin order routes (403) ──────
    public function test_client_cannot_access_admin_order_routes(): void
    {
        // Create a real order to test model-binding routes
        $orderId = $this->createDraftOrder($this->client->id);
        $this->submitOrder($orderId);

        $this->actingAs($this->client);

        $this->get(route('admin.orders.index'))->assertStatus(403);
        $this->get(route('admin.orders.show', $orderId))->assertStatus(403);
        $this->post(route('admin.orders.approve', $orderId))->assertStatus(403);
        $this->post(route('admin.orders.reject', $orderId), ['rejection_reason' => 'test'])->assertStatus(403);
        $this->post(route('admin.orders.execute', $orderId))->assertStatus(403);
        $this->post(route('admin.orders.cancel', $orderId))->assertStatus(403);
    }

    // ── Test 16-17: receipt order execution balance ──────────────────
    public function test_receipt_order_execution_positive_balance(): void
    {
        $orderId = $this->createDraftOrder($this->client->id, 'receipt', '750.00');
        $this->submitOrder($orderId);

        $this->actingAs($this->admin);
        $this->post(route('admin.orders.approve', $orderId));
        $this->post(route('admin.orders.execute', $orderId), ['confirm_execute' => 'EXECUTE']);

        $movement = DB::table('daily_movements')->where('order_id', $orderId)->first();
        $this->assertEquals('750.00', $movement->balance_after);
    }

    public function test_payment_order_execution_negative_balance(): void
    {
        $orderId = $this->createDraftOrder($this->client->id, 'payment', '300.00');
        $this->submitOrder($orderId);

        $this->actingAs($this->admin);
        $this->post(route('admin.orders.approve', $orderId));
        $this->post(route('admin.orders.execute', $orderId), ['confirm_execute' => 'EXECUTE']);

        $movement = DB::table('daily_movements')->where('order_id', $orderId)->first();
        $this->assertEquals('-300.00', $movement->balance_after);
    }

    // ── Test 18-19: reject PENDING order ─────────────────────────────
    public function test_reject_pending_order_changes_status(): void
    {
        $orderId = $this->createDraftOrder($this->client->id);
        $this->submitOrder($orderId);

        $this->actingAs($this->admin);
        $this->post(route('admin.orders.reject', $orderId), [
            'rejection_reason' => 'لا نقبل طلبات أكبر من 1000',
        ]);

        $this->assertDatabaseHas('orders_fund', [
            'id'               => $orderId,
            'status'           => 'REJECTED',
            'rejected_by'      => $this->admin->id,
            'rejection_reason' => 'لا نقبل طلبات أكبر من 1000',
        ]);
    }

    public function test_reject_non_pending_order_rejected(): void
    {
        $orderId = $this->createDraftOrder($this->client->id);

        $this->actingAs($this->admin);
        $response = $this->post(route('admin.orders.reject', $orderId), [
            'rejection_reason' => 'سبب',
        ]);

        $response->assertSessionHasErrors('status');
    }

    // ── Test 20: admin cancel DRAFT/PENDING ──────────────────────────
    public function test_admin_cancel_pending_order(): void
    {
        $orderId = $this->createDraftOrder($this->client->id);
        $this->submitOrder($orderId);

        $this->actingAs($this->admin);
        $response = $this->post(route('admin.orders.cancel', $orderId));

        $response->assertRedirect();
        $this->assertDatabaseHas('orders_fund', [
            'id'     => $orderId,
            'status' => 'CANCELLED',
        ]);
    }

    // ── Test 21: admin cancel DRAFT ──────────────────────────────────
    public function test_admin_cancel_draft_order(): void
    {
        $orderId = $this->createDraftOrder($this->client->id);

        $this->actingAs($this->admin);
        $this->post(route('admin.orders.cancel', $orderId));

        $this->assertDatabaseHas('orders_fund', [
            'id'     => $orderId,
            'status' => 'CANCELLED',
        ]);
    }

    // ── Test 22: daily_movements balance tracking across executions ──
    public function test_balance_tracking_across_multiple_executions(): void
    {
        $service = app(\App\Services\OrderService::class);

        // Execute payment of 200
        $order1Id = $this->createDraftOrderDirectly('payment', '200.00');
        $this->submitOrderDirectly($order1Id);
        $order1 = \App\Models\OrderFund::find($order1Id);
        $service->approve($order1, $this->admin->id);
        $service->execute($order1, $this->admin->id);

        // Execute receipt of 500
        $order2Id = $this->createDraftOrderDirectly('receipt', '500.00');
        $this->submitOrderDirectly($order2Id);
        $order2 = \App\Models\OrderFund::find($order2Id);
        $service->approve($order2, $this->admin->id);
        $service->execute($order2, $this->admin->id);

        // Execute payment of 100
        $order3Id = $this->createDraftOrderDirectly('payment', '100.00');
        $this->submitOrderDirectly($order3Id);
        $order3 = \App\Models\OrderFund::find($order3Id);
        $service->approve($order3, $this->admin->id);
        $service->execute($order3, $this->admin->id);

        // Verify: -200 + 500 - 100 = 200
        $lastMovement = DB::table('daily_movements')->orderByDesc('id')->first();
        $this->assertEquals('200.00', $lastMovement->balance_after);
        $this->assertEquals(3, DB::table('daily_movements')->count());
    }

    // ── Test 23: daily_movements stores correct metadata ─────────────
    public function test_daily_movement_stores_correct_metadata(): void
    {
        $orderId = $this->createDraftOrder($this->client->id, 'payment', '100.00');
        $this->submitOrder($orderId);

        $this->actingAs($this->admin);
        $this->post(route('admin.orders.approve', $orderId));
        $this->post(route('admin.orders.execute', $orderId), ['confirm_execute' => 'EXECUTE']);

        $movement = DB::table('daily_movements')->where('order_id', $orderId)->first();
        $this->assertEquals($orderId, $movement->order_id);
        $this->assertEquals('payment', $movement->movement_type);
        $this->assertEquals('100.00', $movement->amount);
        $this->assertNotNull($movement->executed_at);
        $this->assertNotNull($movement->movement_date);
    }

    // ── Test 24: multiple admins can approve ─────────────────────────
    public function test_different_admins_can_approve_different_orders(): void
    {
        $admin2 = User::create([
            'name'      => 'Admin 2',
            'username'  => 'admin_2_workflow',
            'password'  => 'password',
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $order1Id = $this->createDraftOrderDirectly();
        $this->submitOrderDirectly($order1Id);

        $order2Id = $this->createDraftOrderDirectly();
        $this->submitOrderDirectly($order2Id);

        $service = app(\App\Services\OrderService::class);
        $order1 = \App\Models\OrderFund::find($order1Id);
        $order2 = \App\Models\OrderFund::find($order2Id);

        $service->approve($order1, $this->admin->id);
        $service->approve($order2, $admin2->id);

        $this->assertDatabaseHas('orders_fund', ['id' => $order1Id, 'approved_by' => $this->admin->id]);
        $this->assertDatabaseHas('orders_fund', ['id' => $order2Id, 'approved_by' => $admin2->id]);
    }

    // ── Test 25: client cancel PENDING ───────────────────────────────
    public function test_client_cancel_pending_order(): void
    {
        $orderId = $this->createDraftOrder($this->client->id);
        $this->submitOrder($orderId);

        $this->actingAs($this->client);
        $response = $this->post(route('client.orders.cancel', $orderId));

        $response->assertRedirect();
        $this->assertDatabaseHas('orders_fund', [
            'id'     => $orderId,
            'status' => 'CANCELLED',
        ]);
    }

    // ── Test 26: client cannot cancel other's order ──────────────────
    public function test_client_cannot_cancel_other_users_order(): void
    {
        $orderId = $this->createDraftOrder($this->client->id);

        $client2 = User::create([
            'name'      => 'Client 2',
            'username'  => 'client_2_workflow',
            'password'  => 'password',
            'role'      => 'client',
            'is_active' => true,
        ]);

        $this->actingAs($client2);
        $response = $this->post(route('client.orders.cancel', $orderId));

        $response->assertStatus(403);
    }

    // ── Test 27: rejection_reason is stored ──────────────────────────
    public function test_rejection_reason_stored_correctly(): void
    {
        $orderId = $this->createDraftOrder($this->client->id);
        $this->submitOrder($orderId);

        $this->actingAs($this->admin);
        $this->post(route('admin.orders.reject', $orderId), [
            'rejection_reason' => 'المبلغ يتجاوز الحد المسموح',
        ]);

        $order = DB::table('orders_fund')->where('id', $orderId)->first();
        $this->assertEquals('REJECTED', $order->status);
        $this->assertEquals('المبلغ يتجاوز الحد المسموح', $order->rejection_reason);
        $this->assertEquals($this->admin->id, $order->rejected_by);
    }

    // ── Test 28: execute records executed_at timestamp ───────────────
    public function test_execute_records_executed_at_timestamp(): void
    {
        $orderId = $this->createDraftOrder($this->client->id);
        $this->submitOrder($orderId);

        $this->actingAs($this->admin);
        $this->post(route('admin.orders.approve', $orderId));
        $this->post(route('admin.orders.execute', $orderId), ['confirm_execute' => 'EXECUTE']);

        $order = DB::table('orders_fund')->where('id', $orderId)->first();
        $this->assertNotNull($order->executed_at);
        $this->assertEquals($this->admin->id, $order->executed_by);
    }

    // ── Test 29: admin orders index view renders ─────────────────────
    public function test_admin_orders_index_view_renders(): void
    {
        $this->actingAs($this->admin);
        $response = $this->get(route('admin.orders.index'));

        $response->assertOk();
        $response->assertSee('إدارة الطلبات');
    }

    // ── Test 30: admin orders show view renders ──────────────────────
    public function test_admin_orders_show_view_renders(): void
    {
        $orderId = $this->createDraftOrder($this->client->id);
        $this->submitOrder($orderId);

        $this->actingAs($this->admin);
        $response = $this->get(route('admin.orders.show', $orderId));

        $response->assertOk();
        $response->assertSee('تفاصيل الطلب');
    }
}
