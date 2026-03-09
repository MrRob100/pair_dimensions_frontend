<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LivePortfolioSnapshot extends Model
{
    const CREATED_AT = 'snapshot_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'pair_id',
        'portfolio_value_usdt',
        'coin1_price',
        'coin2_price',
        'pair_position',
        'snapshot_at',
    ];

    protected $casts = [
        'portfolio_value_usdt' => 'float',
        'coin1_price' => 'float',
        'coin2_price' => 'float',
        'pair_position' => 'integer',
        'snapshot_at' => 'datetime',
    ];

    public function pair(): BelongsTo
    {
        return $this->belongsTo(Pair::class);
    }
}
