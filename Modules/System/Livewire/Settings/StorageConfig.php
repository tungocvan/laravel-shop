<?php

namespace Modules\System\Livewire\Settings;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\Cloud\CloudBackupAutomationService;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use Modules\System\Services\Env\SystemGoogleDriveConfigService;
use Throwable;

class StorageConfig extends Component
{
    use AuthorizesSystemActions;

    public array $form = [
        'GOOGLE_DRIVE_CLIENT_ID' => '',
        'GOOGLE_DRIVE_CLIENT_SECRET' => '',
        'GOOGLE_DRIVE_REDIRECT_URI' => '',
        'GOOGLE_DRIVE_FOLDER_NAME' => 'Laravel-Backup',
    ];

    public array $automation = [
        'enabled' => false,
        'time' => '02:00',
        'upload_drive' => true,
        'local_retention' => 30,
        'drive_retention' => 30,
    ];

    public array $automationStatus = [];
    public array $status = [];
    public bool $canUpdate = false;
    public bool $configured = false;

    public function mount(SystemGoogleDriveConfigService $configService, GoogleDriveConnectionService $connectionService, CloudBackupAutomationService $automationService): void
    {
        $this->canUpdate = (bool) auth('admin')->user()?->can('system.env.update');
        $this->form = $configService->publicConfig() + ['GOOGLE_DRIVE_CLIENT_SECRET' => ''];
        $this->form['GOOGLE_DRIVE_CLIENT_SECRET'] = '';
        $this->configured = $configService->isConfigured();
        $this->status = $connectionService->status();
        $config = $automationService->config();
        $this->automation = array_intersect_key($config, $this->automation);
        $this->automationStatus = $config;
    }

    public function save(SystemGoogleDriveConfigService $service): void
    {
        $this->authorizePermission('system.env.update');
        $validated = $this->validate([
            'form.GOOGLE_DRIVE_CLIENT_ID' => ['required', 'string', 'max:512'],
            'form.GOOGLE_DRIVE_CLIENT_SECRET' => ['nullable', 'string', 'max:4096'],
            'form.GOOGLE_DRIVE_REDIRECT_URI' => ['required', 'url:http,https', 'max:2048'],
            'form.GOOGLE_DRIVE_FOLDER_NAME' => ['required', 'string', 'max:255'],
        ]);

        try {
            $result = $service->save($validated['form'], auth('admin')->id());
            $this->form['GOOGLE_DRIVE_CLIENT_SECRET'] = '';
            $this->configured = $service->isConfigured();
            $this->dispatch('notify', type: $result['success'] ? 'success' : 'error', message: $result['message']);
        } catch (Throwable $e) {
            Log::error('StorageConfig Google Drive save failed.', ['exception' => $e::class]);
            $this->dispatch('notify', type: 'error', message: 'Không thể lưu cấu hình Google Drive. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function saveAutomation(CloudBackupAutomationService $service): void
    {
        $this->authorizePermission('system.env.update');
        $validated = $this->validate([
            'automation.enabled' => ['boolean'],
            'automation.time' => ['required', 'date_format:H:i'],
            'automation.upload_drive' => ['boolean'],
            'automation.local_retention' => ['required', 'integer', 'min:1', 'max:365'],
            'automation.drive_retention' => ['required', 'integer', 'min:1', 'max:365'],
        ]);
        $service->save($validated['automation']);
        $this->automationStatus = $service->config();
        $this->dispatch('notify', type: 'success', message: 'Đã lưu cấu hình backup tự động.');
    }

    public function runAutomationNow(CloudBackupAutomationService $service): void
    {
        $this->authorizePermission('system.env.update');
        try {
            $exitCode = Artisan::call('system:cloud-backup', ['--force' => true]);
            $this->automationStatus = $service->config();
            $this->dispatch('notify', type: $exitCode === 0 ? 'success' : 'error', message: $exitCode === 0 ? 'Đã chạy backup tự động thử nghiệm.' : 'Backup thử nghiệm thất bại. Vui lòng kiểm tra log.');
        } catch (Throwable $e) {
            Log::error('Manual cloud backup run failed.', ['error' => $e->getMessage()]);
            $this->dispatch('notify', type: 'error', message: 'Không thể chạy backup tự động thử nghiệm.');
        }
    }

    public function testConnection(GoogleDriveConnectionService $service): void
    {
        $this->authorizePermission('system.env.update');
        try {
            $this->status = $service->testConnection();
            $this->dispatch('notify', type: 'success', message: 'Kết nối Google Drive hoạt động bình thường.');
        } catch (Throwable $e) {
            Log::warning('Google Drive connection test failed.', ['exception' => $e::class]);
            $this->dispatch('notify', type: 'error', message: 'Không thể kết nối Google Drive. Vui lòng kiểm tra lại quyền hoặc log hệ thống.');
        }
    }

    public function disconnect(GoogleDriveConnectionService $service): void
    {
        $this->authorizePermission('system.env.update');
        try {
            $service->disconnect();
            $this->status = $service->status();
            $this->dispatch('notify', type: 'success', message: 'Đã ngắt kết nối Google Drive.');
        } catch (Throwable $e) {
            Log::warning('Google Drive disconnect failed.', ['exception' => $e::class]);
            $this->dispatch('notify', type: 'error', message: 'Không thể ngắt kết nối Google Drive hoàn toàn. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function render()
    {
        return view('System::livewire.settings.storage-config');
    }
}
