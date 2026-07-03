<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'username' => 'required|string|max:100|unique:users,username',
            'password' => 'required|confirmed|min:8',
            'role'     => 'required|in:admin,investor,client',
        ]);

        $validated['is_active'] = true;

        $user = User::create($validated);

        $this->logAudit('create', $user->id);

        return redirect()->route('admin.users.index')
            ->with('success', 'تم إنشاء المستخدم بنجاح');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'role'     => 'required|in:admin,investor,client',
        ]);

        if ($user->id === auth()->id() && $validated['role'] !== 'admin') {
            return back()->withErrors([
                'role' => 'لا يمكنك تغيير دورك الخاص لتفادي فقدان الوصول.',
            ])->withInput();
        }

        $user->update($validated);

        $this->logAudit('update', $user->id);

        return redirect()->route('admin.users.index')
            ->with('success', 'تم تعديل بيانات المستخدم بنجاح');
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|confirmed|min:8',
        ]);

        $user->password = $validated['password'];
        $user->save();

        $this->logAudit('update', $user->id, 'reset_password');

        return redirect()->route('admin.users.index')
            ->with('success', 'تم إعادة تعيين كلمة المرور بنجاح');
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors([
                'status' => 'لا يمكنك إيقاف حسابك الخاص.',
            ]);
        }

        if ($user->is_active) {
            $activeAdminsCount = User::where('role', 'admin')
                ->where('is_active', true)
                ->count();

            if ($user->role === 'admin' && $activeAdminsCount <= 1) {
                return back()->withErrors([
                    'status' => 'لا يمكن إيقاف آخر مدير نشط في النظام.',
                ]);
            }
        }

        $user->update(['is_active' => !$user->is_active]);

        $this->logAudit('update', $user->id, 'toggle_status');

        return redirect()->route('admin.users.index')
            ->with('success', $user->is_active
                ? 'تم تفعيل حساب المستخدم بنجاح'
                : 'تم إيقاف حساب المستخدم بنجاح');
    }

    private function logAudit(string $action, int $entityId, ?string $notes = null): void
    {
        DB::table('log_audit')->insert([
            'user_id'     => auth()->id(),
            'action'      => $action,
            'entity_type' => 'users',
            'entity_id'   => $entityId,
            'notes'       => $notes,
            'created_at'  => now(),
        ]);
    }
}
