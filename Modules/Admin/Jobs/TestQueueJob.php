<?php

namespace Modules\Admin\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class TestQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

   // Modules/Admin/Jobs/TestQueueJob.php
    public function handle(): void
    {
        // Tạm thời dùng Log để kiểm tra thay vì Cache
        \Illuminate\Support\Facades\Log::info('Job đã chạy thành công!');
        \Illuminate\Support\Facades\Cache::put('queue_test_status', 'Success: ' . now(), 300);
        // 1. Khi Worker vừa nhặt Job lên
        Cache::put('queue_test_status', 'Processing...', 300);

        // Giả lập xử lý trong 2 giây để Admin kịp thấy trạng thái thay đổi
        sleep(2);

        // 2. Khi hoàn thành
        Cache::put('queue_test_status', 'Success: Done at ' . now()->format('H:i:s'), 300);
    }
}
