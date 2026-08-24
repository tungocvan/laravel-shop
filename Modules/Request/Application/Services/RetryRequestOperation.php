<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Request\Domain\Enums\ExportStatus;
use Modules\Request\Domain\Enums\RunStatus;
use Modules\Request\Jobs\GenerateRequestExport as GenerateRequestExportJob;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestExportJob;
use Modules\Request\Models\RequestOutboxMessage;

final readonly class RetryRequestOperation
{
    public function __construct(
        private RetryStageActivation $stageActivation,
        private RequestOutboxDispatcher $outbox,
        private RequestAuditAppender $audit,
    ) {}

    public function handle(string $kind, string $publicId, int $actorId): void
    {
        if (! in_array($kind, (array) config('request.operations.retry_allowlist', []), true)) {
            throw ValidationException::withMessages(['operation' => ['operation_not_allowlisted']]);
        }

        match ($kind) {
            'stage_activation' => $this->retryStageActivation($publicId, $actorId),
            'outbox_dispatch' => $this->retryOutbox($publicId, $actorId),
            'export_generation' => $this->retryExport($publicId, $actorId),
            default => throw ValidationException::withMessages(['operation' => ['operation_not_allowlisted']]),
        };
    }

    private function retryStageActivation(string $publicId, int $actorId): void
    {
        $request = InternalRequest::query()->where('public_id', $publicId)->with('currentRun')->firstOrFail();

        if ($request->currentRun?->status !== RunStatus::FailedActivation) {
            return;
        }

        $key = 'operation-stage-'.$request->public_id.'-'.$request->lock_version;
        $this->stageActivation->handle($request, $actorId, $request->lock_version, $key);
    }

    private function retryOutbox(string $publicId, int $actorId): void
    {
        $message = RequestOutboxMessage::query()->where('public_id', $publicId)->firstOrFail();

        if ($message->dispatched_at !== null) {
            return;
        }

        if ($message->failed_at !== null) {
            $message->forceFill([
                'failed_at' => null,
                'available_at' => now('UTC'),
                'last_error_code' => null,
                'last_error_at' => null,
            ])->save();
        }

        $this->outbox->dispatchOne($message->public_id);
        $this->audit->append('request_outbox', $message->public_id, 'request.operation.outbox_retried.v1', $actorId, (string) Str::uuid());
    }

    private function retryExport(string $publicId, int $actorId): void
    {
        $export = RequestExportJob::query()->where('public_id', $publicId)->firstOrFail();

        if ($export->status === ExportStatus::Ready || $export->status === ExportStatus::Pending || $export->status === ExportStatus::Processing) {
            return;
        }

        if ($export->status !== ExportStatus::Failed) {
            throw ValidationException::withMessages(['operation' => ['export_not_retryable']]);
        }

        $export->forceFill([
            'status' => ExportStatus::Pending,
            'last_error_code' => null,
        ])->save();

        GenerateRequestExportJob::dispatch($export->public_id);
        $this->audit->append('request_export', $export->public_id, 'request.operation.export_retried.v1', $actorId, (string) Str::uuid());
    }
}
