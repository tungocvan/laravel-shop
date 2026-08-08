<?php

namespace Modules\Facebook\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacebookPost extends Model
{
    use SoftDeletes;

    protected $table = 'facebook_posts';

    protected $fillable = [
        'facebook_page_id',
        'created_by',
        'title',
        'message',
        'post_type',
        'link_url',
        'status',
        'scheduled_at',
        'queued_at',
        'processing_at',
        'published_at',
        'failed_at',
        'facebook_post_id',
        'facebook_permalink',
        'attempts',
        'idempotency_key',
        'last_error_code',
        'last_error_subcode',
        'last_error_type',
        'last_error_message',
        'last_error_trace_id',
        'meta_response',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'queued_at' => 'datetime',
        'processing_at' => 'datetime',
        'published_at' => 'datetime',
        'failed_at' => 'datetime',
        'meta_response' => 'array',
        'attempts' => 'integer',
    ];

    public const TYPE_TEXT = 'text';

    public const TYPE_PHOTO = 'photo';

    public const TYPE_LINK = 'link';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Bản nháp',
        self::STATUS_SCHEDULED => 'Đã lên lịch',
        self::STATUS_QUEUED => 'Đang chờ queue',
        self::STATUS_PROCESSING => 'Đang đăng',
        self::STATUS_PUBLISHED => 'Đã đăng',
        self::STATUS_FAILED => 'Thất bại',
        self::STATUS_CANCELLED => 'Đã hủy',
    ];

    public const TYPES = [
        self::TYPE_TEXT => 'Nội dung',
        self::TYPE_PHOTO => 'Ảnh',
        self::TYPE_LINK => 'Liên kết',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'facebook_page_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(FacebookPostMedia::class)->orderBy('sort_order');
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now());
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getPostTypeLabelAttribute(): string
    {
        return self::TYPES[$this->post_type] ?? $this->post_type;
    }
}
