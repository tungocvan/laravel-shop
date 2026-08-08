<?php

namespace Modules\Shared\Livewire\ImportExport;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Shared\Services\ImportExport\BaseImportExportService;

class Panel extends Component
{
    use WithFileUploads;

    public string $serviceClass;

    public string $title = 'Import / Export dữ liệu';

    public string $description = 'Tải file mẫu, import dữ liệu hoặc export dữ liệu hiện tại.';

    public mixed $file = null;

    public string $mode = 'update_or_create';

    public bool $dryRun = false;

    public ?array $report = null;

    public array $filters = [];

    public ?string $permission = null;

    public function mount(
        string $serviceClass,
        string $title = 'Import / Export dữ liệu',
        string $description = 'Tải file mẫu, import dữ liệu hoặc export dữ liệu hiện tại.',
        array $filters = [],
        ?string $permission = null
    ): void {
        abort_unless(
            is_subclass_of($serviceClass, BaseImportExportService::class),
            422,
            'Dịch vụ Import / Export không hợp lệ.'
        );

        $this->serviceClass = $serviceClass;
        $this->title = $title;
        $this->description = $description;
        $this->filters = $filters;
        $this->permission = $permission;
    }

    protected function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:10240'],
            'mode' => ['required', 'in:create_only,update_or_create,skip_duplicate,replace'],
            'dryRun' => ['boolean'],
        ];
    }

    public function import(): void
    {
        $this->authorizeAction();
        $this->validate();

        $service = app($this->serviceClass);

        $this->report = $service->import($this->file->getRealPath(), [
            'mode' => $this->mode,
            'dry_run' => $this->dryRun,
        ]);

        $this->file = null;

        if (($this->report['success'] ?? false) === true) {
            session()->flash('success', 'Import hoàn tất.');
            $this->dispatch('import-export-completed', serviceClass: $this->serviceClass);
        } else {
            session()->flash('error', 'Import có lỗi, vui lòng kiểm tra bảng lỗi.');
        }
    }

    public function export()
    {
        $this->authorizeAction();
        $service = app($this->serviceClass);

        $path = $service->export($this->filters);

        return Storage::disk('public')->download($path);
    }

    public function exportTemplate()
    {
        $this->authorizeAction();
        $service = app($this->serviceClass);

        $path = $service->exportTemplate();

        return Storage::disk('public')->download($path);
    }

    public function render(): View
    {
        return view('Shared::livewire.import-export.panel');
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
