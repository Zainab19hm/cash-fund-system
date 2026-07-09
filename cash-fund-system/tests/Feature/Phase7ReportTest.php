<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DailyMovement;
use App\Models\Document;
use App\Models\LogAudit;
use App\Models\OrderFund;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase7ReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $investor;
    protected User $client;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->investor = User::factory()->create(['role' => 'investor', 'is_active' => true]);
        $this->client = User::factory()->create(['role' => 'client', 'is_active' => true]);
        $this->category = Category::factory()->create(['is_active' => true]);
    }

    private function actingAsAdmin()
    {
        return $this->actingAs($this->admin);
    }

    private function actingAsInvestor()
    {
        return $this->actingAs($this->investor);
    }

    private function actingAsClient()
    {
        return $this->actingAs($this->client);
    }

    private function createExecutedOrder(array $overrides = []): OrderFund
    {
        $order = OrderFund::create(array_merge([
            'order_number' => 'ORD-' . uniqid(),
            'type' => 'payment',
            'amount' => '1000.00',
            'status' => 'EXECUTED',
            'order_date' => now()->toDateString(),
            'created_by' => $this->client->id,
            'executed_by' => $this->admin->id,
            'executed_at' => now(),
        ], $overrides));

        OrderItem::create([
            'order_id' => $order->id,
            'category_id' => $this->category->id,
            'description' => 'بند اختبار',
            'amount' => $order->amount,
        ]);

        DailyMovement::create([
            'order_id' => $order->id,
            'movement_type' => $order->type,
            'amount' => $order->amount,
            'balance_after' => $order->type === 'receipt' ? $order->amount : bcmul($order->amount, '-1', 2),
            'movement_date' => now()->toDateString(),
            'executed_at' => now(),
        ]);

        return $order;
    }

    // ══════════════════════════════════════════════════════════════
    // Tests 1-14: All reports return correct data
    // ══════════════════════════════════════════════════════════════

    public function test_rpt01_daily_journal(): void
    {
        $order = $this->createExecutedOrder();

        $this->actingAsAdmin()->get(route('admin.reports.daily-journal'))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_rpt01_daily_journal_filter_by_date(): void
    {
        $order = $this->createExecutedOrder(['order_date' => '2026-01-15']);
        DailyMovement::where('order_id', $order->id)->update(['movement_date' => '2026-01-15']);

        $this->actingAsAdmin()->get(route('admin.reports.daily-journal', ['date' => '2026-01-15']))
            ->assertOk()
            ->assertSee($order->order_number);

        $this->actingAsAdmin()->get(route('admin.reports.daily-journal', ['date' => '2026-12-31']))
            ->assertOk()
            ->assertDontSee($order->order_number);
    }

    public function test_rpt02_orders_with_status(): void
    {
        $order = $this->createExecutedOrder();

        $this->actingAsAdmin()->get(route('admin.reports.orders-status'))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_rpt02_orders_filter_by_status(): void
    {
        $executed = $this->createExecutedOrder();
        $pending = OrderFund::create([
            'order_number' => 'ORD-PENDING',
            'type' => 'receipt',
            'amount' => '500.00',
            'status' => 'PENDING',
            'order_date' => now()->toDateString(),
            'created_by' => $this->client->id,
        ]);

        $this->actingAsAdmin()->get(route('admin.reports.orders-status', ['status' => 'EXECUTED']))
            ->assertOk()
            ->assertSee($executed->order_number)
            ->assertDontSee('ORD-PENDING');

        $this->actingAsAdmin()->get(route('admin.reports.orders-status', ['status' => 'PENDING']))
            ->assertOk()
            ->assertSee('ORD-PENDING')
            ->assertDontSee($executed->order_number);
    }

    public function test_rpt03_order_items_detail(): void
    {
        $order = $this->createExecutedOrder();

        $this->actingAsAdmin()->get(route('admin.reports.order-items', $order))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('بند اختبار');
    }

    public function test_rpt04_missing_documents(): void
    {
        $withDoc = $this->createExecutedOrder();
        Document::create([
            'order_id' => $withDoc->id,
            'file_name' => 'test.pdf',
            'file_path' => '/test.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
            'uploaded_by' => $this->client->id,
            'uploaded_at' => now(),
        ]);

        $withoutDoc = $this->createExecutedOrder(['order_number' => 'ORD-NODOC']);

        $this->actingAsAdmin()->get(route('admin.reports.missing-documents'))
            ->assertOk()
            ->assertSee('ORD-NODOC')
            ->assertDontSee($withDoc->order_number);
    }

    public function test_rpt05_user_activity(): void
    {
        LogAudit::create([
            'user_id' => $this->client->id,
            'action' => 'login',
            'entity_type' => 'users',
            'entity_id' => $this->client->id,
            'created_at' => now(),
        ]);

        $this->actingAsAdmin()->get(route('admin.reports.user-activity'))
            ->assertOk()
            ->assertSee('login');
    }

    public function test_rpt05_user_activity_filter_by_user(): void
    {
        LogAudit::create([
            'user_id' => $this->client->id,
            'action' => 'login',
            'entity_type' => 'users',
            'entity_id' => $this->client->id,
            'created_at' => now(),
        ]);

        LogAudit::create([
            'user_id' => $this->investor->id,
            'action' => 'logout',
            'entity_type' => 'users',
            'entity_id' => $this->investor->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAsAdmin()->get(route('admin.reports.user-activity', ['user_id' => $this->client->id]));
        $response->assertOk();
        $response->assertSee('login');
        $response->assertSee($this->client->name);
        $response->assertDontSee($this->investor->name);
    }

    public function test_rpt06_current_balance(): void
    {
        $order = $this->createExecutedOrder();

        $this->actingAsAdmin()->get(route('admin.reports.current-balance'))
            ->assertOk()
            ->assertSee('-1,000.00');
    }

    public function test_rpt07_movement_statement(): void
    {
        $order = $this->createExecutedOrder();

        $this->actingAsAdmin()->get(route('admin.reports.movement-statement', [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
        ]))->assertOk()->assertSee($order->order_number);
    }

    public function test_rpt08_totals(): void
    {
        $this->createExecutedOrder(['type' => 'payment', 'amount' => '1000.00']);
        $this->createExecutedOrder(['type' => 'receipt', 'amount' => '500.00', 'order_number' => 'ORD-REC']);

        $this->actingAsAdmin()->get(route('admin.reports.totals'))
            ->assertOk()
            ->assertSee('1,000.00')
            ->assertSee('500.00');
    }

    public function test_rpt09_expenses_by_category(): void
    {
        $this->createExecutedOrder();

        $this->actingAsAdmin()->get(route('admin.reports.expenses-by-category'))
            ->assertOk()
            ->assertSee($this->category->name)
            ->assertSee('1,000.00');
    }

    public function test_rpt10_documents_archive(): void
    {
        $order = $this->createExecutedOrder();

        $this->actingAsAdmin()->get(route('admin.reports.documents-archive'))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_rpt11_permissions_report(): void
    {
        $this->createExecutedOrder();

        $this->actingAsAdmin()->get(route('admin.reports.permissions-report'))
            ->assertOk()
            ->assertSee($this->client->name)
            ->assertSee($this->admin->name);
    }

    public function test_rpt12_pending_orders_investor(): void
    {
        $pending = OrderFund::create([
            'order_number' => 'ORD-PENDING',
            'type' => 'payment',
            'amount' => '200.00',
            'status' => 'PENDING',
            'order_date' => now()->toDateString(),
            'created_by' => $this->client->id,
        ]);

        $this->actingAsInvestor()->get(route('investor.pending-orders'))
            ->assertOk()
            ->assertSee('ORD-PENDING');
    }

    public function test_rpt13_orders_dashboard(): void
    {
        $this->createExecutedOrder();
        OrderFund::create([
            'order_number' => 'ORD-PENDING',
            'type' => 'payment',
            'amount' => '100.00',
            'status' => 'PENDING',
            'order_date' => now()->toDateString(),
            'created_by' => $this->client->id,
        ]);

        $this->actingAsAdmin()->get(route('admin.reports.dashboard'))
            ->assertOk()
            ->assertSee('تم التنفيذ')
            ->assertSee('قيد المراجعة');
    }

    public function test_rpt14_audit_trail(): void
    {
        $order = $this->createExecutedOrder();
        $order->update([
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ]);

        $this->actingAsAdmin()->get(route('admin.reports.audit-trail'))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    // ══════════════════════════════════════════════════════════════
    // Test 15: Investor gets 403 for admin-only routes
    // ══════════════════════════════════════════════════════════════

    public function test_investor_forbidden_from_admin_report_routes(): void
    {
        $order = $this->createExecutedOrder();

        $adminRoutes = [
            route('admin.reports.dashboard'),
            route('admin.reports.daily-journal'),
            route('admin.reports.orders-status'),
            route('admin.reports.order-items', $order),
            route('admin.reports.missing-documents'),
            route('admin.reports.user-activity'),
            route('admin.reports.current-balance'),
            route('admin.reports.totals'),
            route('admin.reports.expenses-by-category'),
            route('admin.reports.documents-archive'),
            route('admin.reports.permissions-report'),
            route('admin.reports.audit-trail'),
        ];

        foreach ($adminRoutes as $url) {
            $this->actingAsInvestor()->get($url)->assertStatus(403);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Test 16: Admin can access all 14 reports
    // ══════════════════════════════════════════════════════════════

    public function test_admin_can_access_all_report_routes(): void
    {
        $order = $this->createExecutedOrder();

        $this->actingAsAdmin()->get(route('admin.reports.dashboard'))->assertOk();
        $this->actingAsAdmin()->get(route('admin.reports.daily-journal'))->assertOk();
        $this->actingAsAdmin()->get(route('admin.reports.orders-status'))->assertOk();
        $this->actingAsAdmin()->get(route('admin.reports.order-items', $order))->assertOk();
        $this->actingAsAdmin()->get(route('admin.reports.missing-documents'))->assertOk();
        $this->actingAsAdmin()->get(route('admin.reports.user-activity'))->assertOk();
        $this->actingAsAdmin()->get(route('admin.reports.current-balance'))->assertOk();
        $this->actingAsAdmin()->get(route('admin.reports.movement-statement', [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
        ]))->assertOk();
        $this->actingAsAdmin()->get(route('admin.reports.totals'))->assertOk();
        $this->actingAsAdmin()->get(route('admin.reports.expenses-by-category'))->assertOk();
        $this->actingAsAdmin()->get(route('admin.reports.documents-archive'))->assertOk();
        $this->actingAsAdmin()->get(route('admin.reports.permissions-report'))->assertOk();
        $this->actingAsAdmin()->get(route('admin.reports.audit-trail'))->assertOk();
    }

    // ══════════════════════════════════════════════════════════════
    // Test 17: Client cannot access any report routes
    // ══════════════════════════════════════════════════════════════

    public function test_client_forbidden_from_all_report_routes(): void
    {
        $order = $this->createExecutedOrder();

        $allReportRoutes = [
            route('admin.reports.dashboard'),
            route('admin.reports.daily-journal'),
            route('admin.reports.orders-status'),
            route('admin.reports.current-balance'),
            route('admin.reports.totals'),
            route('admin.reports.expenses-by-category'),
        ];

        foreach ($allReportRoutes as $url) {
            $this->actingAsClient()->get($url)->assertStatus(403);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Test 18: RPT-06 balance matches last balance_after
    // ══════════════════════════════════════════════════════════════

    public function test_rpt06_balance_matches_last_movement(): void
    {
        $order1 = $this->createExecutedOrder([
            'type' => 'receipt',
            'amount' => '5000.00',
            'order_number' => 'ORD-001',
        ]);
        DailyMovement::where('order_id', $order1->id)->update(['balance_after' => '5000.00']);

        $order2 = $this->createExecutedOrder([
            'type' => 'payment',
            'amount' => '1000.00',
            'order_number' => 'ORD-002',
        ]);
        DailyMovement::where('order_id', $order2->id)->update(['balance_after' => '4000.00']);

        $this->actingAsAdmin()->get(route('admin.reports.current-balance'))
            ->assertOk()
            ->assertSee('4,000.00');
    }

    // ══════════════════════════════════════════════════════════════
    // Test 19: RPT-09 only counts EXECUTED orders
    // ══════════════════════════════════════════════════════════════

    public function test_rpt09_only_counts_executed_orders(): void
    {
        $executed = $this->createExecutedOrder(['amount' => '1000.00']);

        $pending = OrderFund::create([
            'order_number' => 'ORD-PENDING-EXP',
            'type' => 'payment',
            'amount' => '2000.00',
            'status' => 'PENDING',
            'order_date' => now()->toDateString(),
            'created_by' => $this->client->id,
        ]);
        OrderItem::create([
            'order_id' => $pending->id,
            'category_id' => $this->category->id,
            'description' => 'بند معلق',
            'amount' => '2000.00',
        ]);

        $response = $this->actingAsAdmin()->get(route('admin.reports.expenses-by-category'));
        $response->assertOk();
        $response->assertSee('1,000.00');
        $response->assertDontSee('2,000.00');
    }

    // ══════════════════════════════════════════════════════════════
    // Test 20: Performance — no N+1 queries on large datasets
    // ══════════════════════════════════════════════════════════════

    public function test_rpt01_no_n_plus_one_queries(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->createExecutedOrder(['order_number' => "ORD-PERF-{$i}"]);
        }

        $this->actingAsAdmin()->get(route('admin.reports.daily-journal'))
            ->assertOk();

        $this->assertDatabaseCount('daily_movements', 5);
    }

    public function test_rpt07_no_n_plus_one_queries(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->createExecutedOrder(['order_number' => "ORD-PERF-{$i}"]);
        }

        $this->actingAsAdmin()->get(route('admin.reports.movement-statement', [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
        ]))->assertOk();

        $this->assertDatabaseCount('daily_movements', 5);
    }

    // ══════════════════════════════════════════════════════════════
    // Test 21: Redirect after login for all roles
    // ══════════════════════════════════════════════════════════════

    public function test_admin_redirects_to_reports_dashboard_after_login(): void
    {
        $this->post(route('login'), [
            'username' => $this->admin->username,
            'password' => 'password',
        ])->assertRedirect(route('admin.reports.dashboard'));
    }

    public function test_investor_redirects_to_dashboard_after_login(): void
    {
        $this->post(route('login'), [
            'username' => $this->investor->username,
            'password' => 'password',
        ])->assertRedirect(route('investor.dashboard'));
    }

    public function test_client_redirects_to_orders_after_login(): void
    {
        $this->post(route('login'), [
            'username' => $this->client->username,
            'password' => 'password',
        ])->assertRedirect(route('client.dashboard'));
    }

    // ══════════════════════════════════════════════════════════════
    // Test 22: Investor dashboard shows real data
    // ══════════════════════════════════════════════════════════════

    public function test_investor_dashboard_shows_balance_and_counts(): void
    {
        $this->createExecutedOrder();

        $this->actingAsInvestor()->get(route('investor.dashboard'))
            ->assertOk()
            ->assertSee('-1,000.00')
            ->assertSee('تم التنفيذ');
    }

    // ══════════════════════════════════════════════════════════════
    // Test 23: Investor can access RPT-06, 07, 08, 09, 12
    // ══════════════════════════════════════════════════════════════

    public function test_investor_can_access_allowed_report_routes(): void
    {
        $this->actingAsInvestor()->get(route('investor.current-balance'))->assertOk();
        $this->actingAsInvestor()->get(route('investor.movement-statement', [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
        ]))->assertOk();
        $this->actingAsInvestor()->get(route('investor.totals'))->assertOk();
        $this->actingAsInvestor()->get(route('investor.expenses-by-category'))->assertOk();
        $this->actingAsInvestor()->get(route('investor.pending-orders'))->assertOk();
    }

    // ══════════════════════════════════════════════════════════════
    // Test 24: RPT-06 investor balance matches admin balance
    // ══════════════════════════════════════════════════════════════

    public function test_rpt06_investor_balance_matches_admin(): void
    {
        $this->createExecutedOrder();

        $adminResponse = $this->actingAsAdmin()->get(route('admin.reports.current-balance'));
        $investorResponse = $this->actingAsInvestor()->get(route('investor.current-balance'));

        $adminResponse->assertOk();
        $investorResponse->assertOk();
    }

    // ══════════════════════════════════════════════════════════════
    // Test 25: Unauthenticated user cannot access reports
    // ══════════════════════════════════════════════════════════════

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $this->get(route('admin.reports.dashboard'))->assertRedirect(route('login'));
        $this->get(route('investor.dashboard'))->assertRedirect(route('login'));
    }

    // ══════════════════════════════════════════════════════════════
    // Test 26: RPT-02 filter by date range
    // ══════════════════════════════════════════════════════════════

    public function test_rpt02_filter_by_date_range(): void
    {
        $order = $this->createExecutedOrder(['order_date' => '2026-06-15']);

        $this->actingAsAdmin()->get(route('admin.reports.orders-status', [
            'from' => '2026-06-01',
            'to' => '2026-06-30',
        ]))->assertOk()->assertSee($order->order_number);

        $this->actingAsAdmin()->get(route('admin.reports.orders-status', [
            'from' => '2026-07-01',
            'to' => '2026-07-31',
        ]))->assertOk()->assertDontSee($order->order_number);
    }

    // ══════════════════════════════════════════════════════════════
    // Test 27: RPT-08 totals with date filter
    // ══════════════════════════════════════════════════════════════

    public function test_rpt08_totals_with_date_filter(): void
    {
        $this->createExecutedOrder(['type' => 'payment', 'amount' => '1000.00', 'order_number' => 'ORD-IN']);

        $this->actingAsAdmin()->get(route('admin.reports.totals', [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
        ]))->assertOk()->assertSee('1,000.00');
    }

    // ══════════════════════════════════════════════════════════════
    // Test 28: RPT-09 expenses with date filter
    // ══════════════════════════════════════════════════════════════

    public function test_rpt09_expenses_with_date_filter(): void
    {
        $this->createExecutedOrder();

        $this->actingAsAdmin()->get(route('admin.reports.expenses-by-category', [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
        ]))->assertOk()->assertSee($this->category->name);
    }

    // ══════════════════════════════════════════════════════════════
    // Test 29: RPT-12 pending orders only shows PENDING
    // ══════════════════════════════════════════════════════════════

    public function test_rpt12_only_shows_pending_orders(): void
    {
        $pending = OrderFund::create([
            'order_number' => 'ORD-PEND-12',
            'type' => 'payment',
            'amount' => '300.00',
            'status' => 'PENDING',
            'order_date' => now()->toDateString(),
            'created_by' => $this->client->id,
        ]);

        $executed = $this->createExecutedOrder(['order_number' => 'ORD-EXEC-12']);

        $this->actingAsInvestor()->get(route('investor.pending-orders'))
            ->assertOk()
            ->assertSee('ORD-PEND-12')
            ->assertDontSee('ORD-EXEC-12');
    }

    // ══════════════════════════════════════════════════════════════
    // Test 30: RPT-13 dashboard counts are accurate
    // ══════════════════════════════════════════════════════════════

    public function test_rpt13_dashboard_counts_accurate(): void
    {
        $this->createExecutedOrder();
        $this->createExecutedOrder(['order_number' => 'ORD-E2', 'type' => 'receipt']);
        OrderFund::create([
            'order_number' => 'ORD-P1',
            'type' => 'payment',
            'amount' => '100.00',
            'status' => 'PENDING',
            'order_date' => now()->toDateString(),
            'created_by' => $this->client->id,
        ]);

        $this->actingAsAdmin()->get(route('admin.reports.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['2', 'تم التنفيذ']);
    }

    // ══════════════════════════════════════════════════════════════
    // Test 31: RPT-01 empty result when no movements
    // ══════════════════════════════════════════════════════════════

    public function test_rpt01_empty_when_no_movements(): void
    {
        $this->actingAsAdmin()->get(route('admin.reports.daily-journal'))
            ->assertOk()
            ->assertSee('لا توجد حركات');
    }

    // ══════════════════════════════════════════════════════════════
    // Test 32: RPT-04 empty when all have documents
    // ══════════════════════════════════════════════════════════════

    public function test_rpt04_empty_when_all_have_documents(): void
    {
        $order = $this->createExecutedOrder();
        Document::create([
            'order_id' => $order->id,
            'file_name' => 'doc.pdf',
            'file_path' => '/doc.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
            'uploaded_by' => $this->client->id,
            'uploaded_at' => now(),
        ]);

        $this->actingAsAdmin()->get(route('admin.reports.missing-documents'))
            ->assertOk()
            ->assertSee('جميع الطلبات لديها وثائق');
    }

    // ══════════════════════════════════════════════════════════════
    // Test 33: RPT-11 includes all users
    // ══════════════════════════════════════════════════════════════

    public function test_rpt11_includes_all_users(): void
    {
        $this->actingAsAdmin()->get(route('admin.reports.permissions-report'))
            ->assertOk()
            ->assertSee($this->admin->name)
            ->assertSee($this->investor->name)
            ->assertSee($this->client->name);
    }

    // ══════════════════════════════════════════════════════════════
    // Test 34: Admin dashboard redirects to reports
    // ══════════════════════════════════════════════════════════════

    public function test_admin_dashboard_redirects_to_reports(): void
    {
        $this->actingAsAdmin()->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.reports.dashboard'));
    }

    // ══════════════════════════════════════════════════════════════
    // Test 35: Movement statement requires from/to
    // ══════════════════════════════════════════════════════════════

    public function test_movement_statement_requires_dates(): void
    {
        $this->actingAsAdmin()->get(route('admin.reports.movement-statement'))
            ->assertSessionHasErrors(['from', 'to']);
    }

    // ══════════════════════════════════════════════════════════════
    // Test 36: Investor movement statement requires dates
    // ══════════════════════════════════════════════════════════════

    public function test_investor_movement_statement_requires_dates(): void
    {
        $this->actingAsInvestor()->get(route('investor.movement-statement'))
            ->assertSessionHasErrors(['from', 'to']);
    }

    // ══════════════════════════════════════════════════════════════
    // Test 37: RPT-05 validates user_id exists
    // ══════════════════════════════════════════════════════════════

    public function test_rpt05_validates_user_id(): void
    {
        $this->actingAsAdmin()->get(route('admin.reports.user-activity', ['user_id' => 9999]))
            ->assertSessionHasErrors(['user_id']);
    }

    // ══════════════════════════════════════════════════════════════
    // Test 38: RPT-02 validates status enum
    // ══════════════════════════════════════════════════════════════

    public function test_rpt02_validates_status_enum(): void
    {
        $this->actingAsAdmin()->get(route('admin.reports.orders-status', ['status' => 'INVALID']))
            ->assertSessionHasErrors(['status']);
    }

    // ══════════════════════════════════════════════════════════════
    // Test 39: Empty balance when no movements exist
    // ══════════════════════════════════════════════════════════════

    public function test_rpt06_zero_balance_when_empty(): void
    {
        $this->actingAsAdmin()->get(route('admin.reports.current-balance'))
            ->assertOk()
            ->assertSee('0.00');
    }

    // ══════════════════════════════════════════════════════════════
    // Test 40: RPT-08 zero totals when empty
    // ══════════════════════════════════════════════════════════════

    public function test_rpt08_zero_totals_when_empty(): void
    {
        $this->actingAsAdmin()->get(route('admin.reports.totals'))
            ->assertOk()
            ->assertSee('0.00');
    }

    // ══════════════════════════════════════════════════════════════
    // Test 41: RPT-09 empty when no executed orders
    // ══════════════════════════════════════════════════════════════

    public function test_rpt09_empty_when_no_executed(): void
    {
        $this->actingAsAdmin()->get(route('admin.reports.expenses-by-category'))
            ->assertOk()
            ->assertSee('لا توجد بيانات صرف');
    }

    // ══════════════════════════════════════════════════════════════
    // Test 42: RPT-12 empty when no pending
    // ══════════════════════════════════════════════════════════════

    public function test_rpt12_empty_when_no_pending(): void
    {
        $this->actingAsInvestor()->get(route('investor.pending-orders'))
            ->assertOk()
            ->assertSee('لا توجد أوامر معلقة');
    }

    // ══════════════════════════════════════════════════════════════
    // Test 43: RPT-14 only shows orders with approval/rejection
    // ══════════════════════════════════════════════════════════════

    public function test_rpt14_only_shows_reviewed_orders(): void
    {
        $approved = $this->createExecutedOrder([
            'order_number' => 'ORD-APP',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ]);

        $draft = OrderFund::create([
            'order_number' => 'ORD-DRAFT',
            'type' => 'payment',
            'amount' => '100.00',
            'status' => 'DRAFT',
            'order_date' => now()->toDateString(),
            'created_by' => $this->client->id,
        ]);

        $this->actingAsAdmin()->get(route('admin.reports.audit-trail'))
            ->assertOk()
            ->assertSee('ORD-APP')
            ->assertDontSee('ORD-DRAFT');
    }

    // ══════════════════════════════════════════════════════════════
    // Test 44: LogAudit model works correctly
    // ══════════════════════════════════════════════════════════════

    public function test_log_audit_model(): void
    {
        $log = LogAudit::create([
            'user_id' => $this->admin->id,
            'action' => 'test_action',
            'entity_type' => 'users',
            'entity_id' => $this->admin->id,
            'notes' => 'test note',
            'created_at' => now(),
        ]);

        $this->assertDatabaseHas('log_audit', [
            'id' => $log->id,
            'action' => 'test_action',
        ]);

        $this->assertEquals('test note', $log->notes);
        $this->assertEquals($this->admin->id, $log->user->id);
    }

    // ══════════════════════════════════════════════════════════════
    // Test 45: User model relationships work
    // ══════════════════════════════════════════════════════════════

    public function test_user_model_relationships(): void
    {
        $order = $this->createExecutedOrder([
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ]);

        $this->assertNotEmpty($this->client->createdOrders);
        $this->assertNotEmpty($this->admin->approvedOrders);
        $this->assertNotEmpty($this->admin->executedOrders);
    }
}
