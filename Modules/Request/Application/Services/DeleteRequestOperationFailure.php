<?php
namespace Modules\Request\Application\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Request\Domain\Enums\ExportStatus;
use Modules\Request\Models\RequestExportJob;
use Modules\Request\Models\RequestNotificationDelivery;
use Modules\Request\Models\RequestOutboxMessage;

final class DeleteRequestOperationFailure
{
    public function __construct(private readonly RequestAuditAppender $audit) {}
    public function handle(string $kind, string $publicId, int $actorId): void
    {
        $stored = DB::transaction(function () use ($kind, $publicId, $actorId): ?array {
            if ($kind === 'outbox_dispatch') {
                $record = RequestOutboxMessage::query()->where('public_id', $publicId)->lockForUpdate()->firstOrFail();
                if ($record->failed_at === null) throw ValidationException::withMessages(['operation' => 'Chỉ xóa được outbox đã thất bại.']);
                RequestNotificationDelivery::query()->where('outbox_public_id', $publicId)->delete();
                $record->delete(); $stored = null;
            } elseif ($kind === 'export_generation') {
                $record = RequestExportJob::query()->where('public_id', $publicId)->lockForUpdate()->firstOrFail();
                if ($record->status !== ExportStatus::Failed) throw ValidationException::withMessages(['operation' => 'Chỉ xóa được tác vụ xuất đã thất bại.']);
                $stored = $record->storage_disk && $record->storage_path ? [$record->storage_disk, $record->storage_path] : null;
                $record->delete();
            } else throw ValidationException::withMessages(['operation' => 'Lỗi kích hoạt luồng phải được phục hồi, không được xóa riêng.']);
            $this->audit->append('request_operation', $publicId, 'request.operation.deleted.v1', $actorId, (string) Str::uuid(), ['kind' => $kind]);
            return $stored;
        });
        if ($stored && ! str_contains($stored[1], '..')) Storage::disk($stored[0])->delete($stored[1]);
    }
}
