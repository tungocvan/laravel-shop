<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $table = 'wp_tags';

    protected $fillable = ['name', 'slug'];

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'wp_post_tag', 'tag_id', 'post_id');
    }
}