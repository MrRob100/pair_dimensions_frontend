<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coin extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $primaryKey = 'symbol';
    protected $keyType = 'string';

    protected $fillable = [
        'symbol',
        'date_added',
    ];

    protected $casts = [
        'date_added' => 'datetime',
    ];
}
