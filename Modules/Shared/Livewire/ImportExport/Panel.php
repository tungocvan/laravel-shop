<?php

namespace Modules\Shared\Livewire\ImportExport;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Shared\Services\ImportExport\BaseImportExportService;

class Panel extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $serviceClass;

    public string $title = 'Import / Export dữ liệu';

    public string $description = 'Tải file mẫu, import dữ liệu hoặc export dữ liệu hiện tại.';

    public mixed $file = null;

    public string $mode = 'update_or_create';

    public bool $dryRun = false;

    public ?array $report = null;

    #[Reactive]
    public array $filters = [];

    #[Locked]
    public ?string $permission = null;

    public bool $showSuccessModal = false;

    public string $successTitle = '';

    public string $successMessage = '';

    public function mount(
        string $serviceClass,
        string $title = 'Import / Export dữ liệu',
        string $description = 'Tải file mẫu, import dữ liệu hoặc export dữ liệu hiện tại.',
        array $filters = [],
        ?string $permission = null
    ): void {
        $this->assertServiceClass($serviceClass);

        $this->serviceClass = $serviceClass;
        $this->title = $title;
        $this->description = $description;
        $this->filters = $filters;
        $this->permission = $permission;
    }

    protected function rules(): array
    {
        $allowedModes = implode(',', $this->service()->allowedImportModes());

        return [
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:10240'],
            'mode' => ['required', 'in:'.$allowedModes],
            'dryRun' => ['boolean'],
        ];
    }

    public function import(): void
    {
        $service = $this->authorizedService();
        $this->validate();

        $this->report = $service->import($this->file->getRealPath(), [
            'mode' => $this->mode,
            'dry_run' => $this->dryRun,
        ]);

        $this->file = null;

        if (($this->report['success'] ?? false) === true) {
            $this->openSuccessModal(
                $this->dryRun ? 'Dry-run thành công' : 'Import thành công',
                $this->dryRun
                    ? 'Dữ liệu hợp lệ và chưa ghi vào database. Nhấn OK để tải lại màn hình.'
                    : 'Dữ liệu đã được import thành công. Nhấn OK để tải lại màn hình và xem dữ liệu mới nhất.'
            );
            $this->dispatch('import-export-completed', serviceClass: $this->serviceClass);
        } else {
            session()->flash('error', 'Import có lỗi, vui lòng kiểm tra bảng lỗi.');
        }
    }

    public function export()
    {
        $service = $this->authorizedService();
        $path = $service->export($this->filters);
        $selectedCount = count($this->filters['selected_ids'] ?? []);

        $this->openSuccessModal(
            'Export thành công',
            $selectedCount > 0
                ? "Đã tạo file export cho {$selectedCount} bản ghi đã chọn. Nhấn OK để tải lại màn hình."
                : 'Đã tạo file export dữ liệu. Nhấn OK để tải lại màn hình.'
        );

        return response()
            ->download($service->exportAbsolutePath($path), basename($path))
            ->deleteFileAfterSend(true);
    }

    public function exportTemplate()
    {
        $service = $this->authorizedService();
        $path = $service->exportTemplate();

        return response()
            ->download($service->exportAbsolutePath($path), basename($path))
            ->deleteFileAfterSend(true);
    }

    public function acknowledgeSuccess()
    {
        $this->showSuccessModal = false;

        return redirect()->to(request()->header('Referer') ?: url()->current());
    }

    public function render(): View
    {
        return view('Shared::livewire.import-export.panel');
    }

    private function openSuccessModal(string $title, string $message): void
    {
        $this->successTitle = $title;
        $this->successMessage = $message;
        $this->showSuccessModal = true;
    }

    private function authorizedService(): BaseImportExportService
    {
        $this->authorizeAction();

        return $this->service();
    }

    private function service(): BaseImportExportService
    {
        $this->assertServiceClass($this->serviceClass);

        return app($this->serviceClass);
    }

    private function assertServiceClass(string $serviceClass): void
    {
        abort_unless(
            is_subclass_of($serviceClass, BaseImportExportService::class),
            422,
            'Dịch vụ Import / Export không hợp lệ.'
        );
    }

    private function authorizeAction(): void
    {
        if ($this->permission === null) {
            return;
        }

        abort_unless(
            auth('admin')->check() && auth('admin')->user()->can($this->permission),
            403
        );
    }
}
