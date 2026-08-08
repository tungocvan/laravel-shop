<?php

namespace Modules\Muasamcong\Console\Commands;

use Illuminate\Console\Command;
use Modules\Muasamcong\Services\MuaSamCongService;

class TestPricingCommand extends Command
{
    protected $signature = 'msc:test
                            {--payload= : Đường dẫn file JSON payload}';

    protected $description = 'Kiểm tra API tra cứu giá của Cổng Mua sắm công';

    public function handle(MuaSamCongService $service): int
    {
        $payloadFile = $this->option('payload');

        if (! $payloadFile || ! is_file($payloadFile) || ! is_readable($payloadFile)) {
            $this->error('Truyền file JSON có thể đọc bằng --payload=/path/to/payload.json.');

            return Command::FAILURE;
        }

        $payload = json_decode((string) file_get_contents($payloadFile), true);

        if (! is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            $this->error('File payload không phải JSON hợp lệ.');

            return Command::FAILURE;
        }

        $keyword = data_get($payload, '0.query.0.keyWord');

        if (! is_string($keyword) || mb_strlen(trim($keyword)) < 2) {
            $this->error('Payload phải chứa từ khóa hợp lệ tại 0.query.0.keyWord.');

            return Command::FAILURE;
        }

        $result = $service->searchPricing($keyword);

        if (! $result['success']) {
            $this->error($result['message'] ?? 'API Mua sắm công trả lỗi.');

            return Command::FAILURE;
        }

        $total = data_get($result, 'data.total');
        $this->info('Kết nối thành công.');

        if ($total !== null) {
            $this->line('Tổng kết quả: '.$total);
        }

        return Command::SUCCESS;
    }
}
