<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Collection;
use Modules\Request\Domain\Enums\ExportStatus;
use Modules\Request\Domain\Enums\RunStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestExportJob;
use Modules\Request\Models\RequestOutboxMessage;

final class RequestOperationsQuery
{
    public function failures(?int $limit = null): Collection
    {
        $limit = min(max($limit ?? (int) config('request.operations.page_size', 25), 1), (int) config('request.operations.max_page_size', 100));
        $perType = max(1, (int) ceil($limit / 3));

        $stage = InternalRequest::query()
            ->select(['request_instances.id', 'request_instances.public_id', 'request_instances.request_number', 'request_instances.lock_version', 'request_instances.current_run_id', 'request_instances.updated_at'])
            ->whereHas('currentRun', fn ($query) => $query->where('status', RunStatus::FailedActivation->value))
            ->latest('request_instances.updated_at')
            ->limit($perType)
            ->get()
            ->map(fn (InternalRequest $request): array => [
                'kind' => 'stage_activation',
                'public_id' => $request->public_id,
                'label' => $request->request_number ?: $request->public_id,
                'error_code' => 'failed_activation',
                'attempt_count' => (int) ($request->currentRun?->activation_retry_count ?? 0),
                'updated_at' => $request->updated_at,
                'deletable' => false,
            ]);

        $outbox = RequestOutboxMessage::query()
            ->whereNotNull('failed_at')
            ->latest('failed_at')
            ->limit($perType)
            ->get(['public_id', 'event_key', 'attempt_count', 'last_error_code', 'failed_at'])
            ->map(fn (RequestOutboxMessage $message): array => [
                'kind' => 'outbox_dispatch',
                'public_id' => $message->public_id,
                'label' => $message->event_key,
                'error_code' => $message->last_error_code,
                'attempt_count' => $message->attempt_count,
                'updated_at' => $message->failed_at,
                'deletable' => true,
            ]);

        $exports = RequestExportJob::query()
            ->where('status', ExportStatus::Failed->value)
            ->latest('updated_at')
            ->limit($perType)
            ->get(['public_id', 'format', 'attempt_count', 'last_error_code', 'updated_at'])
            ->map(fn (RequestExportJob $export): array => [
                'kind' => 'export_generation',
                'public_id' => $export->public_id,
                'label' => strtoupper($export->format).' · '.$export->public_id,
                'error_code' => $export->last_error_code,
                'attempt_count' => $export->attempt_count,
                'updated_at' => $export->updated_at,
                'deletable' => true,
            ]);

        return $stage
            ->concat($outbox)
            ->concat($exports)
            ->sortByDesc('updated_at')
            ->take($limit)
            ->values();
    }
}
