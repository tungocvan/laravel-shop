<?php

namespace Modules\Request\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Request\Database\Factories\RequestExportJobFactory;
use Modules\Request\Domain\Enums\ExportStatus;
use Modules\Request\Models\Concerns\HasPublicUlid;

class RequestExportJob extends Model
{
    use HasFactory, HasPublicUlid;

    protected $fillable = ['requested_by', 'filter_snapshot_json', 'field_snapshot_json', 'authorization_scope_json', 'format', 'status', 'row_count', 'checksum', 'storage_disk', 'storage_path', 'expires_at', 'attempt_count', 'last_error_code', 'idempotency_key_hash'];

    protected static function newFactory(): RequestExportJobFactory
    {
        return RequestExportJobFactory::new();
    }

    protected function casts(): array
    {
        return ['filter_snapshot_json' => 'array', 'field_snapshot_json' => 'array', 'authorization_scope_json' => 'array', 'status' => ExportStatus::class, 'row_count' => 'integer', 'expires_at' => 'immutable_datetime', 'attempt_count' => 'integer'];
    }
}
