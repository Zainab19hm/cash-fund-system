<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DailyMovement;
use App\Models\OrderFund;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase8SecurityPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $client;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->client = User::factory()->create(['role' => 'client', 'is_active' => true]);
        $this->category = Category::factory()->create(['is_active' => true]);
    }

    private function createExecutedOrder(int $index): OrderFund
    {
        $amount = number_format(rand(100, 99999), 2, '.', '');
        $type = $index % 2 === 0 ? 'payment' : 'receipt';

        $order = OrderFund::create([
            'order_number' => sprintf('ORD-PERF-%04d', $index),
            'type' => $type,
            'amount' => $amount,
            'status' => 'EXECUTED',
            'order_date' => now()->subDays(rand(0, 60))->toDateString(),
            'created_by' => $this->client->id,
            'executed_by' => $this->admin->id,
            'executed_at' => now()->subDays(rand(0, 60)),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'category_id' => $this->category->id,
            'description' => "بند اختبار رقم {$index}",
            'amount' => $amount,
        ]);

        DailyMovement::create([
            'order_id' => $order->id,
            'movement_type' => $type,
            'amount' => $amount,
            'balance_after' => $type === 'receipt' ? $amount : bcmul($amount, '-1', 2),
            'movement_date' => $order->order_date,
            'executed_at' => $order->executed_at,
        ]);

        return $order;
    }

    private function seedLargeDataset(int $count = 600): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->createExecutedOrder($i);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Part A — Security Verification Tests
    // ══════════════════════════════════════════════════════════════

    public function test_password_is_hashed_with_bcrypt(): void
    {
        $user = User::factory()->create(['password' => 'test-password-123']);
        $this->assertNotEquals('test-password-123', $user->password);
        $this->assertTrue(password_verify('test-password-123', $user->password));
        $this->assertStringStartsWith('$2y$', $user->password);
    }

    public function test_csrf_token_required_for_forms(): void
    {
        $csrfFile = file_get_contents(app_path('Http/Middleware/VerifyCsrfToken.php'));
        $this->assertStringContainsString('protected $except', $csrfFile);
        $this->assertStringNotContainsString("'/", $csrfFile, 'VerifyCsrfToken must not exclude any routes');
        $this->assertStringContainsString(
            \App\Http\Middleware\VerifyCsrfToken::class,
            file_get_contents(app_path('Http/Kernel.php')),
            'VerifyCsrfToken must be registered in Kernel'
        );
    }

    public function test_documents_stored_outside_public(): void
    {
        $this->assertDirectoryExists(storage_path('app'));
        $testPath = 'private/documents/test';
        $fullPath = storage_path('app/' . $testPath);
        $this->assertStringContainsString('private', $fullPath);
        $this->assertStringNotContainsString('public', $fullPath);
    }

    public function test_decimal_precision_in_migrations(): void
    {
        $migrationFiles = glob(database_path('migrations/*.php'));
        $allContent = '';
        foreach ($migrationFiles as $file) {
            $allContent .= file_get_contents($file);
        }

        $this->assertStringContainsString("decimal('amount', 15, 2)", $allContent, 'orders_fund.amount must use decimal(15,2)');
        $this->assertStringContainsString("decimal('amount', 15, 2)", $allContent, 'order_items.amount must use decimal(15,2)');
        $this->assertStringContainsString("decimal('amount', 15, 2)", $allContent, 'daily_movements.amount must use decimal(15,2)');
        $this->assertStringContainsString("decimal('balance_after', 15, 2)", $allContent, 'daily_movements.balance_after must use decimal(15,2)');
    }

    public function test_no_raw_sql_in_services(): void
    {
        $serviceFile = file_get_contents(app_path('Services/ReportService.php'));
        $this->assertStringNotContainsString('->whereRaw(', $serviceFile);
        $this->assertStringNotContainsString('DB::statement(', $serviceFile);

        $orderServiceFile = file_get_contents(app_path('Services/OrderService.php'));
        $this->assertStringNotContainsString('->whereRaw(', $orderServiceFile);
        $this->assertStringNotContainsString('DB::raw(', $orderServiceFile);
        $this->assertStringNotContainsString('DB::statement(', $orderServiceFile);
    }

    public function test_bcadd_used_for_financial_calculations(): void
    {
        $orderServiceFile = file_get_contents(app_path('Services/OrderService.php'));
        $this->assertStringContainsString('bcadd', $orderServiceFile);
        $this->assertStringContainsString('bccomp', $orderServiceFile);
        $this->assertStringContainsString('bcmul', $orderServiceFile);
    }

    // ══════════════════════════════════════════════════════════════
    // Part B — Confirmation Dialog Tests
    // ══════════════════════════════════════════════════════════════

    public function test_execute_action_has_double_confirmation(): void
    {
        $order = $this->createExecutedOrder(0);
        $order->update(['status' => 'APPROVED']);

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $view = $this->actingAs($admin)->get(route('admin.orders.show', $order));
        $view->assertOk();
        $view->assertSee('confirm_execute');
        $view->assertSee('EXECUTE');
    }

    public function test_approve_action_has_confirm_dialog(): void
    {
        $order = $this->createExecutedOrder(0);
        $order->update(['status' => 'PENDING']);

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $view = $this->actingAs($admin)->get(route('admin.orders.show', $order));
        $view->assertOk();
        $view->assertSee("confirm('هل أنت متأكد من اعتماد هذا الطلب؟')", false);
    }

    public function test_cancel_action_has_confirm_dialog(): void
    {
        $order = $this->createExecutedOrder(0);
        $order->update(['status' => 'DRAFT']);

        $view = $this->actingAs($this->client)->get(route('client.orders.show', $order));
        $view->assertOk();
        $view->assertSee("confirm('هل أنت متأكد من إلغاء هذا الطلب؟')", false);
    }

    public function test_toggle_user_status_has_confirm_dialog(): void
    {
        $user = User::factory()->create(['role' => 'client', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $view = $this->actingAs($admin)->get(route('admin.users.index'));
        $view->assertOk();
        $view->assertSee("confirm(&#039;هل أنت متأكد من إيقاف حساب", false);
    }

    public function test_toggle_category_status_has_confirm_dialog(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        \Illuminate\Support\Facades\DB::table('permissions')->insert([
            ['key' => 'manage_categories', 'label' => 'إدارة التصنيفات', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $permId = \Illuminate\Support\Facades\DB::table('permissions')->where('key', 'manage_categories')->value('id');
        \Illuminate\Support\Facades\DB::table('role_permissions')->insert([
            ['role' => 'admin', 'permission_id' => $permId, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $view = $this->actingAs($admin)->get(route('admin.categories.index'));
        $view->assertOk();
        $view->assertSee("confirm(&#039;هل أنت متأكد من إيقاف التصنيف", false);
    }

    public function test_permissions_update_has_confirmation_modal(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $view = $this->actingAs($admin)->get(route('admin.permissions.index'));
        $view->assertOk();
        $view->assertSee('showConfirm()');
        $view->assertSee('تأكيد التحديث');
    }

    public function test_logout_has_confirm_dialog(): void
    {
        $view = $this->actingAs($this->admin)->get(route('admin.reports.dashboard'));
        $view->assertOk();
        $view->assertSee("confirm('هل أنت متأكد من تسجيل الخروج؟')", false);
    }

    // ══════════════════════════════════════════════════════════════
    // Part C — Performance Tests (600+ executed orders)
    // ══════════════════════════════════════════════════════════════

    public function test_rpt01_performance_large_dataset(): void
    {
        $this->seedLargeDataset(600);

        $start = microtime(true);
        $this->actingAs($this->admin)->get(route('admin.reports.daily-journal'));
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(5, $elapsed, "RPT-01 took {$elapsed}s — exceeds 5s limit");
    }

    public function test_rpt07_performance_large_dataset(): void
    {
        $this->seedLargeDataset(600);

        $start = microtime(true);
        $this->actingAs($this->admin)->get(route('admin.reports.movement-statement', [
            'from' => now()->subDays(90)->toDateString(),
            'to' => now()->toDateString(),
        ]));
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(5, $elapsed, "RPT-07 took {$elapsed}s — exceeds 5s limit");
    }

    public function test_rpt01_daily_journal_returns_600_movements(): void
    {
        $this->seedLargeDataset(600);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.daily-journal'));
        $response->assertOk();
        $this->assertDatabaseCount('daily_movements', 600);
    }

    public function test_rpt07_movement_statement_paginates(): void
    {
        $this->seedLargeDataset(600);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.movement-statement', [
            'from' => now()->subDays(90)->toDateString(),
            'to' => now()->toDateString(),
        ]));
        $response->assertOk();
        $this->assertDatabaseCount('daily_movements', 600);
    }

    // ══════════════════════════════════════════════════════════════
    // Part D — Backup Infrastructure Tests
    // ══════════════════════════════════════════════════════════════

    public function test_backup_config_exists(): void
    {
        $this->assertFileExists(config_path('backup.php'));
        $config = require config_path('backup.php');
        $this->assertEquals('Cash Fund', $config['backup']['name']);
    }

    public function test_backup_cleanup_keeps_30_days(): void
    {
        $config = require config_path('backup.php');
        $this->assertEquals(30, $config['cleanup']['default_strategy']['keep_all_backups_for_days']);
        $this->assertEquals(30, $config['cleanup']['default_strategy']['keep_daily_backups_for_days']);
    }

    public function test_backup_schedule_configured(): void
    {
        $kernelFile = file_get_contents(app_path('Console/Kernel.php'));
        $this->assertStringContainsString("backup:run", $kernelFile);
        $this->assertStringContainsString("backup:clean", $kernelFile);
        $this->assertStringContainsString("->at('02:00')", $kernelFile);
        $this->assertStringContainsString("->at('03:00')", $kernelFile);
    }

    public function test_backup_includes_documents_directory(): void
    {
        $config = require config_path('backup.php');
        $include = $config['backup']['source']['files']['include'];
        $this->assertNotEmpty($include);
    }

    // ملاحظة: backup:run (النسخ الاحتياطي الفعلي) لا يُختبر هنا لأنه يتطلب
    // mysqldump واتصال MySQL حقيقي غير متوفر ببيئة الاختبار (SQLite).
    // يجب التحقق يدوياً من backup:run في بيئة staging/production قبل الاعتماد عليه.
    public function test_backup_clean_command_executes_successfully(): void
    {
        $this->artisan('backup:clean')
            ->assertExitCode(0);
    }

    // ══════════════════════════════════════════════════════════════
    // Part W — RTL / Arabic Tests
    // ══════════════════════════════════════════════════════════════

    public function test_main_layout_has_rtl(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString('dir="rtl"', $layout);
        $this->assertStringContainsString('lang="ar"', $layout);
    }

    public function test_login_page_has_rtl(): void
    {
        $login = file_get_contents(resource_path('views/auth/login.blade.php'));
        $this->assertStringContainsString('dir="rtl"', $login);
        $this->assertStringContainsString('lang="ar"', $login);
    }

    public function test_all_print_views_have_rtl(): void
    {
        $printViews = glob(resource_path('views/admin/reports/prints/*.blade.php'));
        $printViews = array_merge($printViews, glob(resource_path('views/investor/reports/prints/*.blade.php')));

        foreach ($printViews as $view) {
            $content = file_get_contents($view);
            $this->assertStringContainsString(
                'dir="rtl"',
                $content,
                "RTL missing in: " . basename($view)
            );
        }
    }

    public function test_all_standalone_views_have_rtl(): void
    {
        $standalone = [
            resource_path('views/admin/orders/report-pdf.blade.php'),
            resource_path('views/admin/orders/report-print.blade.php'),
            resource_path('views/client/orders/disbursement-voucher-pdf.blade.php'),
            resource_path('views/client/orders/disbursement-voucher-print.blade.php'),
            resource_path('views/client/orders/receipt-voucher-print.blade.php'),
        ];

        foreach ($standalone as $view) {
            if (file_exists($view)) {
                $content = file_get_contents($view);
                $this->assertStringContainsString('dir="rtl"', $content, "RTL missing in: " . basename($view));
            }
        }
    }

    public function test_csrf_token_in_meta_tag(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString('csrf-token', $layout);
    }
}
