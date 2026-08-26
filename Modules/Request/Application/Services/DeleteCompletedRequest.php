<?php
namespace Modules\Request\Application\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Models\InternalRequest;
final class DeleteCompletedRequest
{
    public function __construct(private readonly RequestAuditAppender $audit) {}
    public function handle(string $publicId, int $actorId): void
    {
        $files = DB::transaction(function () use ($publicId, $actorId): array {
            $request = InternalRequest::query()->where('public_id', $publicId)->lockForUpdate()->firstOrFail();
            if (! in_array($request->status, [RequestStatus::Approved, RequestStatus::Rejected, RequestStatus::Cancelled], true)) throw ValidationException::withMessages(['request' => 'Chỉ có thể xóa đề nghị đã kết thúc.']);
            $id = $request->id; $runIds = DB::table('request_runs')->where('request_instance_id', $id)->pluck('id'); $taskIds = DB::table('request_tasks')->whereIn('request_run_id', $runIds)->pluck('id');
            $files = DB::table('request_attachments')->where('request_instance_id', $id)->get(['storage_disk', 'storage_path'])->map(fn ($f) => [$f->storage_disk, $f->storage_path])->all();
            DB::table('request_instances')->where('id', $id)->update(['current_run_id' => null, 'current_payload_revision_id' => null]);
            // Preserve the immutable audit trail while releasing its restrictive
            // runtime FK before the business aggregate is permanently removed.
            DB::table('request_audit_events')->where('request_instance_id', $id)->update(['request_instance_id' => null]);
            DB::table('request_decisions')->where('request_instance_id', $id)->delete();
            DB::table('request_task_candidates')->whereIn('request_task_id', $taskIds)->delete();
            DB::table('request_tasks')->whereIn('id', $taskIds)->update(['replaces_task_id' => null, 'replaced_by_task_id' => null]);
            DB::table('request_tasks')->whereIn('id', $taskIds)->delete();
            DB::table('request_attachments')->where('request_instance_id', $id)->delete(); DB::table('request_comments')->where('request_instance_id', $id)->delete();
            DB::table('request_runs')->whereIn('id', $runIds)->delete(); DB::table('request_payload_revisions')->where('request_instance_id', $id)->delete(); DB::table('request_instances')->where('id', $id)->delete();
            $this->audit->append('request', $publicId, 'request.deleted.v1', $actorId, (string) Str::uuid(), ['terminal_status' => $request->status->value]); return $files;
        });
        foreach ($files as [$disk, $path]) if ($disk !== 'public' && str_starts_with($path, 'request/attachments/') && ! str_contains($path, '..')) Storage::disk($disk)->delete($path);
    }
}
