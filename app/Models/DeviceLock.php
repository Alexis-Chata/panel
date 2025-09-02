<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceLock extends Model
{
    protected $fillable = ['game_session_id', 'user_id', 'device_hash'];

    public function session()
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
