<?php

namespace Modules\Muasamcong\Console\Commands;

use Illuminate\Console\Command;
use Modules\Muasamcong\Services\MuaSamCongService;

class TestHsmtCommand extends Command
{
    protected $signature = 'msc:test-hsmt 
        {keyword : Từ khóa gói thầu} 
        {range : Khoảng ngày dạng YYYY-MM-DD:YYYY-MM-DD}';

    protected $description = 'Test API tra cứu hồ sơ mời thầu (HSMT) trên muasamcong.mpi.gov.vn';

    public function handle(MuaSamCongService $service): int
    {
        $keyword = $this->argument('keyword');
        $range = $this->argument('range');

        // Tách range
        if (! str_contains($range, ':')) {
            $this->error('❌ Sai format range. VD: 2025-11-20:2025-11-21');

            return Command::FAILURE;
        }

        [$from, $to] = explode(':', $range);

        if (! strtotime($from) || ! strtotime($to) || $to < $from) {
            $this->error('❌ Khoảng ngày không hợp lệ.');

            return Command::FAILURE;
        }

        $this->info('⏳ Đang gọi API HSMT...');

        $result = $service->searchHsmt((string) $keyword, $from, $to);

        $status = $result['status'] ?? 0;
        $data = $result['data'] ?? null;
        // dd($data['page']['content']);
        $this->info("HTTP Status: {$status}");

        if (! ($result['success'] ?? false)) {
            $this->error('❌ '.($result['message'] ?? 'Lỗi khi gọi API'));

            return Command::FAILURE;
        }

        // In ra số lượng kết quả
        $total = $data['total'] ?? 0;
        $this->info("✅ Tổng kết quả tìm thấy: {$total}");

        // Hiển thị danh sách ngắn gọn
        if (! empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $this->line('-----------------------------------------------------');
                $this->info('📌 Gói thầu: '.($item['bidName'][0] ?? 'N/A'));
                $this->line('Mã TBMT: '.($item['notifyNo'] ?? 'N/A'));
                $this->line('Ngày đăng tải: '.($item['publicDate'] ?? 'N/A'));
                $this->line('Thời điểm đóng thầu: '.($item['bidOpenDate'] ?? 'N/A'));
                $this->line('Mã Bên mời thầu: '.($item['investorCode'] ?? 'N/A'));
                $this->line('Bên mời thầu: '.($item['investorName'] ?? 'N/A'));
                $this->line('Địa điểm: '.($item['locations'][0]['districtName'] ?? '').' - '.($item['locations'][0]['provName'] ?? ''));
            }
        } else {
            $this->warn('⚠️ Không có dữ liệu trả về!');
        }

        return Command::SUCCESS;
    }
}
