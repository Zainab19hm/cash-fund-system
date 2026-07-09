<?php

namespace Tests\Feature;

use App\Models\DailyMovement;
use App\Models\OrderFund;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase6PdfAccountStatementTest extends TestCase
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
            'name'      => 'Admin P6',
            'username'  => 'admin_phase6',
            'password'  => 'password',
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $this->client = User::create([
            'name'      => 'Client P6',
            'username'  => 'client_phase6',
            'password'  => 'password',
            'role'      => 'client',
            'is_active' => true,
        ]);

        $this->otherClient = User::create([
            'name'      => 'Other Client P6',
            'username'  => 'other_client_phase6',
            'password'  => 'password',
            'role'      => 'client',
            'is_active' => true,
        ]);

        $this->investor = User::create([
            'name'      => 'Investor P6',
            'username'  => 'investor_phase6',
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

    private function executeOrder(OrderFund $order, ?int $executedBy = null): void
    {
        app(\App\Services\OrderService::class)->execute($order, $executedBy ?? $this->admin->id);
    }

    private function fullLifecycle(string $type = 'payment', string $amount = '100.00', ?int $creatorId = null): OrderFund
    {
        $order = $this->createDraftOrder($type, $amount, $creatorId);
        $this->submitOrder($order);
        $this->approveOrder($order);
        $this->executeOrder($order);
        return $order->fresh();
    }

    // ══════════════════════════════════════════════════════════════════
    // 1. Client opens disbursement-voucher for own EXECUTED order → 200 + PDF
    // ══════════════════════════════════════════════════════════════════

    public function test_disbursement_voucher_returns_200_for_own_executed_order(): void
    {
        $order = $this->fullLifecycle('payment', '500.00');

        $this->actingAs($this->client);
        $response = $this->get(route('client.orders.disbursement-voucher', $order));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    // ══════════════════════════════════════════════════════════════════
    // 2. Client tries disbursement-voucher for another client's order → 403
    // ══════════════════════════════════════════════════════════════════

    public function test_disbursement_voucher_returns_403_for_other_clients_order(): void
    {
        $order = $this->fullLifecycle('payment', '500.00', $this->otherClient->id);

        $this->actingAs($this->client);
        $response = $this->get(route('client.orders.disbursement-voucher', $order));

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════════════════
    // 3. Client tries disbursement-voucher for own non-EXECUTED order → 404
    // ══════════════════════════════════════════════════════════════════

    public function test_disbursement_voucher_returns_404_for_non_executed_order(): void
    {
        $order = $this->createDraftOrder('payment', '500.00');
        $this->submitOrder($order);
        $this->approveOrder($order);

        $this->actingAs($this->client);
        $response = $this->get(route('client.orders.disbursement-voucher', $order));

        $response->assertStatus(404);
    }

    // ══════════════════════════════════════════════════════════════════
    // 4. admin/investor blocked from disbursement-voucher route (role:client)
    // ══════════════════════════════════════════════════════════════════

    public function test_admin_blocked_from_disbursement_voucher(): void
    {
        $order = $this->fullLifecycle('payment', '500.00');

        $this->actingAs($this->admin);
        $response = $this->get(route('client.orders.disbursement-voucher', $order));

        $response->assertStatus(403);
    }

    public function test_investor_blocked_from_disbursement_voucher(): void
    {
        $order = $this->fullLifecycle('payment', '500.00');

        $this->actingAs($this->investor);
        $response = $this->get(route('client.orders.disbursement-voucher', $order));

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════════════════
    // 5. Account statement shows only current user's EXECUTED orders
    // ══════════════════════════════════════════════════════════════════

    public function test_account_statement_shows_only_own_executed_orders(): void
    {
        $executed = $this->fullLifecycle('payment', '500.00', $this->client->id);

        $pending = $this->createDraftOrder('receipt', '200.00', $this->client->id);
        $this->submitOrder($pending);

        $otherExecuted = $this->fullLifecycle('payment', '300.00', $this->otherClient->id);

        $this->actingAs($this->client);
        $response = $this->get(route('client.orders.account-statement'));

        $response->assertOk();
        $response->assertSee($executed->order_number);
        $response->assertDontSee($pending->order_number);
        $response->assertDontSee($otherExecuted->order_number);
    }

    // ══════════════════════════════════════════════════════════════════
    // 6. Client A's account statement has zero orders from Client B
    // ══════════════════════════════════════════════════════════════════

    public function test_account_statement_no_cross_client_data(): void
    {
        $orderB = $this->fullLifecycle('receipt', '750.00', $this->otherClient->id);

        $this->actingAs($this->client);
        $response = $this->get(route('client.orders.account-statement'));

        $response->assertOk();
        $response->assertDontSee($orderB->order_number);
    }

    // ══════════════════════════════════════════════════════════════════
    // 7. Date filter works correctly
    // ══════════════════════════════════════════════════════════════════

    public function test_account_statement_date_filter_works(): void
    {
        $o1 = $this->fullLifecycle('payment', '100.00');

        $this->actingAs($this->client);

        $response = $this->get(route('client.orders.account-statement', [
            'from_date' => '2026-07-06',
        ]));
        $response->assertOk();
        $response->assertDontSee($o1->order_number);

        $response2 = $this->get(route('client.orders.account-statement', [
            'from_date' => '2026-07-01',
            'to_date'   => '2026-07-10',
        ]));
        $response2->assertOk();
        $response2->assertSee($o1->order_number);
    }

    // ══════════════════════════════════════════════════════════════════
    // 8. PDF content contains order number and correct amount
    // ══════════════════════════════════════════════════════════════════

    public function test_pdf_contains_order_number_and_amount(): void
    {
        $order = $this->fullLifecycle('payment', '1234.56');

        $this->actingAs($this->client);
        $response = $this->get(route('client.orders.disbursement-voucher', $order));

        $response->assertOk();
        $this->assertStringStartsWith('application/pdf', $response->headers->get('Content-Type'));

        $viewHtml = view('client.orders.disbursement-voucher-pdf', ['order' => $order])->render();
        $this->assertStringContainsString($order->order_number, $viewHtml);
        $this->assertStringContainsString('1,234.56', $viewHtml);
    }

    // ══════════════════════════════════════════════════════════════════
    // 9. PDF content-type is application/pdf
    // ══════════════════════════════════════════════════════════════════

    public function test_pdf_content_type_is_application_pdf(): void
    {
        $order = $this->fullLifecycle('receipt', '500.00');

        $this->actingAs($this->client);
        $response = $this->get(route('client.orders.disbursement-voucher', $order));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    // ══════════════════════════════════════════════════════════════════
    // 10. Account statement view has RTL + Arabic
    // ══════════════════════════════════════════════════════════════════

    public function test_account_statement_view_has_rtl_arabic(): void
    {
        $this->actingAs($this->client);
        $response = $this->get(route('client.orders.account-statement'));

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('كشف حساب');
    }

    // ══════════════════════════════════════════════════════════════════
    // 11. Disbursement voucher view has RTL
    // ══════════════════════════════════════════════════════════════════

    public function test_pdf_view_has_rtl_and_arabic_content(): void
    {
        $order = $this->fullLifecycle('payment', '500.00');

        $viewHtml = view('client.orders.disbursement-voucher-pdf', ['order' => $order])->render();

        $this->assertStringContainsString('dir="rtl"', $viewHtml);
        $this->assertStringContainsString('lang="ar"', $viewHtml);
        $this->assertStringContainsString('إذن صرف', $viewHtml);
        $this->assertStringContainsString($order->order_number, $viewHtml);
    }

    // ══════════════════════════════════════════════════════════════════
    // 12. Index view has status filter
    // ══════════════════════════════════════════════════════════════════

    public function test_client_orders_index_has_status_filter(): void
    {
        $content = file_get_contents(resource_path('views/client/orders/index.blade.php'));

        $this->assertStringContainsString('status', $content);
        $this->assertStringContainsString('الكل', $content);
        $this->assertStringContainsString('مسودة', $content);
        $this->assertStringContainsString('قيد المراجعة', $content);
        $this->assertStringContainsString('معتمد', $content);
        $this->assertStringContainsString('مرفوض', $content);
        $this->assertStringContainsString('منفَّذ', $content);
        $this->assertStringContainsString('ملغى', $content);
    }

    // ══════════════════════════════════════════════════════════════════
    // 13. Index view has disbursement voucher link for EXECUTED
    // ══════════════════════════════════════════════════════════════════

    public function test_index_has_disbursement_voucher_link_conditionally(): void
    {
        $content = file_get_contents(resource_path('views/client/orders/index.blade.php'));

        $this->assertStringContainsString('disbursement-voucher', $content);
        $this->assertStringContainsString("order->status === 'EXECUTED'", $content);
    }

    // ══════════════════════════════════════════════════════════════════
    // 14. Index view has account-statement link
    // ══════════════════════════════════════════════════════════════════

    public function test_index_has_account_statement_link(): void
    {
        $content = file_get_contents(resource_path('views/client/orders/index.blade.php'));

        $this->assertStringContainsString('account-statement', $content);
        $this->assertStringContainsString('كشف حساب', $content);
    }

    // ══════════════════════════════════════════════════════════════════
    // 15. Status filter on client index actually filters
    // ══════════════════════════════════════════════════════════════════

    public function test_client_index_status_filter_works(): void
    {
        $draft = $this->createDraftOrder('payment', '100.00');
        $executed = $this->fullLifecycle('receipt', '200.00');

        $this->actingAs($this->client);

        $response = $this->get(route('client.orders.index', ['status' => 'DRAFT']));
        $response->assertOk();
        $response->assertSee($draft->order_number);
        $response->assertDontSee($executed->order_number);

        $response2 = $this->get(route('client.orders.index', ['status' => 'EXECUTED']));
        $response2->assertOk();
        $response2->assertSee($executed->order_number);
        $response2->assertDontSee($draft->order_number);
    }

    // ══════════════════════════════════════════════════════════════════
    // 16. Unauthenticated user redirected from PDF route
    // ══════════════════════════════════════════════════════════════════

    public function test_unauthenticated_redirected_from_pdf_route(): void
    {
        $order = $this->fullLifecycle();
        $response = $this->get(route('client.orders.disbursement-voucher', $order));
        $response->assertRedirect(route('login'));
    }

    public function test_unauthenticated_redirected_from_account_statement(): void
    {
        $response = $this->get(route('client.orders.account-statement'));
        $response->assertRedirect(route('login'));
    }

    // ══════════════════════════════════════════════════════════════════
    // 17. PDF disbursement-voucher view file exists
    // ══════════════════════════════════════════════════════════════════

    public function test_pdf_view_file_exists(): void
    {
        $this->assertFileExists(
            resource_path('views/client/orders/disbursement-voucher-pdf.blade.php'),
            'disbursement-voucher PDF view must exist'
        );
    }

    public function test_account_statement_view_file_exists(): void
    {
        $this->assertFileExists(
            resource_path('views/client/orders/account-statement.blade.php'),
            'account-statement view must exist'
        );
    }

    // ══════════════════════════════════════════════════════════════════
    // 18. OrderPdfController exists and has disbursementVoucher method
    // ══════════════════════════════════════════════════════════════════

    public function test_order_pdf_controller_has_required_methods(): void
    {
        $controller = app(\App\Http\Controllers\Client\OrderPdfController::class);
        $this->assertTrue(method_exists($controller, 'disbursementVoucher'));
    }

    // ══════════════════════════════════════════════════════════════════
    // 19. Routes are registered
    // ══════════════════════════════════════════════════════════════════

    public function test_routes_are_registered(): void
    {
        $this->assertNotEmpty(route('client.orders.disbursement-voucher', 1));
        $this->assertNotEmpty(route('client.orders.account-statement'));
    }

    // ══════════════════════════════════════════════════════════════════
    // 20. PDF shows correct type (صرف/قبض)
    // ══════════════════════════════════════════════════════════════════

    public function test_pdf_shows_correct_type_label(): void
    {
        // Disbursement (payment) → إذن صرف
        $disbursement = $this->fullLifecycle('payment', '500.00');
        $disbursementView = view('client.orders.disbursement-voucher-pdf', ['order' => $disbursement])->render();
        $this->assertStringContainsString('إذن صرف', $disbursementView);
        $this->assertStringNotContainsString('قبض', $disbursementView);

        // Receipt → إذن قبض
        $receipt = $this->fullLifecycle('receipt', '500.00');
        $receiptView = view('client.orders.disbursement-voucher-pdf', ['order' => $receipt])->render();
        $this->assertStringContainsString('قبض', $receiptView);
        $this->assertStringNotContainsString('صرف', $receiptView);
    }
}
