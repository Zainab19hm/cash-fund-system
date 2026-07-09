<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'name',
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
