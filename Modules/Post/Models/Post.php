<?php

namespace Modules\Post\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;

    protected $table = 'wp_posts';

    protected $fillable = [
        'name', 'slug', 'summary', 'content', 'thumbnail',
        'is_featured', 'status', 'views', 'user_id', 'published_at',
        'meta_title', 'meta_description',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'views' => 'integer',
        'published_at' => 'datetime',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_post', 'post_id', 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'wp_post_tag', 'post_id', 'tag_id');
    }

    public function author()
    {
        return $this->user();
    }
}
