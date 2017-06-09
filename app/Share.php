<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Share extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code', 'readCount',
    ];

    public function link()
    {
        return $this->belongsTo('App\Link');
    }
}
