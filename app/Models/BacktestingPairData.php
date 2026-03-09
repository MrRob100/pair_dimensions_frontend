<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BacktestingPairData extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'backtesting_result_id',
        'pair_value_usd',
        'day',
    ];

    protected $casts = [
        'pair_value_usd' => 'float',
        'day' => 'integer',
    ];

    public function backtestingResult(): BelongsTo
    {
        return $this->belongsTo(BacktestingResult::class);
    }
}
