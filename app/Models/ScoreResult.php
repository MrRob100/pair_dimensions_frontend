<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoreResult extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'score',
        'date_calculated',
        'interval',
        'pair_id',
    ];

    protected $casts = [
        'score' => 'float',
        'date_calculated' => 'datetime',
    ];

    public function pair(): BelongsTo
    {
        return $this->belongsTo(Pair::class);
    }
}
