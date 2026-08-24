<?php

namespace Modules\Request\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Request\Database\Factories\RequestCommentFactory;
use Modules\Request\Models\Concerns\HasPublicUlid;

class RequestComment extends Model
{
    use HasFactory, HasPublicUlid;

    public $timestamps = false;

    protected $fillable = ['request_instance_id', 'request_run_id', 'author_id', 'body', 'body_format', 'redacted_at', 'redacted_by', 'redaction_reason', 'created_at'];

    protected static function newFactory(): RequestCommentFactory
    {
        return RequestCommentFactory::new();
    }

    protected static function booted(): void
    {
        static::updating(function (self $comment): void {
            if ($comment->isDirty(['request_instance_id', 'request_run_id', 'author_id', 'body', 'body_format', 'created_at'])) {
                throw new \LogicException('Request comments are immutable.');
            }
        });
        static::deleting(fn (): never => throw new \LogicException('Request comments cannot be deleted.'));
    }

    protected function casts(): array
    {
        return ['author_id' => 'integer', 'redacted_at' => 'immutable_datetime', 'created_at' => 'immutable_datetime'];
    }

    public function requestInstance(): BelongsTo
    {
        return $this->belongsTo(InternalRequest::class, 'request_instance_id');
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(RequestRun::class, 'request_run_id');
    }
}
