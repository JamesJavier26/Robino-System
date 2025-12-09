<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Queue extends Model
{
    use HasFactory;

    protected $fillable = ['player_id', 'court', 'date', 'position', 'status'];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    protected static function booted()
    {
        static::creating(function ($queue) {
            $maxPosition = self::where('date', $queue->date)->max('position') ?? 0;
            $queue->position = $maxPosition + 1;
        });
    }
}
