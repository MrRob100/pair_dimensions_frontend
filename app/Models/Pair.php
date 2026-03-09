<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pair extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'symbol_1',
        'symbol_2',
        'type',
        'date_added',
    ];

    protected $casts = [
        'date_added' => 'datetime',
    ];

    public function backtestingResults(): HasMany
    {
        return $this->hasMany(BacktestingResult::class);
    }

    public function scoreResults(): HasMany
    {
        return $this->hasMany(ScoreResult::class);
    }

    public function liveState(): HasOne
    {
        return $this->hasOne(LiveState::class);
    }

    public function liveTrades(): HasMany
    {
        return $this->hasMany(LiveTrade::class);
    }

    public function livePortfolioSnapshots(): HasMany
    {
        return $this->hasMany(LivePortfolioSnapshot::class);
    }

    public function pairUses(): HasMany
    {
        return $this->hasMany(PairUse::class);
    }

    public function getFullSymbolAttribute(): string
    {
        return $this->symbol_1 . '/' . $this->symbol_2;
    }
}
