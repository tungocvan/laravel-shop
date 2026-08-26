<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Request\Models\RequestType;
use Modules\Request\Models\InternalRequest;

final class DeleteUnpublishedRequestType
{
    public function __construct(private readonly RequestAuditAppender $audit) {}

    public function handle(RequestType $type, int $actorId): void
    {
        DB::transaction(function () use ($type, $actorId): void {
            $type = RequestType::query()->lockForUpdate()->findOrFail($type->id);
            if ($type->current_published_version_id !== null || $type->versions()->whereNotNull('published_at')->exists() || InternalRequest::query()->where('request_type_id', $type->id)->exists()) {
                throw ValidationException::withMessages(['deleteType' => 'Chỉ có thể xóa loại đề nghị chưa từng phát hành và chưa có dữ liệu sử dụng.']);
            }
            $publicId = $type->public_id;
            $name = $type->name;
            $type->forceFill(['active_draft_version_id' => null])->save();
            $type->versions()->each(fn ($version) => $version->delete());
            $type->delete();
            $this->audit->append('request_type', $publicId, 'request.type.deleted.v1', $actorId, (string) Str::uuid(), ['name' => $name]);
        });
    }
}
