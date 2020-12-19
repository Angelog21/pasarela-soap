<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'wallet_id', 'token', 'session_id','descripcion', 'monto'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function wallet(){
        return $this->belongsTo(Wallet::class);
    }
}
