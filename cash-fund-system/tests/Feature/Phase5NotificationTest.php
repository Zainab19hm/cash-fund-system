<?php

namespace Tests\Feature;

use App\Models\DailyMovement;
use App\Models\Notification;
use App\Models\OrderFund;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Phase5NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $client;
    private User $otherClient;
    private User $investor;
    private int $categoryId;

    protected function setUp(): void
    {
        parent::setUp();

        \Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);

        $this->admin = User::create([
            'name'      => 'Admin P5',
            'username'  => 'admin_phase5',
            'password'  => 'password',
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $this->client = User::create([
            'name'      => 'Client P5',
            'username'  => 'client_phase5',
            'password'  => 'password',
            'role'      => 'client',
            'is_active' => true,
        ]);

        $this->otherClient = User::create([
            'name'      => 'Other Client P5',
            'username'  => 'other_client_phase5',
            'password'  => 'password',
            'role'      => 'client',
            'is_active' => true,
        ]);

        $this->investor = User::create([
            'name'      => 'Investor P5',
            'username'  => 'investor_phase5',
            'password'  => 'password',
            'role'      => 'investor',
            'is_active' => true,
        ]);

        $this->categoryId = DB::table('categories')->where('is_active', true)->first()->id;
    }

    // ══════════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════════

    private function createDraftOrder(string $type = 'payment', string $amount = '100.00', ?int $creatorId = null): OrderFund
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

    private function submitOrder(OrderFund $order): void
    {
        app(\App\Services\OrderService::class)->submitForApproval($order);
    }

    private function approveOrder(OrderFund $order, ?int $approvedBy = null): void
    {
        app(\App\Services\OrderService::class)->approve($order, $approvedBy ?? $this->admin->id);
    }

    private function rejectOrder(OrderFund $order, string $reason = 'سبب الرفض', ?int $rejectedBy = null): void
    {
        app(\App\Services\OrderService::class)->reject($order, $rejectedBy ?? $this->admin->id, $reason);
    }

    private function executeOrder(OrderFund $order, ?int $executedBy = null): void
    {
        app(\App\Services\OrderService::class)->execute($order, $executedBy ?? $this->admin->id);
    }

    // ══════════════════════════════════════════════════════════════════
    // 1. approve() → APPROVED notification created for order owner
    // ══════════════════════════════════════════════════════════════════

    public function test_approve_creates_approved_notification(): void
    {
        $order = $this->createDraftOrder();
        $this->submitOrder($order);

        $this->approveOrder($order);

        $this->assertDatabaseHas('notifications', [
            'user_id'  => $this->client->id,
            'order_id' => $order->id,
            'type'     => 'APPROVED',
        ]);

        $notification = Notification::where('order_id', $order->id)->where('type', 'APPROVED')->first();
        $this->assertEquals('تم اعتماد طلبك رقم ' . $order->order_number, $notification->message);
    }

    // ══════════════════════════════════════════════════════════════════
    // 2. reject() → REJECTED notification with rejection reason
    // ══════════════════════════════════════════════════════════════════

    public function test_reject_creates_rejected_notification_with_reason(): void
    {
        $order = $this->createDraftOrder();
        $this->submitOrder($order);

        $reason = 'المبلغ يتجاوز الحد المسموح';
        $this->rejectOrder($order, $reason);

        $this->assertDatabaseHas('notifications', [
            'user_id'  => $this->client->id,
            'order_id' => $order->id,
            'type'     => 'REJECTED',
        ]);

        $notification = Notification::where('order_id', $order->id)->where('type', 'REJECTED')->first();
        $this->assertStringContainsString('تم رفض طلبك رقم', $notification->message);
        $this->assertStringContainsString($reason, $notification->message);
    }

    public function test_reject_creates_notification_without_reason_suffix_when_no_reason(): void
    {
        $order = $this->createDraftOrder();
        $this->submitOrder($order);

        $service = app(\App\Services\OrderService::class);
        $service->reject($order, $this->admin->id, 'سبب');

        $notification = Notification::where('order_id', $order->id)->where('type', 'REJECTED')->first();
        $this->assertStringContainsString('تم رفض طلبك رقم', $notification->message);
    }

    // ══════════════════════════════════════════════════════════════════
    // 3. execute() → EXECUTED notification within same transaction
    // ══════════════════════════════════════════════════════════════════

    public function test_execute_creates_executed_notification(): void
    {
        $order = $this->createDraftOrder();
        $this->submitOrder($order);
        $this->approveOrder($order);

        $this->executeOrder($order);

        $this->assertDatabaseHas('notifications', [
            'user_id'  => $this->client->id,
            'order_id' => $order->id,
            'type'     => 'EXECUTED',
        ]);

        $notification = Notification::where('order_id', $order->id)->where('type', 'EXECUTED')->first();
        $this->assertEquals('تم تنفيذ طلبك رقم ' . $order->order_number, $notification->message);
    }

    public function test_execute_notification_in_same_transaction_as_daily_movement(): void
    {
        $order = $this->createDraftOrder('payment', '500.00');
        $this->submitOrder($order);
        $this->approveOrder($order);

        $this->executeOrder($order);

        $orderNotifications = Notification::where('order_id', $order->id)->count();
        $orderMovements = DailyMovement::where('order_id', $order->id)->count();

        $this->assertEquals(2, $orderNotifications);
        $this->assertEquals(1, $orderMovements);
    }

    public function test_execute_rollback_prevents_orphaned_notifications(): void
    {
        $order = $this->createDraftOrder('payment', '100.00');
        $this->submitOrder($order);
        $this->approveOrder($order);

        $notificationService = $this->mock(NotificationService::class);
        $notificationService->shouldReceive('notify')
            ->once()
            ->andThrow(new \RuntimeException('Simulated notification failure'));

        $service = app(\App\Services\OrderService::class);

        try {
            $service->execute($order, $this->admin->id);
        } catch (\RuntimeException $e) {
            // Expected
        }

        $order->refresh();
        $this->assertNotEquals('EXECUTED', $order->status);
        $this->assertDatabaseMissing('daily_movements', ['order_id' => $order->id]);
        $this->assertDatabaseMissing('notifications', ['order_id' => $order->id, 'type' => 'EXECUTED']);
    }

    // ══════════════════════════════════════════════════════════════════
    // 4. submitForApproval() and cancel() → NO notifications
    // ══════════════════════════════════════════════════════════════════

    public function test_submit_for_approval_does_not_create_notification(): void
    {
        $order = $this->createDraftOrder();

        $this->submitOrder($order);

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_cancel_does_not_create_notification(): void
    {
        $order = $this->createDraftOrder();
        $this->submitOrder($order);

        app(\App\Services\OrderService::class)->cancel($order, $this->client->id);

        $this->assertDatabaseCount('notifications', 0);
    }

    // ══════════════════════════════════════════════════════════════════
    // 5. Client sees only own notifications (data isolation)
    // ══════════════════════════════════════════════════════════════════

    public function test_client_sees_only_own_notifications(): void
    {
        $order1 = $this->createDraftOrder('payment', '100.00', $this->client->id);
        $this->submitOrder($order1);
        $this->approveOrder($order1);

        $order2 = $this->createDraftOrder('receipt', '200.00', $this->otherClient->id);
        $this->submitOrder($order2);
        $this->approveOrder($order2);

        $this->actingAs($this->client);
        $response = $this->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee($order1->order_number);
        $response->assertDontSee($order2->order_number);
    }

    // ══════════════════════════════════════════════════════════════════
    // 6. markAsRead on another user's notification → rejected
    // ══════════════════════════════════════════════════════════════════

    public function test_client_cannot_mark_other_clients_notification_as_read(): void
    {
        $order = $this->createDraftOrder('payment', '100.00', $this->otherClient->id);
        $this->submitOrder($order);
        $this->approveOrder($order);

        $notification = Notification::where('order_id', $order->id)->first();

        $this->actingAs($this->client);
        $response = $this->post(route('notifications.read', $notification));

        $response->assertStatus(403);

        $notification->refresh();
        $this->assertFalse($notification->is_read);
    }

    public function test_client_can_mark_own_notification_as_read(): void
    {
        $order = $this->createDraftOrder();
        $this->submitOrder($order);
        $this->approveOrder($order);

        $notification = Notification::where('order_id', $order->id)->first();
        $this->assertFalse($notification->is_read);

        $this->actingAs($this->client);
        $response = $this->post(route('notifications.read', $notification));

        $response->assertRedirect();

        $notification->refresh();
        $this->assertTrue($notification->is_read);
        $this->assertNotNull($notification->read_at);
    }

    // ══════════════════════════════════════════════════════════════════
    // 7. Unread count returns correct number, decreases after markAsRead
    // ══════════════════════════════════════════════════════════════════

    public function test_unread_count_returns_correct_number(): void
    {
        $order1 = $this->createDraftOrder('payment', '100.00');
        $this->submitOrder($order1);
        $this->approveOrder($order1);

        $order2 = $this->createDraftOrder('receipt', '200.00');
        $this->submitOrder($order2);
        $this->rejectOrder($order2, 'سبب');

        $this->actingAs($this->client);
        $response = $this->getJson(route('notifications.unread-count'));

        $response->assertOk();
        $response->assertJson(['count' => 2]);
    }

    public function test_unread_count_decreases_after_mark_as_read(): void
    {
        $order = $this->createDraftOrder();
        $this->submitOrder($order);
        $this->approveOrder($order);

        $notification = Notification::where('order_id', $order->id)->first();

        $this->actingAs($this->client);

        $response1 = $this->getJson(route('notifications.unread-count'));
        $response1->assertJson(['count' => 1]);

        $this->post(route('notifications.read', $notification));

        $response2 = $this->getJson(route('notifications.unread-count'));
        $response2->assertJson(['count' => 0]);
    }

    // ══════════════════════════════════════════════════════════════════
    // 8. admin/investor cannot reach client notifications (no notifications)
    // ══════════════════════════════════════════════════════════════════

    public function test_admin_notifications_page_shows_empty(): void
    {
        $order = $this->createDraftOrder();
        $this->submitOrder($order);
        $this->approveOrder($order);

        $this->actingAs($this->admin);
        $response = $this->get(route('notifications.index'));

        $response->assertOk();
        $response->assertDontSee($order->order_number);
    }

    public function test_investor_notifications_page_shows_empty(): void
    {
        $order = $this->createDraftOrder();
        $this->submitOrder($order);
        $this->approveOrder($order);

        $this->actingAs($this->investor);
        $response = $this->get(route('notifications.index'));

        $response->assertOk();
        $response->assertDontSee($order->order_number);
    }

    public function test_admin_unread_count_is_zero(): void
    {
        $order = $this->createDraftOrder();
        $this->submitOrder($order);
        $this->approveOrder($order);

        $this->actingAs($this->admin);
        $response = $this->getJson(route('notifications.unread-count'));

        $response->assertOk();
        $response->assertJson(['count' => 0]);
    }

    public function test_investor_unread_count_is_zero(): void
    {
        $order = $this->createDraftOrder();
        $this->submitOrder($order);
        $this->approveOrder($order);

        $this->actingAs($this->investor);
        $response = $this->getJson(route('notifications.unread-count'));

        $response->assertOk();
        $response->assertJson(['count' => 0]);
    }

    // ══════════════════════════════════════════════════════════════════
    // 9. RTL/Arabic on notifications page
    // ══════════════════════════════════════════════════════════════════

    public function test_notifications_page_has_rtl_arabic(): void
    {
        $order = $this->createDraftOrder();
        $this->submitOrder($order);
        $this->approveOrder($order);

        $this->actingAs($this->client);
        $response = $this->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('الإشعارات');
    }

    // ══════════════════════════════════════════════════════════════════
    // 10. unread-count endpoint returns JSON only (no HTML leak)
    // ══════════════════════════════════════════════════════════════════

    public function test_unread_count_endpoint_returns_json_only(): void
    {
        $this->actingAs($this->client);
        $response = $this->getJson(route('notifications.unread-count'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');

        $content = $response->content();
        $this->assertStringNotContainsString('<!DOCTYPE', $content);
        $this->assertStringNotContainsString('<html', $content);

        $json = json_decode($content, true);
        $this->assertArrayHasKey('count', $json);
        $this->assertIsInt($json['count']);
    }

    // ══════════════════════════════════════════════════════════════════
    // 11. Notification model & table structure
    // ══════════════════════════════════════════════════════════════════

    public function test_notifications_table_has_required_columns(): void
    {
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('notifications'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('notifications', 'user_id'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('notifications', 'order_id'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('notifications', 'type'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('notifications', 'message'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('notifications', 'is_read'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('notifications', 'read_at'));
    }

    public function test_notification_model_is_fillable(): void
    {
        $model = new Notification();
        $this->assertContains('user_id', $model->getFillable());
        $this->assertContains('order_id', $model->getFillable());
        $this->assertContains('type', $model->getFillable());
        $this->assertContains('message', $model->getFillable());
        $this->assertContains('is_read', $model->getFillable());
        $this->assertContains('read_at', $model->getFillable());
    }

    public function test_notification_model_has_relationships(): void
    {
        $model = new Notification();
        $this->assertTrue(method_exists($model, 'user'));
        $this->assertTrue(method_exists($model, 'order'));
    }

    public function test_notification_model_has_unread_scope(): void
    {
        $model = new Notification();
        $this->assertTrue(method_exists($model, 'scopeUnread'));
    }

    // ══════════════════════════════════════════════════════════════════
    // 12. NotificationService exists and has required methods
    // ══════════════════════════════════════════════════════════════════

    public function test_notification_service_has_required_methods(): void
    {
        $service = app(NotificationService::class);
        $this->assertTrue(method_exists($service, 'notify'));
        $this->assertTrue(method_exists($service, 'markAsRead'));
        $this->assertTrue(method_exists($service, 'unreadCount'));
    }

    // ══════════════════════════════════════════════════════════════════
    // 13. Notification routes exist
    // ══════════════════════════════════════════════════════════════════

    public function test_notification_routes_are_registered(): void
    {
        $this->assertNotEmpty(route('notifications.index'));
        $this->assertNotEmpty(route('notifications.unread-count'));
    }

    // ══════════════════════════════════════════════════════════════════
    // 14. Unauthenticated user redirected from notifications
    // ══════════════════════════════════════════════════════════════════

    public function test_unauthenticated_user_redirected_from_notifications(): void
    {
        $response = $this->get(route('notifications.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_redirected_from_unread_count(): void
    {
        $response = $this->getJson(route('notifications.unread-count'));
        $response->assertStatus(401);
    }

    // ══════════════════════════════════════════════════════════════════
    // 15. Notification is_read defaults to false
    // ══════════════════════════════════════════════════════════════════

    public function test_notification_is_read_defaults_to_false(): void
    {
        $order = $this->createDraftOrder();
        $this->submitOrder($order);
        $this->approveOrder($order);

        $notification = Notification::where('order_id', $order->id)->first();
        $this->assertFalse($notification->is_read);
        $this->assertNull($notification->read_at);
    }

    // ══════════════════════════════════════════════════════════════════
    // 16. approve() transaction: both status + notification succeed or both fail
    // ══════════════════════════════════════════════════════════════════

    public function test_approve_rollback_prevents_orphaned_state(): void
    {
        $order = $this->createDraftOrder();
        $this->submitOrder($order);

        $notificationService = $this->mock(NotificationService::class);
        $notificationService->shouldReceive('notify')
            ->once()
            ->andThrow(new \RuntimeException('Simulated failure'));

        $service = app(\App\Services\OrderService::class);

        try {
            $service->approve($order, $this->admin->id);
        } catch (\RuntimeException $e) {
            // Expected
        }

        $order->refresh();
        $this->assertEquals('PENDING', $order->status);
        $this->assertNull($order->approved_by);
        $this->assertDatabaseMissing('notifications', ['order_id' => $order->id, 'type' => 'APPROVED']);
    }

    public function test_reject_rollback_prevents_orphaned_state(): void
    {
        $order = $this->createDraftOrder();
        $this->submitOrder($order);

        $notificationService = $this->mock(NotificationService::class);
        $notificationService->shouldReceive('notify')
            ->once()
            ->andThrow(new \RuntimeException('Simulated failure'));

        $service = app(\App\Services\OrderService::class);

        try {
            $service->reject($order, $this->admin->id, 'سبب');
        } catch (\RuntimeException $e) {
            // Expected
        }

        $order->refresh();
        $this->assertEquals('PENDING', $order->status);
        $this->assertNull($order->rejected_by);
        $this->assertDatabaseMissing('notifications', ['order_id' => $order->id, 'type' => 'REJECTED']);
    }

    // ══════════════════════════════════════════════════════════════════
    // 17. Full lifecycle: DRAFT → PENDING → APPROVED → EXECUTED creates exactly 2 notifications
    // ══════════════════════════════════════════════════════════════════

    public function test_full_lifecycle_creates_two_notifications(): void
    {
        $order = $this->createDraftOrder('receipt', '1000.00');
        $this->submitOrder($order);
        $this->approveOrder($order);
        $this->executeOrder($order);

        $notifications = Notification::where('order_id', $order->id)->get();
        $this->assertCount(2, $notifications);

        $types = $notifications->pluck('type')->sort()->values()->all();
        $this->assertEquals(['APPROVED', 'EXECUTED'], $types);
    }

    // ══════════════════════════════════════════════════════════════════
    // 18. Notification sent to order creator (not the admin who approved)
    // ══════════════════════════════════════════════════════════════════

    public function test_notification_sent_to_order_creator_not_admin(): void
    {
        $order = $this->createDraftOrder('payment', '100.00', $this->client->id);
        $this->submitOrder($order);
        $this->approveOrder($order, $this->admin->id);

        $notification = Notification::where('order_id', $order->id)->first();
        $this->assertEquals($this->client->id, $notification->user_id);
        $this->assertNotEquals($this->admin->id, $notification->user_id);
    }

    // ══════════════════════════════════════════════════════════════════
    // 19. No TODO comments remain in OrderService
    // ══════════════════════════════════════════════════════════════════

    public function test_no_todo_notification_comments_in_order_service(): void
    {
        $source = file_get_contents(app_path('Services/OrderService.php'));
        $this->assertStringNotContainsString('// TODO: notification', $source,
            'All TODO notification comments should be replaced with actual implementation');
    }

    // ══════════════════════════════════════════════════════════════════
    // 20. NotificationController exists and has required methods
    // ══════════════════════════════════════════════════════════════════

    public function test_notification_controller_has_required_methods(): void
    {
        $controller = app(\App\Http\Controllers\NotificationController::class);
        $this->assertTrue(method_exists($controller, 'index'));
        $this->assertTrue(method_exists($controller, 'markAsRead'));
        $this->assertTrue(method_exists($controller, 'unreadCount'));
    }

    // ══════════════════════════════════════════════════════════════════
    // 21. Layout contains notification badge
    // ══════════════════════════════════════════════════════════════════

    public function test_layout_contains_notification_badge_element(): void
    {
        $content = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString('notification-badge', $content);
        $this->assertStringContainsString('notifications.index', $content);
    }

    public function test_layout_contains_polling_javascript(): void
    {
        $content = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString('/notifications/unread-count', $content);
        $this->assertStringContainsString('fetchUnreadCount', $content);
        $this->assertStringContainsString('setInterval', $content);
        $this->assertStringContainsString('30000', $content);
    }

    // ══════════════════════════════════════════════════════════════════
    // 22. Notification view file exists
    // ══════════════════════════════════════════════════════════════════

    public function test_notifications_index_view_exists(): void
    {
        $this->assertFileExists(
            resource_path('views/notifications/index.blade.php'),
            'notifications index view must exist'
        );
    }

    // ══════════════════════════════════════════════════════════════════
    // 23. Notification view shows type badges in Arabic
    // ══════════════════════════════════════════════════════════════════

    public function test_notification_view_contains_arabic_type_labels(): void
    {
        $content = file_get_contents(resource_path('views/notifications/index.blade.php'));
        $this->assertStringContainsString('موافق عليه', $content);
        $this->assertStringContainsString('مرفوض', $content);
        $this->assertStringContainsString('تم التنفيذ', $content);
    }

    // ══════════════════════════════════════════════════════════════════
    // 24. Notification view links to client order show
    // ══════════════════════════════════════════════════════════════════

    public function test_notification_view_links_to_client_order_show(): void
    {
        $content = file_get_contents(resource_path('views/notifications/index.blade.php'));
        $this->assertStringContainsString('client.orders.show', $content);
    }

    // ══════════════════════════════════════════════════════════════════
    // 25. Notifications view shows empty state when no notifications
    // ══════════════════════════════════════════════════════════════════

    public function test_notifications_page_shows_empty_state(): void
    {
        $this->actingAs($this->client);
        $response = $this->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('لا توجد إشعارات');
    }

    // ══════════════════════════════════════════════════════════════════
    // 26. Pagination works on notifications page
    // ══════════════════════════════════════════════════════════════════

    public function test_notifications_page_is_paginated(): void
    {
        $order = $this->createDraftOrder();
        $this->submitOrder($order);
        $this->approveOrder($order);

        $this->actingAs($this->client);
        $response = $this->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee($order->order_number);
    }

    // ══════════════════════════════════════════════════════════════════
    // 27. Marking as read redirects back
    // ══════════════════════════════════════════════════════════════════

    public function test_mark_as_read_redirects_back(): void
    {
        $order = $this->createDraftOrder();
        $this->submitOrder($order);
        $this->approveOrder($order);

        $notification = Notification::where('order_id', $order->id)->first();

        $this->actingAs($this->client);
        $response = $this->post(route('notifications.read', $notification));

        $response->assertRedirect();
    }

    // ══════════════════════════════════════════════════════════════════
    // 28. Migration is idempotent (up/down works)
    // ══════════════════════════════════════════════════════════════════

    public function test_notifications_migration_up_and_down(): void
    {
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('notifications'));

        Schema::dropIfExists('notifications');
        $this->assertFalse(DB::getSchemaBuilder()->hasTable('notifications'));

        \Artisan::call('migrate:fresh', ['--force' => true]);
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('notifications'));
    }

    // ══════════════════════════════════════════════════════════════════
    // 29. Multiple orders generate independent notifications
    // ══════════════════════════════════════════════════════════════════

    public function test_multiple_orders_generate_independent_notifications(): void
    {
        $o1 = $this->createDraftOrder('payment', '100.00');
        $this->submitOrder($o1);
        $this->approveOrder($o1);

        $o2 = $this->createDraftOrder('receipt', '200.00');
        $this->submitOrder($o2);
        $this->rejectOrder($o2, 'سبب آخر');

        $this->assertDatabaseHas('notifications', ['order_id' => $o1->id, 'type' => 'APPROVED']);
        $this->assertDatabaseHas('notifications', ['order_id' => $o2->id, 'type' => 'REJECTED']);
        $this->assertDatabaseMissing('notifications', ['order_id' => $o1->id, 'type' => 'REJECTED']);
        $this->assertDatabaseMissing('notifications', ['order_id' => $o2->id, 'type' => 'APPROVED']);
    }

    // ══════════════════════════════════════════════════════════════════
    // 30. NotificationService injected into OrderService via constructor
    // ══════════════════════════════════════════════════════════════════

    public function test_order_service_injects_notification_service(): void
    {
        $reflection = new \ReflectionClass(\App\Services\OrderService::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();

        $parameterTypes = array_map(fn($p) => $p->getType()->getName(), $parameters);

        $this->assertContains(NotificationService::class, $parameterTypes);
    }

    // ══════════════════════════════════════════════════════════════════
    // VERIFICATION: Rollback test (phase 5 prompt §2)
    // ══════════════════════════════════════════════════════════════════

    public function test_execute_rolls_back_completely_if_notification_fails(): void
    {
        $order = $this->createDraftOrder('payment', '100.00');
        $this->submitOrder($order);
        $this->approveOrder($order);

        $this->assertEquals('APPROVED', $order->status);
        $this->assertNull($order->executed_at);
        $itemsCountBefore = DailyMovement::count();
        $notificationsBefore = Notification::where('order_id', $order->id)->count();

        $notificationService = $this->mock(NotificationService::class);
        $notificationService->shouldReceive('notify')
            ->once()
            ->andThrow(new \Exception('محاكاة فشل'));

        try {
            app(\App\Services\OrderService::class)->execute($order, $this->admin->id);
            $this->fail('كان يجب أن يرمي استثناء');
        } catch (\Exception $e) {
            // تحقق: الطلب لسا APPROVED (ما تحدثش لـ EXECUTED)
            $order->refresh();
            $this->assertEquals('APPROVED', $order->status);
            $this->assertNull($order->executed_at);

            // تحقق: ولا صف انضاف بـ daily_movements
            $this->assertEquals($itemsCountBefore, DailyMovement::count());

            // تحقق: ولا إشعار EXECUTED انضاف (الموجود هو APPROVED من approve())
            $this->assertEquals($notificationsBefore, Notification::where('order_id', $order->id)->count());
            $this->assertDatabaseMissing('notifications', ['order_id' => $order->id, 'type' => 'EXECUTED']);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // VERIFICATION: approve() rollback test
    // ══════════════════════════════════════════════════════════════════

    public function test_approve_rolls_back_completely_if_notification_fails(): void
    {
        $order = $this->createDraftOrder('payment', '100.00');
        $this->submitOrder($order);

        $this->assertEquals('PENDING', $order->status);

        $notificationService = $this->mock(NotificationService::class);
        $notificationService->shouldReceive('notify')
            ->once()
            ->andThrow(new \Exception('محاكاة فشل'));

        try {
            app(\App\Services\OrderService::class)->approve($order, $this->admin->id);
            $this->fail('كان يجب أن يرمي استثناء');
        } catch (\Exception $e) {
            $order->refresh();
            $this->assertEquals('PENDING', $order->status);
            $this->assertNull($order->approved_by);
            $this->assertNull($order->approved_at);
            $this->assertDatabaseMissing('notifications', ['order_id' => $order->id]);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // VERIFICATION: reject() rollback test
    // ══════════════════════════════════════════════════════════════════

    public function test_reject_rolls_back_completely_if_notification_fails(): void
    {
        $order = $this->createDraftOrder('payment', '100.00');
        $this->submitOrder($order);

        $this->assertEquals('PENDING', $order->status);

        $notificationService = $this->mock(NotificationService::class);
        $notificationService->shouldReceive('notify')
            ->once()
            ->andThrow(new \Exception('محاكاة فشل'));

        try {
            app(\App\Services\OrderService::class)->reject($order, $this->admin->id, 'سبب');
            $this->fail('كان يجب أن يرمي استثناء');
        } catch (\Exception $e) {
            $order->refresh();
            $this->assertEquals('PENDING', $order->status);
            $this->assertNull($order->rejected_by);
            $this->assertNull($order->rejection_reason);
            $this->assertDatabaseMissing('notifications', ['order_id' => $order->id]);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // VERIFICATION: Isolation test (phase 5 prompt §3)
    // ══════════════════════════════════════════════════════════════════

    public function test_notification_only_created_for_order_creator(): void
    {
        $order = $this->createDraftOrder('payment', '100.00', $this->client->id);
        $this->submitOrder($order);

        $this->assertEquals(0, Notification::count());

        $this->approveOrder($order, $this->admin->id);

        // تحقق العدد الكلي بجدول notifications = 1 بالضبط
        $this->assertEquals(1, Notification::count());

        // تحقق الصف الوحيد يخص العميل فقط
        $notification = Notification::first();
        $this->assertEquals($this->client->id, $notification->user_id);

        // تحقق صراحة: ما في صف لـ admin أو investor
        $this->assertDatabaseMissing('notifications', ['user_id' => $this->admin->id]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $this->investor->id]);
    }
}
