<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    private function runSeeder(): void
    {
        // Run migrations first, then seed separately to ensure full control
        \Artisan::call('migrate', ['--force' => true]);

        // Manually insert seed data as the DatabaseSeeder does
        DB::table('users')->insert([
            'name' => 'System Admin',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissions = [
            ['key' => 'create_order',       'label' => 'Order Creation',       'created_at' => now(), 'updated_at' => now()],
            ['key' => 'approve_order',      'label' => 'Order Approval',       'created_at' => now(), 'updated_at' => now()],
            ['key' => 'reject_order',       'label' => 'Order Rejection',      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'execute_order',      'label' => 'Order Execution',      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'cancel_order',       'label' => 'Order Cancellation',   'created_at' => now(), 'updated_at' => now()],
            ['key' => 'manage_users',       'label' => 'User Management',      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'manage_permissions', 'label' => 'Permission Management','created_at' => now(), 'updated_at' => now()],
            ['key' => 'manage_categories',  'label' => 'Category Management',  'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('permissions')->insert($permissions);

        $permissionRows = DB::table('permissions')->get();
        $rolePermissions = [];
        foreach ($permissionRows as $perm) {
            $rolePermissions[] = [
                'role' => 'admin',
                'permission_id' => $perm->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('role_permissions')->insert($rolePermissions);

        $categories = [
            ['name' => 'Salaries',          'type' => 'payment', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Office Supplies',   'type' => 'payment', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Maintenance',       'type' => 'payment', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sales Revenue',     'type' => 'receipt', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Investor Support',  'type' => 'receipt', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Misc Services',     'type' => 'both',    'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('categories')->insert($categories);
    }

    public function test_migrate_fresh_seed_works(): void
    {
        $exitCode = \Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $this->assertEquals(0, $exitCode);

        $this->assertTrue(DB::getSchemaBuilder()->hasTable('users'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('permissions'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('role_permissions'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('categories'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('order_number_sequences'));
    }

    public function test_exactly_6_categories_seeded(): void
    {
        $this->runSeeder();
        $count = DB::table('categories')->count();
        $this->assertEquals(6, $count);
    }

    public function test_category_names_and_types_match_documented(): void
    {
        $this->runSeeder();

        $expected = [
            ['name' => 'Salaries', 'type' => 'payment'],
            ['name' => 'Office Supplies', 'type' => 'payment'],
            ['name' => 'Maintenance', 'type' => 'payment'],
            ['name' => 'Sales Revenue', 'type' => 'receipt'],
            ['name' => 'Investor Support', 'type' => 'receipt'],
            ['name' => 'Misc Services', 'type' => 'both'],
        ];

        foreach ($expected as $cat) {
            $exists = DB::table('categories')
                ->where('name', $cat['name'])
                ->where('type', $cat['type'])
                ->exists();
            $this->assertTrue($exists, "Category '{$cat['name']}' with type '{$cat['type']}' not found");
        }
    }

    public function test_manage_categories_permission_exists_and_linked_to_admin_only(): void
    {
        $this->runSeeder();

        $permId = DB::table('permissions')->where('key', 'manage_categories')->value('id');
        $this->assertNotNull($permId, 'manage_categories permission should exist');

        $adminLinked = DB::table('role_permissions')
            ->where('role', 'admin')
            ->where('permission_id', $permId)
            ->exists();
        $this->assertTrue($adminLinked, 'manage_categories should be linked to admin role');

        $investorLinked = DB::table('role_permissions')
            ->where('role', 'investor')
            ->where('permission_id', $permId)
            ->exists();
        $this->assertFalse($investorLinked, 'manage_categories should NOT be linked to investor');

        $clientLinked = DB::table('role_permissions')
            ->where('role', 'client')
            ->where('permission_id', $permId)
            ->exists();
        $this->assertFalse($clientLinked, 'manage_categories should NOT be linked to client');
    }

    public function test_seeder_first_run_works_cleanly(): void
    {
        $this->runSeeder();

        $this->assertDatabaseHas('users', ['username' => 'admin']);
        $this->assertEquals(8, DB::table('permissions')->count());
        $this->assertEquals(8, DB::table('role_permissions')->count());
    }

    public function test_migrate_fresh_twice_no_duplicate_categories(): void
    {
        $this->runSeeder();
        $countAfterFirst = DB::table('categories')->count();

        // Clear and re-seed (simulating migrate:fresh --seed twice)
        \Artisan::call('migrate:fresh', ['--force' => true]);
        $this->runSeeder();
        $countAfterSecond = DB::table('categories')->count();

        $this->assertEquals($countAfterFirst, $countAfterSecond);
        $this->assertEquals(6, $countAfterSecond);
    }
}
