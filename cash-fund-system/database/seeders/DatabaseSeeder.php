<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            'name'            => 'System Admin',
            'email'           => 'admin@example.com',
            'national_id'     => '000000001',
            'employee_number' => 'EMP-0001',
            'phone'           => null,
            'position'        => null,
            'username'        => 'admin',
            'password'        => Hash::make('password'),
            'role'            => 'admin',
            'is_active'       => true,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        DB::table('users')->insert([
            'name'            => 'Super Admin',
            'email'           => 'superadmin@example.com',
            'national_id'     => '000000002',
            'employee_number' => 'EMP-0002',
            'phone'           => null,
            'position'        => null,
            'username'        => 'superAdmin',
            'password'        => Hash::make('Super123?'),
            'role'            => 'admin',
            'is_active'       => true,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $permissions = [
            ['key' => 'create_order',       'label' => 'إنشاء طلب',      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'approve_order',      'label' => 'اعتماد طلب',      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'reject_order',       'label' => 'رفض طلب',        'created_at' => now(), 'updated_at' => now()],
            ['key' => 'execute_order',      'label' => 'تنفيذ طلب',      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'cancel_order',       'label' => 'إلغاء طلب',      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'manage_users',       'label' => 'إدارة المستخدمين', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'manage_permissions', 'label' => 'إدارة الصلاحيات', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'manage_categories',  'label' => 'إدارة التصنيفات', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('permissions')->insert($permissions);

        $permissionRows = DB::table('permissions')->get();

        $rolePermissions = [];
        foreach ($permissionRows as $perm) {
            if ($perm->key === 'create_order') {
                continue;
            }
            $rolePermissions[] = [
                'role'          => 'admin',
                'permission_id' => $perm->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }

        DB::table('role_permissions')->insert($rolePermissions);

        $createOrderPerm = DB::table('permissions')->where('key', 'create_order')->first();
        if ($createOrderPerm) {
            DB::table('role_permissions')->insert([
                'role'          => 'client',
                'permission_id' => $createOrderPerm->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        $this->call(CategorySeeder::class);
    }
}
