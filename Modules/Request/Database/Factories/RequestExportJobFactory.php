<?php

namespace Modules\Request\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Request\Domain\Enums\ExportStatus;
use Modules\Request\Models\RequestExportJob;

class RequestExportJobFactory extends Factory
{
    protected $model = RequestExportJob::class;

    public function definition(): array
    {
        return ['requested_by' => 1, 'filter_snapshot_json' => [], 'field_snapshot_json' => [], 'authorization_scope_json' => [], 'format' => 'csv', 'status' => ExportStatus::Pending, 'idempotency_key_hash' => hash('sha256', fake()->uuid())];
    }
}
