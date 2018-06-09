<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'url', 'title', 'cover', 'abstract', 'content', 'tags',
        'user_id', 'category_id',
    ];

    /**
     * @return array
     */
    public function getSummaryAttribute()
    {
        $share = $this->share;

        return [
            'id' => $this->id,
            'url' => $this->url,
            'title' => $this->title,
            'cover' => $this->cover,
            'category_id' => $this->category_id,
            'tags' => $this->tags,
            'abstract' => $this->abstract,
            'share_url' => $share ? action('ShareController@show', $share->code) : null,
            'share_count' => optional($share)->read_count,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function share()
    {
        return $this->hasOne(Share::class);
    }
}
