<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BacktestingResult extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'pair_id',
        'ma_length',
        'thresh',
        'profit_percent',
        'profit_percent_against_wbw',
        'trade_count',
        'date_tested',
    ];

    protected $casts = [
        'profit_percent' => 'float',
        'profit_percent_against_wbw' => 'float',
        'ma_length' => 'integer',
        'thresh' => 'float',
        'trade_count' => 'integer',
        'date_tested' => 'date',
    ];

    public function pair(): BelongsTo
    {
        return $this->belongsTo(Pair::class);
    }

    public function backtestingPairData(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BacktestingPairData::class);
    }
}
