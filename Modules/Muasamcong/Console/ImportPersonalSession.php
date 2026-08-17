<?php

namespace Modules\Muasamcong\Console;

use Illuminate\Console\Command;
use Modules\Muasamcong\Services\ContractorHistoryService;
use Modules\Muasamcong\Services\PersonalSessionService;
use Throwable;

class ImportPersonalSession extends Command
{
    protected $signature = 'msc:import-personal-session
        {--stdin : Read the Cookie header value from STDIN}
        {--test : Verify the imported Personal Page session immediately}';

    protected $description = 'Import the Muasamcong Personal Page session cookie into encrypted database storage';

    public function handle(PersonalSessionService $sessions, ContractorHistoryService $history): int
    {
        if (! $this->option('stdin')) {
            $this->components->error('Vì lý do bảo mật, lệnh này chỉ nhận Cookie qua STDIN. Dùng --stdin.');

            return self::FAILURE;
        }

        $cookie = trim((string) stream_get_contents(STDIN));

        if ($cookie === '') {
            $this->components->error('Không nhận được Cookie từ STDIN.');

            return self::FAILURE;
        }

        if (! str_contains($cookie, 'JSESSIONID=')) {
            $this->components->error('Cookie không có JSESSIONID. Hãy đăng nhập Mua sắm công trước khi cập nhật session.');

            return self::FAILURE;
        }

        try {
            $sessions->save($cookie);
            $this->components->info('Đã lưu Personal Page Session vào database ở dạng mã hóa.');

            if (! $this->option('test')) {
                return self::SUCCESS;
            }

            $result = $history->testSession();
            $sessions->markVerified();
            $this->components->info('Session hợp lệ. API lịch sử nhà thầu phản hồi thành công ('.$result['total'].' gói).');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $sessions->markFailed($e->getMessage());
            $this->components->error('Không thể xác minh Personal Page Session. Hãy đăng nhập lại và cập nhật session mới.');

            return self::FAILURE;
        }
    }
}
