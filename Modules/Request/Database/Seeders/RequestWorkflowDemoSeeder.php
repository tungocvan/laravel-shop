<?php

namespace Modules\Request\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestTypeVersion;

class RequestWorkflowDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('RequestWorkflowDemoSeeder skipped: workflow demo data is local/testing only.');

            return;
        }

        $actorId = (int) config('request.settings.starter_template_actor_id', 0);
        $versions = RequestTypeVersion::query()
            ->orderBy('id')
            ->limit(6)
            ->get(['id', 'request_type_id']);

        if ($actorId <= 0 || $versions->isEmpty()) {
            $this->command?->warn('RequestWorkflowDemoSeeder skipped: starter actor or Request type versions are unavailable.');

            return;
        }

        $statuses = RequestStatus::cases();
        $now = now('UTC');
        $rowCount = 42;

        for ($index = 1; $index <= $rowCount; $index++) {
            $status = $statuses[($index - 1) % count($statuses)];
            $version = $versions[($index - 1) % $versions->count()];
            $createdAt = $now->copy()->subHours($index * 3);
            $request = InternalRequest::query()->firstOrNew([
                'request_number' => sprintf('REQ-DEMO-%04d', $index),
            ]);

            $request->forceFill([
                'request_type_id' => $version->request_type_id,
                'request_type_version_id' => $version->id,
                'requester_id' => $actorId,
                'status' => $status,
                'title_snapshot' => sprintf('Dữ liệu demo Request #%02d — %s', $index, $this->statusLabel($status)),
                'requester_snapshot_json' => [
                    'id' => $actorId,
                    'display_name' => 'Request Demo Requester',
                ],
                'lock_version' => 1,
                'submitted_at' => $status === RequestStatus::Draft ? null : $createdAt->copy()->addMinutes(20),
                'approved_at' => $status === RequestStatus::Approved ? $createdAt->copy()->addHours(2) : null,
                'rejected_at' => $status === RequestStatus::Rejected ? $createdAt->copy()->addHours(2) : null,
                'returned_at' => $status === RequestStatus::Returned ? $createdAt->copy()->addHours(2) : null,
                'cancelled_at' => $status === RequestStatus::Cancelled ? $createdAt->copy()->addHours(1) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt->copy()->addMinutes(30),
            ])->save();
        }

        $this->command?->info(sprintf(
            'RequestWorkflowDemoSeeder ready: %d deterministic requests across %d statuses for pagination/report/export testing.',
            $rowCount,
            count($statuses),
        ));
    }

    private function statusLabel(RequestStatus $status): string
    {
        return match ($status) {
            RequestStatus::Draft => 'Bản nháp',
            RequestStatus::Pending => 'Đang chờ duyệt',
            RequestStatus::Approved => 'Đã duyệt',
            RequestStatus::Rejected => 'Đã từ chối',
            RequestStatus::Returned => 'Đã trả lại',
            RequestStatus::Cancelled => 'Đã hủy',
        };
    }
}
