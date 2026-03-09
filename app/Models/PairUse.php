<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PairUse extends Model
{
    protected $table = 'pair_use';

    public $timestamps = false;

    protected $fillable = [
        'pair_id',
        'status',
        'date_started',
        'date_finished',
    ];

    protected $casts = [
        'date_started' => 'datetime',
        'date_finished' => 'datetime',
    ];

    public function pair(): BelongsTo
    {
        return $this->belongsTo(Pair::class);
    }
}
