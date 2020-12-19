<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'nombres', 'email', 'password','cedula','telefono'
    ];

    protected $hidden = [
        'password',
    ];

    public function wallet(){
        return $this->hasOne(Wallet::class);
    }

    public function payments(){
        return $this->hasMany(Payment::class);
    }
}
