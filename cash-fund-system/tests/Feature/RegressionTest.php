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
}
