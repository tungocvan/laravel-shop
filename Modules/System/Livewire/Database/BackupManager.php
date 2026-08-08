<?php

declare(strict_types=1);

namespace Modules\System\Livewire\Database;

use Exception;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Http;
use Modules\System\Jobs\SendDatabaseBackupEmail;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\DatabaseService;

class BackupManager extends Component
{
    use AuthorizesSystemActions, WithFileUploads;

    public $sqlFile;

    public string $googleDriveUrl = '';

    public bool $showEmailModal = false;

    public string $emailBackupFile = '';

    public string $backupEmail = '';

    /**
     * Lắng nghe sự kiện để cập nhật danh sách khi có file mới được tạo
     */
    #[On('backup-updated')]
    public function refresh(): void
    {
        // Livewire tự động re-render khi state thay đổi hoặc có event
    }

    /**
     * Render danh sách file từ Service
     */
    public function render(DatabaseService $service)
    {
        return view('System::livewire.database.backup-manager', [
            'backups' => $service->getAllBackupFiles(),
            'backupDirectories' => [
                'storage/app/private/backups',
                'storage/app/backups (thư mục cũ)',
            ],
        ]);
    }

    /**
     * Xử lý khôi phục dữ liệu
     */
    public function restoreBackup(string $fileName, DatabaseService $service): void
    {
        $this->authorizePermission('database.restore');

        try {
            $success = $service->restoreFromFile($fileName);

            if ($success) {
                $this->dispatch('notify', type: 'success', message: 'Khôi phục dữ liệu thành công!');
            }
        } catch (Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function deleteBackup(string $fileName, DatabaseService $service): void
    {
        $this->authorizePermission('database.destroy');

        try {
            $service->deleteBackup($fileName);
            session()->flash('success', "Đã xóa backup {$fileName}.");
        } catch (Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function uploadSql(DatabaseService $service): void
    {
        $this->authorizePermission('database.restore');

        $validated = $this->validate([
            'sqlFile' => ['required', 'file', 'max:20480'],
        ], [
            'sqlFile.required' => 'Vui lòng chọn file SQL.',
            'sqlFile.max' => 'File upload trực tiếp không được vượt quá 20 MB.',
        ]);

        try {
            $name = $service->importBackupFile(
                $validated['sqlFile']->getRealPath(),
                $validated['sqlFile']->getClientOriginalName(),
            );
            $this->reset('sqlFile');
            session()->flash('success', "Đã tải lên {$name}. Hãy kiểm tra và bấm RESTORE khi sẵn sàng.");
        } catch (Exception $e) {
            $this->addError('sqlFile', $e->getMessage());
        }
    }

    public function importFromGoogleDrive(DatabaseService $service): void
    {
        $this->authorizePermission('database.restore');
        $this->validate([
            'googleDriveUrl' => ['required', 'url', 'max:2048'],
        ]);

        if (! preg_match('~(?:/file/d/|[?&]id=)([A-Za-z0-9_-]{10,})~', $this->googleDriveUrl, $matches)) {
            $this->addError('googleDriveUrl', 'Link Google Drive không hợp lệ. Hãy dùng link chia sẻ của một file SQL.');
            return;
        }

        $temporaryPath = tempnam(storage_path('framework'), 'drive-sql-');

        if ($temporaryPath === false) {
            $this->addError('googleDriveUrl', 'Không thể tạo file tạm để tải backup.');
            return;
        }

        try {
            $response = Http::withOptions(['sink' => $temporaryPath])
                ->connectTimeout(15)
                ->timeout(300)
                ->get('https://drive.usercontent.google.com/download', [
                    'id' => $matches[1],
                    'export' => 'download',
                    'confirm' => 't',
                ]);

            if (! $response->successful()) {
                throw new Exception('Google Drive trả về HTTP '.$response->status().'. Hãy kiểm tra quyền chia sẻ file.');
            }

            $name = $service->importBackupFile($temporaryPath, 'google-drive-'.$matches[1].'.sql');
            $this->googleDriveUrl = '';
            session()->flash('success', "Đã tải {$name} từ Google Drive. Hãy kiểm tra và bấm RESTORE khi sẵn sàng.");
        } catch (Exception $e) {
            $this->addError('googleDriveUrl', $e->getMessage());
        } finally {
            @unlink($temporaryPath);
        }
    }

    public function openEmailModal(string $fileName, DatabaseService $service): void
    {
        $this->authorizePermission('database.download');

        $path = $service->getDownloadPath($fileName);

        if ($path === null) {
            $this->dispatch('notify', type: 'error', message: 'File backup không tồn tại.');

            return;
        }

        if (filesize($path) > SendDatabaseBackupEmail::MAX_ATTACHMENT_BYTES) {
            $this->dispatch('notify', type: 'error', message: 'Chỉ gửi được file backup có dung lượng tối đa 10MB.');

            return;
        }

        $this->emailBackupFile = $fileName;
        $this->backupEmail = (string) (auth('admin')->user()?->email ?? '');
        $this->resetErrorBag('backupEmail');
        $this->showEmailModal = true;
    }

    public function sendBackupEmail(DatabaseService $service): void
    {
        $this->authorizePermission('database.download');

        $validated = $this->validate([
            'emailBackupFile' => ['required', 'string', 'max:255'],
            'backupEmail' => ['required', 'email:rfc', 'max:255'],
        ], [
            'backupEmail.required' => 'Vui lòng nhập email nhận backup.',
            'backupEmail.email' => 'Địa chỉ email không hợp lệ.',
        ]);

        $path = $service->getDownloadPath($validated['emailBackupFile']);

        if ($path === null) {
            $this->addError('emailBackupFile', 'File backup không còn tồn tại.');

            return;
        }

        if (filesize($path) > SendDatabaseBackupEmail::MAX_ATTACHMENT_BYTES) {
            $this->addError('emailBackupFile', 'File backup vượt quá giới hạn 10MB.');

            return;
        }

        SendDatabaseBackupEmail::dispatch(
            $validated['emailBackupFile'],
            $validated['backupEmail'],
        );

        $this->showEmailModal = false;
        $this->emailBackupFile = '';
        session()->flash('success', 'Đã đưa yêu cầu gửi backup vào hàng đợi email.');
    }
}
