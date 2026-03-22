<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PairInput extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'pair_id',
        'type',
        'symbol',
        'amount',
        'amount_usd',
        'price',
    ];

    protected $casts = [
        'amount' => 'float',
        'amount_usd' => 'float',
        'price' => 'float',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (PairInput $pairInput) {
            $pair = $pairInput->pair;
            if (!$pair) return;

            $symbol1 = strtoupper($pair->symbol_1);
            $isSymbol1 = strtoupper($pairInput->symbol) === $symbol1;
            $field = $isSymbol1 ? 'holdings_coin1' : 'holdings_coin2';

            $state = LiveState::firstOrCreate(
                ['pair_id' => $pair->id],
                ['pair_position' => 0, 'holdings_coin1' => 0, 'holdings_coin2' => 0, 'holdings_usdt' => 0, 'is_active' => true]
            );

            if ($pairInput->type === 'input') {
                $state->$field += $pairInput->amount;
            } else {
                $state->$field = max(0, $state->$field - $pairInput->amount);
            }

            $state->save();
        });
    }

    public function pair(): BelongsTo
    {
        return $this->belongsTo(Pair::class);
    }
}
