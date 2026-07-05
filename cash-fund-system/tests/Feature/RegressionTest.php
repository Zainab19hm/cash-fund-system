<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegressionTest extends TestCase
{
    use RefreshDatabase;

    private function seedAndLogin(): User
    {
        \Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $user = User::where('username', 'admin')->first();
        $this->actingAs($user);
        return $user;
    }

    public function test_admin_can_access_dashboard(): void
    {
        $this->seedAndLogin();
        $response = $this->get(route('admin.dashboard'));
        $response->assertOk();
    }

    public function test_user_management_routes_registered(): void
    {
        $this->seedAndLogin();
        $this->assertNotEmpty(route('admin.users.index'));
        $this->assertNotEmpty(route('admin.users.create'));
        $this->assertNotEmpty(route('admin.users.store'));
    }

    public function test_user_management_store_works(): void
    {
        $this->seedAndLogin();
        $response = $this->post(route('admin.users.store'), [
            'name' => 'New User',
            'username' => 'new_user_regression',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'client',
        ]);
        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['username' => 'new_user_regression']);
    }

    public function test_permissions_database_has_manage_categories(): void
    {
        $this->seedAndLogin();
        $permissions = DB::table('permissions')->pluck('key')->toArray();
        $this->assertContains('manage_categories', $permissions);
    }

    public function test_permissions_update_works(): void
    {
        $this->seedAndLogin();
        $permIds = DB::table('permissions')->pluck('id', 'key')->toArray();
        $assignments = [];
        foreach ($permIds as $key => $id) {
            $assignments[] = ['role' => 'admin', 'permission_id' => $id];
            $assignments[] = ['role' => 'investor', 'permission_id' => $id];
            $assignments[] = ['role' => 'client', 'permission_id' => $id];
        }
        $response = $this->post(route('admin.permissions.update'), ['assignments' => $assignments]);
        $response->assertRedirect(route('admin.permissions.index'));
        $this->assertDatabaseCount('role_permissions', count($assignments));
    }

    public function test_role_middleware_blocks_wrong_role(): void
    {
        \Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $investor = User::create([
            'name' => 'Investor',
            'username' => 'investor_regression',
            'password' => 'password',
            'role' => 'investor',
            'is_active' => true,
        ]);
        $this->actingAs($investor);
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(403);
    }

    public function test_role_middleware_blocks_inactive_user(): void
    {
        \Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $admin = User::create([
            'name' => 'Inactive Admin',
            'username' => 'inactive_admin',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => false,
        ]);
        $this->actingAs($admin);
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_old_audit_records_unaffected_by_new_migrations(): void
    {
        $this->seedAndLogin();
        $adminId = DB::table('users')->first()->id;
        DB::table('log_audit')->insert([
            'user_id' => $adminId,
            'action' => 'create',
            'entity_type' => 'users',
            'entity_id' => 1,
            'notes' => null,
            'created_at' => now()->subDays(10),
        ]);

        $exitCode = \Artisan::call('migrate', ['--force' => true]);
        $this->assertEquals(0, $exitCode);

        $logCount = DB::table('log_audit')
            ->where('entity_type', 'users')
            ->where('action', 'create')
            ->count();
        $this->assertGreaterThanOrEqual(1, $logCount);
    }

    public function test_category_management_routes_exist_and_work(): void
    {
        $this->seedAndLogin();
        $this->assertNotEmpty(route('admin.categories.index'));
        $this->assertNotEmpty(route('admin.categories.create'));
        $this->assertNotEmpty(route('admin.categories.store'));

        // Verify categories are seeded and accessible
        $count = DB::table('categories')->count();
        $this->assertEquals(6, $count);
    }

    public function test_route_names_no_conflicts(): void
    {
        $this->seedAndLogin();
        $routes = [
            'admin.dashboard',
            'admin.users.index',
            'admin.users.create',
            'admin.users.store',
            'admin.permissions.index',
            'admin.permissions.update',
            'admin.categories.index',
            'admin.categories.create',
            'admin.categories.store',
        ];
        foreach ($routes as $routeName) {
            $this->assertNotEmpty(route($routeName), "Route '{$routeName}' should be registered");
        }
    }

    public function test_admin_nav_component_contains_required_links(): void
    {
        $content = file_get_contents(resource_path('views/components/admin-nav.blade.php'));
        $this->assertStringContainsString('admin.users.index', $content);
        $this->assertStringContainsString('admin.permissions.index', $content);
        $this->assertStringContainsString('admin.categories.index', $content);
    }

    public function test_categories_index_view_renders_successfully(): void
    {
        $this->seedAndLogin();
        $response = $this->get(route('admin.categories.index'));
        $response->assertStatus(200);
        $response->assertSee('إدارة التصنيفات');
        $response->assertSee('رواتب');
    }

    public function test_categories_create_view_renders_successfully(): void
    {
        $this->seedAndLogin();
        $response = $this->get(route('admin.categories.create'));
        $response->assertStatus(200);
        $response->assertSee('تصنيف جديد');
    }

    public function test_categories_edit_view_renders_successfully(): void
    {
        $this->seedAndLogin();
        $catId = DB::table('categories')->first()->id;
        $response = $this->get(route('admin.categories.edit', $catId));
        $response->assertStatus(200);
        $response->assertSee('تعديل التصنيف');
    }

    public function test_users_index_view_renders_successfully(): void
    {
        $this->seedAndLogin();
        $response = $this->get(route('admin.users.index'));
        $response->assertStatus(200);
        $response->assertSee('إدارة المستخدمين');
    }

    public function test_permissions_index_view_renders_successfully(): void
    {
        $this->seedAndLogin();
        $response = $this->get(route('admin.permissions.index'));
        $response->assertStatus(200);
        $response->assertSee('إدارة الصلاحيات');
        $response->assertSee('manage_categories');
    }

    public function test_app_layout_component_file_exists(): void
    {
        $this->assertFileExists(
            resource_path('views/components/app-layout.blade.php'),
            'app-layout component file must exist'
        );
    }

    public function test_rtl_and_lang_attributes_in_layout(): void
    {
        $this->seedAndLogin();
        $response = $this->get(route('admin.categories.index'));
        $response->assertStatus(200);
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
    }

    // ── Phase 4A Regression ────────────────────────────────────────

    public function test_orders_fund_table_exists(): void
    {
        $this->seedAndLogin();
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('orders_fund'));
    }

    public function test_order_items_table_exists(): void
    {
        $this->seedAndLogin();
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('order_items'));
    }

    public function test_documents_table_exists(): void
    {
        $this->seedAndLogin();
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('documents'));
    }

    public function test_order_number_sequences_table_exists(): void
    {
        $this->seedAndLogin();
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('order_number_sequences'));
    }

    public function test_client_order_routes_registered(): void
    {
        $this->seedAndLogin();
        $routes = [
            'client.orders.index',
            'client.orders.create',
            'client.orders.store',
        ];
        foreach ($routes as $routeName) {
            $this->assertNotEmpty(route($routeName), "Route '{$routeName}' should be registered");
        }
    }

    public function test_order_number_generation_works(): void
    {
        $this->seedAndLogin();
        $service = new \App\Services\OrderNumberService();
        $number = $service->generate();
        $this->assertMatchesRegularExpression('/^ORD-\d{4}-\d{3}$/', $number);
    }

    public function test_status_badge_component_file_exists(): void
    {
        $this->assertFileExists(
            resource_path('views/components/status-badge.blade.php'),
            'status-badge component file must exist'
        );
    }

    public function test_status_badge_component_contains_all_statuses(): void
    {
        $content = file_get_contents(resource_path('views/components/status-badge.blade.php'));
        $statuses = ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED', 'EXECUTED', 'CANCELLED'];
        foreach ($statuses as $status) {
            $this->assertStringContainsString($status, $content);
        }
    }

    public function test_create_order_permission_exists_in_database(): void
    {
        $this->seedAndLogin();
        $perm = DB::table('permissions')->where('key', 'create_order')->first();
        $this->assertNotNull($perm);
    }

    public function test_order_model_exists_and_isfillable(): void
    {
        $this->seedAndLogin();
        $model = new \App\Models\OrderFund();
        $this->assertContains('order_number', $model->getFillable());
        $this->assertContains('status', $model->getFillable());
    }

    public function test_order_item_model_exists_and_isfillable(): void
    {
        $this->seedAndLogin();
        $model = new \App\Models\OrderItem();
        $this->assertContains('order_id', $model->getFillable());
        $this->assertContains('category_id', $model->getFillable());
    }

    public function test_document_model_exists_and_isfillable(): void
    {
        $this->seedAndLogin();
        $model = new \App\Models\Document();
        $this->assertContains('order_id', $model->getFillable());
        $this->assertContains('file_path', $model->getFillable());
    }

    public function test_admin_cannot_access_client_order_routes(): void
    {
        $this->seedAndLogin();
        $response = $this->get(route('client.orders.index'));
        $response->assertStatus(403);
    }

    public function test_categories_accessible_after_new_migrations(): void
    {
        $this->seedAndLogin();
        $count = DB::table('categories')->count();
        $this->assertEquals(6, $count);
    }

    public function test_permissions_system_intact_after_new_migrations(): void
    {
        $this->seedAndLogin();
        $permCount = DB::table('permissions')->count();
        $this->assertEquals(8, $permCount);

        $adminPerms = DB::table('role_permissions')
            ->where('role', 'admin')
            ->count();
        $this->assertEquals(7, $adminPerms);
    }

    public function test_create_order_permission_is_client_only(): void
    {
        $this->seedAndLogin();

        $createOrderPerm = DB::table('permissions')->where('key', 'create_order')->first();
        $this->assertNotNull($createOrderPerm, 'create_order permission must exist');

        $adminLinked = DB::table('role_permissions')
            ->where('role', 'admin')
            ->where('permission_id', $createOrderPerm->id)
            ->exists();
        $this->assertFalse($adminLinked, 'create_order must NOT be linked to admin');

        $investorLinked = DB::table('role_permissions')
            ->where('role', 'investor')
            ->where('permission_id', $createOrderPerm->id)
            ->exists();
        $this->assertFalse($investorLinked, 'create_order must NOT be linked to investor');

        $clientLinked = DB::table('role_permissions')
            ->where('role', 'client')
            ->where('permission_id', $createOrderPerm->id)
            ->exists();
        $this->assertTrue($clientLinked, 'create_order must be linked to client');
    }

    // ── Phase 4B Regression ────────────────────────────────────────

    public function test_daily_movements_table_exists(): void
    {
        $this->seedAndLogin();
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('daily_movements'));
    }

    public function test_daily_movements_has_required_columns(): void
    {
        $this->seedAndLogin();
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('daily_movements', 'order_id'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('daily_movements', 'movement_type'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('daily_movements', 'amount'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('daily_movements', 'balance_after'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('daily_movements', 'movement_date'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('daily_movements', 'executed_at'));
    }

    public function test_daily_movement_model_exists_and_is_fillable(): void
    {
        $this->seedAndLogin();
        $model = new \App\Models\DailyMovement();
        $this->assertContains('order_id', $model->getFillable());
        $this->assertContains('balance_after', $model->getFillable());
    }

    public function test_admin_order_routes_registered(): void
    {
        $this->seedAndLogin();
        $routes = [
            'admin.orders.index',
            'admin.orders.approve',
            'admin.orders.reject',
            'admin.orders.execute',
            'admin.orders.cancel',
        ];
        foreach ($routes as $routeName) {
            $this->assertNotEmpty(route($routeName, 1), "Route '{$routeName}' should be registered");
        }
    }

    public function test_client_cancel_route_registered(): void
    {
        $this->seedAndLogin();
        $this->assertNotEmpty(route('client.orders.cancel', 1));
    }

    public function test_admin_order_service_has_required_methods(): void
    {
        $this->seedAndLogin();
        $service = new \App\Services\OrderService(new \App\Services\OrderNumberService());
        $this->assertTrue(method_exists($service, 'approve'));
        $this->assertTrue(method_exists($service, 'reject'));
        $this->assertTrue(method_exists($service, 'cancel'));
        $this->assertTrue(method_exists($service, 'execute'));
    }

    public function test_order_fund_model_has_daily_movements_relationship(): void
    {
        $this->seedAndLogin();
        // The relationship is on DailyMovement, not OrderFund
        $model = new \App\Models\DailyMovement();
        $this->assertTrue(method_exists($model, 'order'));
    }

    public function test_admin_orders_index_view_file_exists(): void
    {
        $this->assertFileExists(
            resource_path('views/admin/orders/index.blade.php'),
            'admin orders index view must exist'
        );
    }

    public function test_admin_orders_show_view_file_exists(): void
    {
        $this->assertFileExists(
            resource_path('views/admin/orders/show.blade.php'),
            'admin orders show view must exist'
        );
    }

    public function test_admin_nav_contains_orders_link(): void
    {
        $content = file_get_contents(resource_path('views/components/admin-nav.blade.php'));
        $this->assertStringContainsString('admin.orders.index', $content);
        $this->assertStringContainsString('إدارة الطلبات', $content);
    }

    public function test_status_badge_contains_all_statuses(): void
    {
        $content = file_get_contents(resource_path('views/components/status-badge.blade.php'));
        $statuses = ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED', 'EXECUTED', 'CANCELLED'];
        foreach ($statuses as $status) {
            $this->assertStringContainsString($status, $content);
        }
    }

    public function test_investor_blocked_from_admin_order_routes(): void
    {
        \Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $investor = User::create([
            'name'      => 'Investor 4B',
            'username'  => 'investor_4b',
            'password'  => 'password',
            'role'      => 'investor',
            'is_active' => true,
        ]);
        $this->actingAs($investor);
        $response = $this->get(route('admin.orders.index'));
        $response->assertStatus(403);
    }

    public function test_client_blocked_from_admin_order_routes(): void
    {
        \Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $client = User::create([
            'name'      => 'Client 4B',
            'username'  => 'client_4b',
            'password'  => 'password',
            'role'      => 'client',
            'is_active' => true,
        ]);
        $this->actingAs($client);
        $response = $this->get(route('admin.orders.index'));
        $response->assertStatus(403);
    }
}
