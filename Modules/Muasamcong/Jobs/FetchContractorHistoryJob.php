<?php

namespace Modules\Muasamcong\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Muasamcong\Models\ContractorSearchJob;
use Modules\Muasamcong\Services\ContractorHistoryService;
use Modules\Muasamcong\Services\ContractorSearchArchiveService;
use Throwable;

class FetchContractorHistoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public readonly int $searchJobId) {}

    public function handle(
        ContractorHistoryService $history,
        ContractorSearchArchiveService $archive
    ): void {
        $job = ContractorSearchJob::query()->findOrFail($this->searchJobId);

        $job->update([
            'status' => 'running',
            'progress' => 10,
            'status_message' => 'Đang kết nối Cổng Mua sắm công và tải lịch sử nhà thầu...',
            'started_at' => now(),
            'error_message' => null,
        ]);

        try {
            $data = $history->search(
                $job->contractor_code,
                $job->from_date?->format('Y-m-d'),
                $job->to_date?->format('Y-m-d')
            );

            $job->update([
                'status' => 'saving',
                'progress' => 85,
                'status_message' => 'Đã tải dữ liệu. Đang lưu lịch sử vào server...',
                'processed_pages' => (int) ($data['total_pages'] ?? 0),
                'total_pages' => (int) ($data['total_pages'] ?? 0),
            ]);

            $search = $archive->store(
                $job->contractor_code,
                $job->contractor_name,
                $job->from_date?->format('Y-m-d'),
                $job->to_date?->format('Y-m-d'),
                $data,
                $job->requested_by ? (int) $job->requested_by : null
            );

            $job->update([
                'status' => 'completed',
                'progress' => 100,
                'status_message' => 'Tra cứu hoàn tất. Dữ liệu đã được lưu trên server.',
                'contractor_search_id' => $search->id,
                'finished_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);

            $job->update([
                'status' => 'failed',
                'status_message' => 'Tra cứu không thành công.',
                'error_message' => class_basename($e).': '.$e->getMessage(),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }
}
