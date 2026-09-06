<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AccountActivated;
use App\Models\User;
use App\Models\UserPermission;
use App\Rules\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('employee_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        $permissions = DB::table('permissions')->orderBy('id')->get();

        // صلاحيات كل دور
        $rolePermissions = DB::table('role_permissions')
            ->get()
            ->groupBy('role')
            ->map(fn($rows) => $rows->pluck('permission_id')->map('intval')->toArray())
            ->toArray();

        // صلاحيات خاصة بكل مستخدم (granted/revoked)
        $userPermissions = DB::table('user_permissions')
            ->get()
            ->groupBy('user_id')
            ->map(fn($rows) => $rows->keyBy('permission_id'))
            ->toArray();

        return view('admin.users.index', compact('users', 'permissions', 'rolePermissions', 'userPermissions'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:100',
            'national_id'     => [
                'required',
                'digits_between:5,20',
                Rule::unique('users', 'national_id'),
            ],
            'employee_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'employee_number'),
            ],
            'phone'           => 'nullable|string|max:20',
            'position'        => 'nullable|string|max:100',
            'username'        => 'required|string|max:100|unique:users,username',
            // Pass the new user's name+username so StrongPassword checks them,
            // not the currently-logged-in admin's credentials.
            'password'        => ['required', 'confirmed', new StrongPassword(
                $request->input('name'),
                $request->input('username'),
            )],
            'role'            => 'required|in:admin,investor,client',
        ], [
            'national_id.digits_between' => 'رقم الهوية الوطنية يجب أن يحتوي أرقاماً فقط (5-20 خانة)',
            'national_id.unique'         => 'رقم الهوية هذا مسجّل بالفعل لمستخدم آخر',
            'employee_number.unique'     => 'الرقم الوظيفي هذا مستخدم بالفعل',
        ]);

        $validated['is_active'] = true;

        $user = User::create($validated);

        $this->logAudit('create', $user->id);

        return redirect()->route('admin.users.index')
            ->with('success', 'تم إنشاء المستخدم بنجاح');
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            // Pass the target user's name+username so the check runs against
            // their credentials, not the admin who is performing the reset.
            'password' => ['required', 'confirmed', new StrongPassword(
                $user->name,
                $user->username,
            )],
        ]);

        $user->password = $validated['password'];
        $user->save();

        $this->logAudit('update', $user->id, 'reset_password');

        return redirect()->route('admin.users.index')
            ->with('success', 'تم إعادة تعيين كلمة المرور بنجاح');
    }

    public function updatePermissions(Request $request, User $user)
    {
        $allPermissions = DB::table('permissions')->get();

        // الصلاحيات القادمة من الفورم (مصفوفة من permission_id)
        $submitted = collect($request->input('permissions', []))->map('intval')->toArray();

        DB::transaction(function () use ($user, $allPermissions, $submitted) {
            // احذف كل صلاحيات المستخدم الخاصة أولاً
            UserPermission::where('user_id', $user->id)->delete();

            // صلاحيات الدور الأصلية
            $rolePermIds = DB::table('role_permissions')
                ->where('role', $user->role)
                ->pluck('permission_id')
                ->map('intval')
                ->toArray();

            $rows = [];
            foreach ($allPermissions as $perm) {
                $inRole      = in_array($perm->id, $rolePermIds);
                $inSubmitted = in_array($perm->id, $submitted);

                if ($inRole && !$inSubmitted) {
                    // الدور يعطيها لكن المستخدم سُحبت منه
                    $rows[] = [
                        'user_id'       => $user->id,
                        'permission_id' => $perm->id,
                        'granted'       => false,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                } elseif (!$inRole && $inSubmitted) {
                    // الدور لا يعطيها لكن المستخدم منحت له
                    $rows[] = [
                        'user_id'       => $user->id,
                        'permission_id' => $perm->id,
                        'granted'       => true,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                }
                // إذا كانا متطابقين (كلاهما true أو كلاهما false) → لا نسجّل شيء (default behavior)
            }

            if (!empty($rows)) {
                DB::table('user_permissions')->insert($rows);
            }
        });

        $this->logAudit('update', $user->id, 'update_user_permissions');

        return redirect()->route('admin.users.index')
            ->with('success', 'تم تحديث صلاحيات المستخدم بنجاح');
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

        // FIX #13: capture the current state BEFORE the update so the flag
        // check below is unambiguous (is_active will reflect the NEW value
        // after update(), which is correct but easy to misread without this).
        $wasActive = $user->is_active;

        $user->update(['is_active' => !$wasActive]);

        $this->logAudit('update', $user->id, 'toggle_status');

        // Send activation email only when transitioning inactive → active.
        if (!$wasActive && $user->email) {
            try {
                Mail::to($user->email)->send(new AccountActivated($user));
            } catch (\Throwable $e) {
                // Log the failure but don't interrupt the admin's workflow
                logger()->error('Account activation email failed for user ' . $user->id . ': ' . $e->getMessage());
            }
        }

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

    public function suggestPassword(): \Illuminate\Http\JsonResponse
    {
        $password = $this->generateStrongPassword();
        return response()->json(['password' => $password]);
    }

    private function generateStrongPassword(int $length = 16): string
    {
        $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lowercase = 'abcdefghjkmnpqrstuvwxyz';
        $numbers = '23456789';
        $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';

        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        return str_split($password) ? implode('', $this->secureShuffle(str_split($password))) : $password;
    }

    private function secureShuffle(array $array): array
    {
        $count = count($array);
        for ($i = $count - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$array[$i], $array[$j]] = [$array[$j], $array[$i]];
        }
        return $array;
    }
}
