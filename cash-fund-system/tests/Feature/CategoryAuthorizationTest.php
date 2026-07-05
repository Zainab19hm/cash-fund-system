<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CategoryAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function seedAndLogin(string $role): User
    {
        \Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);

        $user = User::create([
            'name' => "Test {$role}",
            'username' => "{$role}_user",
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);

        $this->actingAs($user);
        return $user;
    }

    public function test_investor_cannot_access_category_index(): void
    {
        $this->seedAndLogin('investor');
        $response = $this->get(route('admin.categories.index'));
        $response->assertStatus(403);
    }

    public function test_investor_cannot_access_category_create(): void
    {
        $this->seedAndLogin('investor');
        $response = $this->get(route('admin.categories.create'));
        $response->assertStatus(403);
    }

    public function test_investor_cannot_access_category_store(): void
    {
        $this->seedAndLogin('investor');
        $response = $this->post(route('admin.categories.store'), [
            'name' => 'Test',
            'type' => 'payment',
        ]);
        $response->assertStatus(403);
    }

    public function test_client_cannot_access_category_index(): void
    {
        $this->seedAndLogin('client');
        $response = $this->get(route('admin.categories.index'));
        $response->assertStatus(403);
    }

    public function test_client_cannot_access_category_create(): void
    {
        $this->seedAndLogin('client');
        $response = $this->get(route('admin.categories.create'));
        $response->assertStatus(403);
    }

    public function test_client_cannot_access_category_store(): void
    {
        $this->seedAndLogin('client');
        $response = $this->post(route('admin.categories.store'), [
            'name' => 'Test',
            'type' => 'payment',
        ]);
        $response->assertStatus(403);
    }

    // The seeder assigns ALL permissions to admin role.
    // To test admin WITHOUT manage_categories, we must remove it from role_permissions.
    public function test_admin_without_manage_categories_permission_gets_403(): void
    {
        \Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);

        $managePermId = DB::table('permissions')->where('key', 'manage_categories')->value('id');
        DB::table('role_permissions')
            ->where('role', 'admin')
            ->where('permission_id', $managePermId)
            ->delete();

        $admin = User::create([
            'name' => 'Stripped Admin',
            'username' => 'stripped_admin',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->actingAs($admin);

        $response = $this->get(route('admin.categories.index'));
        $response->assertStatus(403);
    }

    public function test_admin_without_manage_categories_cannot_store(): void
    {
        \Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);

        $managePermId = DB::table('permissions')->where('key', 'manage_categories')->value('id');
        DB::table('role_permissions')
            ->where('role', 'admin')
            ->where('permission_id', $managePermId)
            ->delete();

        $admin = User::create([
            'name' => 'Stripped Admin 2',
            'username' => 'stripped_admin2',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->actingAs($admin);

        $response = $this->post(route('admin.categories.store'), [
            'name' => 'Test',
            'type' => 'payment',
        ]);
        $response->assertStatus(403);
    }

    public function test_csrf_middleware_is_enforced_and_has_no_exclusions(): void
    {
        $this->seedAndLogin('admin');

        // Verify CSRF middleware is registered in the kernel
        $kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $middleware = $kernel->getMiddlewareGroups()['web'];
        $this->assertContains(
            \App\Http\Middleware\VerifyCsrfToken::class,
            $middleware,
            'VerifyCsrfToken middleware must be in the web middleware group'
        );

        // Verify no URLs are excluded from CSRF verification by reading the source
        $source = file_get_contents(app_path('Http/Middleware/VerifyCsrfToken.php'));
        $this->assertStringContainsString('protected $except', $source);
        $this->assertStringNotContainsString('http', $source,
            'CSRF middleware should have no URL exclusions');
    }

    public function test_mass_assignment_protection_on_store(): void
    {
        $this->seedAndLogin('admin');

        $response = $this->post(route('admin.categories.store'), [
            'name' => 'MassTest',
            'type' => 'payment',
            'id' => 999,
            'created_at' => '2020-01-01',
            'is_active' => false,
        ]);
        $response->assertRedirect();

        $category = DB::table('categories')->where('name', 'MassTest')->first();
        $this->assertNotNull($category);
        $this->assertNotEquals(999, $category->id);
        $this->assertTrue((bool) $category->is_active);
    }

    public function test_mass_assignment_protection_on_update(): void
    {
        $this->seedAndLogin('admin');

        $catId = DB::table('categories')->first()->id;

        $response = $this->put(route('admin.categories.update', $catId), [
            'name' => 'UpdatedName',
            'type' => 'payment',
            'id' => 999,
        ]);
        $response->assertRedirect();

        $category = DB::table('categories')->find($catId);
        $this->assertEquals('UpdatedName', $category->name);
    }
}
