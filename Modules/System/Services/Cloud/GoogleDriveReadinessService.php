<?php

declare(strict_types=1);

namespace Modules\System\Services\Cloud;

use Illuminate\Support\Facades\Http;
use Throwable;

class GoogleDriveReadinessService
{
    public function __construct(private readonly GoogleDriveConnectionService $drive) {}

    public function check(): array
    {
        $status = $this->drive->status();

        if (! ($status['connected'] ?? false)) {
            return $this->result(false, false, 'not_connected', 'Google Drive chưa được kết nối.');
        }

        try {
            $response = Http::withToken($this->drive->accessToken())
                ->acceptJson()
                ->timeout(20)
                ->get('https://www.googleapis.com/drive/v3/files', [
                    'pageSize' => 1,
                    'fields' => 'files(id,name)',
                    'spaces' => 'drive',
                ]);
        } catch (Throwable) {
            return $this->result(true, false, 'connection_error', 'Không thể kết nối Google Drive API. Hãy kiểm tra mạng và thử lại.');
        }

        if ($response->successful()) {
            return $this->result(true, true, 'ready', 'Google Drive API sẵn sàng.');
        }

        $payload = $response->json();
        $reason = (string) data_get($payload, 'error.details.0.reason', data_get($payload, 'error.errors.0.reason', ''));
        $message = (string) data_get($payload, 'error.message', '');

        if ($response->status() === 403 && in_array($reason, ['ACCESS_TOKEN_SCOPE_INSUFFICIENT', 'insufficientPermissions'], true)) {
            return $this->result(
                true,
                false,
                'scope_insufficient',
                'Tài khoản đã kết nối nhưng token OAuth thiếu quyền Google Drive. Cần cấp lại quyền một lần.',
                403,
                $reason,
            );
        }

        return $this->result(
            true,
            false,
            'api_error',
            $message !== '' ? 'Google Drive API từ chối yêu cầu: '.$message : 'Google Drive API chưa sẵn sàng.',
            $response->status(),
            $reason,
        );
    }

    private function result(
        bool $connected,
        bool $ready,
        string $code,
        string $message,
        ?int $httpStatus = null,
        string $reason = '',
    ): array {
        return compact('connected', 'ready', 'code', 'message', 'httpStatus', 'reason');
    }
}
