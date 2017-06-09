<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'url', 'title', 'cover', 'abstract', 'category_id', 'content', 'tags',
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function share()
    {
        return $this->hasOne('App\Share');
    }
}
