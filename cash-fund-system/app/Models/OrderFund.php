<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderFund extends Model
{
    protected $table = 'orders_fund';

    protected $fillable = [
        'order_number',
        'type',
        'amount',
        'description',
        'payer_name',
        'status',
        'order_date',
        'created_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejection_reason',
        'executed_by',
        'executed_at',
        'cancelled_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'order_date' => 'date',
        'approved_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'order_id');
    }
}
