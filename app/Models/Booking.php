<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'date',
        'time',
        'duration',
        'time_end',
        'color',
        'court'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


}