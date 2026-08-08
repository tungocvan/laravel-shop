<?php

namespace Modules\Facebook\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacebookPostMedia extends Model
{
    protected $table = 'facebook_post_media';

    protected $fillable = [
        'facebook_post_id',
        'media_type',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'sort_order',
        'facebook_media_id',
        'status',
        'last_error_message',
    ];

    protected $casts = [
        'size' => 'integer',
        'sort_order' => 'integer',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_UPLOADED = 'uploaded';

    public const STATUS_FAILED = 'failed';

    public function post(): BelongsTo
    {
        return $this->belongsTo(FacebookPost::class, 'facebook_post_id');
    }
}
