<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveState extends Model
{
    protected $table = 'live_state';
    const CREATED_AT = null;

    protected $fillable = [
        'pair_id',
        'pair_position',
        'holdings_coin1',
        'holdings_coin2',
        'holdings_usdt',
        'pair_ma_length',
        'pair_threshold',
        'is_active',
    ];

    protected $casts = [
        'pair_position' => 'integer',
        'holdings_coin1' => 'float',
        'holdings_coin2' => 'float',
        'holdings_usdt' => 'float',
        'pair_ma_length' => 'integer',
        'pair_threshold' => 'float',
        'is_active' => 'boolean',
        'updated_at' => 'datetime',
    ];

    public function pair(): BelongsTo
    {
        return $this->belongsTo(Pair::class);
    }
}
