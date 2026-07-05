<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = DB::table('permissions')->orderBy('id')->get();

        $rolePermissions = DB::table('role_permissions')
            ->select('role', 'permission_id')
            ->get()
            ->groupBy('role')
            ->map(fn($rows) => $rows->pluck('permission_id')->toArray())
            ->toArray();

        return view('admin.permissions.index', compact('permissions', 'rolePermissions'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'assignments'   => 'required|array',
            'assignments.*' => 'array',
            'assignments.*.role'         => 'required|in:admin,investor,client',
            'assignments.*.permission_id' => 'required|integer|exists:permissions,id',
        ]);

        $requested = collect($request->assignments);

        $rolesInPayload = $requested->pluck('role')->unique()->values()->toArray();
        sort($rolesInPayload);
        $expectedRoles = ['admin', 'client', 'investor'];

        if ($rolesInPayload !== $expectedRoles) {
            return back()->withErrors([
                'permissions' => 'بيانات ناقصة: يجب إرسال صلاحيات لجميع الأدوار الثلاثة (مدير النظام، مستثمر، عميل).',
            ]);
        }

        $managePermId = DB::table('permissions')
            ->where('key', 'manage_permissions')
            ->value('id');

        $adminHasManage = $requested->contains(
            fn($a) => $a['role'] === 'admin' && (int) $a['permission_id'] === $managePermId
        );

        if (!$adminHasManage) {
            return back()->withErrors([
                'permissions' => 'لا يمكن سحب صلاحية إدارة الصلاحيات من دور مدير النظام — هذا سيمنع أي تعديل مستقبلي على الصلاحيات.',
            ]);
        }

        DB::transaction(function () use ($requested) {
            DB::table('role_permissions')->delete();

            $rows = $requested->map(fn($a) => [
                'role'          => $a['role'],
                'permission_id' => $a['permission_id'],
                'created_at'    => now(),
                'updated_at'    => now(),
            ])->toArray();

            if (!empty($rows)) {
                DB::table('role_permissions')->insert($rows);
            }
        });

        DB::table('log_audit')->insert([
            'user_id'     => auth()->id(),
            'action'      => 'update',
            'entity_type' => 'role_permissions',
            'entity_id'   => 0,
            'notes'       => 'تحديث الصلاحيات لكل الأدوار (عملية دفعة)',
            'created_at'  => now(),
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'تم تحديث الصلاحيات بنجاح');
    }
}
