<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchRecords extends Model
{
    protected $fillable = [
        'match_id',
        'date',
        'court',
        'players',
    ];

    protected $casts = [
        'players' => 'array',
    ];
}
