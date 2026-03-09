<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveTrade extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'pair_id',
        'trade_type',
        'side',
        'symbol',
        'quantity',
        'requested_quantity',
        'fill_price',
        'usdt_value',
        'binance_order_id',
        'pair_position_before',
        'pair_position_after',
    ];

    protected $casts = [
        'quantity' => 'float',
        'requested_quantity' => 'float',
        'fill_price' => 'float',
        'usdt_value' => 'float',
        'binance_order_id' => 'integer',
        'pair_position_before' => 'integer',
        'pair_position_after' => 'integer',
        'created_at' => 'datetime',
    ];

    public function pair(): BelongsTo
    {
        return $this->belongsTo(Pair::class);
    }
}
