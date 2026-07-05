<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyMovement extends Model
{
    protected $table = 'daily_movements';

    protected $fillable = [
        'order_id',
        'movement_type',
        'amount',
        'balance_after',
        'movement_date',
        'executed_at',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'balance_after' => 'decimal:2',
        'movement_date' => 'date',
        'executed_at'   => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderFund::class, 'order_id');
    }
}
