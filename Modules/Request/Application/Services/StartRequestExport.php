<?php

namespace Modules\Request\Application\Services;

use Illuminate\Validation\ValidationException;
use Modules\Request\Data\RequestExportPlan;
use Modules\Request\Domain\Enums\ExportStatus;
use Modules\Request\Jobs\GenerateRequestExport as GenerateRequestExportJob;
use Modules\Request\Models\RequestExportJob;

final readonly class StartRequestExport
{
    public function __construct(private GenerateRequestExport $generator) {}

    public function handle(mixed $user, RequestExportPlan $plan, string $format, string $idempotencyKey): RequestExportJob
    {
        if (! in_array($format, ['csv', 'xlsx'], true)) {
            throw ValidationException::withMessages(['format' => __('Request::request.exports.invalid_format')]);
        }

        $idempotencyKey = trim($idempotencyKey);

        if ($idempotencyKey === '' || strlen($idempotencyKey) > 191) {
            throw ValidationException::withMessages(['idempotency_key' => __('Request::request.exports.invalid_idempotency_key')]);
        }

        $userId = (int) $user->getAuthIdentifier();
        $hash = hash('sha256', $idempotencyKey);

        $export = RequestExportJob::query()->firstOrCreate(
            ['requested_by' => $userId, 'idempotency_key_hash' => $hash],
            [
                'filter_snapshot_json' => $plan->filters,
                'field_snapshot_json' => $plan->fields,
                'authorization_scope_json' => $plan->authorizationScope,
                'format' => $format,
                'status' => ExportStatus::Pending,
                'row_count' => $plan->authorizedRowCount,
            ],
        );

        if (! $export->wasRecentlyCreated) {
            return $export;
        }

        if ($plan->shouldQueue()) {
            GenerateRequestExportJob::dispatch($export->public_id);

            return $export;
        }

        return $this->generator->handle($export);
    }
}
