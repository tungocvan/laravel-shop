<?php

namespace Modules\Request\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Request\Database\Factories\RequestAttachmentFactory;
use Modules\Request\Domain\Enums\AttachmentClassification;
use Modules\Request\Domain\Enums\AttachmentScanStatus;
use Modules\Request\Models\Concerns\HasPublicUlid;

class RequestAttachment extends Model
{
    use HasFactory, HasPublicUlid;

    public $timestamps = false;

    protected $fillable = ['request_instance_id', 'request_comment_id', 'payload_field_key', 'uploaded_by', 'storage_disk', 'storage_path', 'original_filename', 'generated_filename', 'mime_type', 'extension', 'size_bytes', 'checksum', 'classification', 'scan_status', 'scan_metadata_json', 'quarantined_at', 'removed_at', 'removed_by', 'removal_reason', 'created_at'];

    protected static function newFactory(): RequestAttachmentFactory
    {
        return RequestAttachmentFactory::new();
    }

    protected function casts(): array
    {
        return ['uploaded_by' => 'integer', 'size_bytes' => 'integer', 'classification' => AttachmentClassification::class, 'scan_status' => AttachmentScanStatus::class, 'scan_metadata_json' => 'array', 'quarantined_at' => 'immutable_datetime', 'removed_at' => 'immutable_datetime', 'created_at' => 'immutable_datetime'];
    }

    public function requestInstance(): BelongsTo
    {
        return $this->belongsTo(InternalRequest::class, 'request_instance_id');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(RequestComment::class, 'request_comment_id');
    }
}
