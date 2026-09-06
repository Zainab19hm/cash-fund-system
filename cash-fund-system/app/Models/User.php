<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'name',
        'email',
        'national_id',
        'employee_number',
        'phone',
        'position',
        'username',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function setPasswordAttribute($value): void
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('Password cannot be empty');
        }
        $this->attributes['password'] = Hash::make($value);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function userPermissions(): HasMany
    {
        return $this->hasMany(\App\Models\UserPermission::class);
    }

    /**
     * التحقق إذا كان المستخدم يملك صلاحية معينة.
     * الأولوية: user_permissions (granted/revoked) ثم role_permissions.
     */
    public function hasPermission(string $key): bool
    {
        $permission = DB::table('permissions')->where('key', $key)->first();
        if (!$permission) {
            return false;
        }

        // تحقق من صلاحية خاصة بالمستخدم أولاً
        $userPerm = DB::table('user_permissions')
            ->where('user_id', $this->id)
            ->where('permission_id', $permission->id)
            ->first();

        if ($userPerm !== null) {
            return (bool) $userPerm->granted;
        }

        // الرجوع لصلاحيات الدور
        return DB::table('role_permissions')
            ->where('role', $this->role)
            ->where('permission_id', $permission->id)
            ->exists();
    }

    public function createdOrders(): HasMany
    {
        return $this->hasMany(OrderFund::class, 'created_by');
    }

    public function approvedOrders(): HasMany
    {
        return $this->hasMany(OrderFund::class, 'approved_by');
    }

    public function executedOrders(): HasMany
    {
        return $this->hasMany(OrderFund::class, 'executed_by');
    }

    public function rejectedOrders(): HasMany
    {
        return $this->hasMany(OrderFund::class, 'rejected_by');
    }

    public function logEntries(): HasMany
    {
        return $this->hasMany(LogAudit::class);
    }
}
