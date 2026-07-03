<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = DB::table('users')->insert([
            'name'          => 'System Admin',
            'username'      => 'admin',
            'password'      => Hash::make('password'),
            'role'          => 'admin',
            'is_active'     => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $permissions = [
            ['key' => 'create_order',       'label' => 'إنشاء طلب',      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'approve_order',      'label' => 'اعتماد طلب',      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'reject_order',       'label' => 'رفض طلب',        'created_at' => now(), 'updated_at' => now()],
            ['key' => 'execute_order',      'label' => 'تنفيذ طلب',      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'cancel_order',       'label' => 'إلغاء طلب',      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'manage_users',       'label' => 'إدارة المستخدمين', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'manage_permissions', 'label' => 'إدارة الصلاحيات', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('permissions')->insert($permissions);

        $permissionRows = DB::table('permissions')->get();

        $rolePermissions = [];
        foreach ($permissionRows as $perm) {
            $rolePermissions[] = [
                'role'          => 'admin',
                'permission_id' => $perm->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }

        DB::table('role_permissions')->insert($rolePermissions);
    }
}
