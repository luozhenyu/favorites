<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'openid', 'session_key', 'third_session',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'openid', 'session_key',
    ];


    public function links()
    {
        return $this->hasMany('App\Link');
    }

    public function categories()
    {
        return $this->hasMany('App\Category');
    }
}
