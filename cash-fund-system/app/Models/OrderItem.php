<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'category_id',
        'description',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderFund::class, 'order_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
