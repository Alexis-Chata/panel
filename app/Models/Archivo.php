<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Archivo extends Model
{

    protected $fillable = ['url', 'game_session_id'];
    public function gameSession(){return $this->belongsTo(GameSession::class);}

}
