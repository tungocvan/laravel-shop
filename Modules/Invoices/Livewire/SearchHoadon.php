<?php

namespace Modules\Invoices\Livewire;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Modules\Invoices\Jobs\ProcessGdtInvoicesJob;
use Modules\Invoices\Services\GdtApiService;
use Modules\Invoices\Services\GdtInvoiceService;
use Modules\Invoices\Services\InvoiceImportService;

class SearchHoadon extends Component
{
    use WithFileUploads;

    protected GdtInvoiceService $invoiceService;
    protected InvoiceImportService $importService;
    protected GdtApiService $apiService;

    public $start_date;
    public $end_date;
    public $vatIn = false;
    public $useQueue = false;
    public array $logs = [];
    public ?string $syncId = null;
    public string $syncState = 'idle';
    public ?string $syncMessage = null;
    public ?string $syncFile = null;
    public array $availableFiles = [];
    public ?string $selectedFile = null;
    public $uploadFile;

    public function boot(
        GdtInvoiceService $invoiceService,
        InvoiceImportService $importService,
        GdtApiService $apiService
    ): void {
        $this->invoiceService = $invoiceService;
        $this->importService = $importService;
        $this->apiService = $apiService;
    }

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
        $this->refreshAvailableFiles();
    }

    private function log(string $msg): void
    {
        $this->logs[] = '['.now()->format('H:i:s').'] '.$msg;
        $this->dispatch('scroll-bottom');
    }

    public function run(): void
    {
        $this->authorizePermission('invoices-create');
        $this->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'vatIn' => ['boolean'],
            'useQueue' => ['boolean'],
        ]);

        $this->logs = [];
        $this->syncMessage = null;
        $this->syncFile = null;
        $this->log('Bắt đầu xử lý…');

        if ($this->useQueue) {
            $this->syncId = (string) Str::uuid();
            $this->syncState = 'queued';
            $status = [
                'state' => 'queued',
                'message' => 'Đã đưa tác vụ vào hàng đợi.',
                'logs' => ['['.now()->format('H:i:s').'] Đã đưa tác vụ vào hàng đợi.'],
                'started_at' => now()->toIso8601String(),
                'file' => null,
            ];
            Cache::put($this->statusKey(), $status, now()->addHours(24));

            ProcessGdtInvoicesJob::dispatch(
                $this->start_date,
                $this->end_date,
                (bool) $this->vatIn,
                $this->syncId
            );

            $this->pollStatus();
            return;
        }

        $this->syncState = 'processing';
        try {
            $file = $this->invoiceService->processRange(
                $this->start_date,
                $this->end_date,
                fn ($msg) => $this->log($msg),
                (bool) $this->vatIn
            );
            $this->syncState = 'completed';
            $this->syncMessage = 'Đồng bộ hoàn tất.';
            $this->syncFile = is_string($file) ? $file : null;
            $this->log('Hoàn tất xử lý!');
            $this->refreshAvailableFiles();
        } catch (\Throwable $exception) {
            $this->syncState = 'failed';
            $this->syncMessage = $exception->getMessage();
            $this->log('❌ '.$exception->getMessage());
        }

        if (! $this->apiService->hasToken()) {
            session()->flash('status', 'Token đã hết hạn.');
            $this->redirectRoute('admin.invoices.create-token');
        }
    }

    public function pollStatus(): void
    {
        if (! $this->syncId) {
            return;
        }

        $status = Cache::get($this->statusKey());
        if (! is_array($status)) {
            return;
        }

        $this->syncState = (string) ($status['state'] ?? 'queued');
        $this->syncMessage = $status['message'] ?? null;
        $this->syncFile = $status['file'] ?? null;
        $this->logs = array_values($status['logs'] ?? []);

        if (in_array($this->syncState, ['completed', 'failed'], true)) {
            $this->refreshAvailableFiles();
        }
    }

    public function refreshAvailableFiles(): void
    {
        $base = trim((string) config('invoices.storage.export_directory', 'gdt'), '/');
        $direction = (bool) $this->vatIn ? 'vat_in' : 'vat_out';
        $folder = storage_path("app/{$base}/{$direction}");

        $this->availableFiles = [];
        if (! is_dir($folder)) {
            return;
        }

        $files = glob($folder.'/*.{xlsx,csv}', GLOB_BRACE) ?: [];
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        $this->availableFiles = array_map(static fn (string $path): array => [
            'name' => basename($path),
            'path' => $path,
            'size' => filesize($path) ?: 0,
            'modified_at' => date('Y-m-d H:i:s', filemtime($path) ?: time()),
        ], array_slice($files, 0, 50));

        if ($this->selectedFile && ! collect($this->availableFiles)->contains('path', $this->selectedFile)) {
            $this->selectedFile = null;
        }
    }

    public function updatedVatIn(): void
    {
        $this->selectedFile = null;
        $this->refreshAvailableFiles();
    }

    public function importSelectedFile(): void
    {
        $this->authorizePermission('invoices-create');
        $this->validate(['selectedFile' => ['required', 'string']]);

        $allowed = collect($this->availableFiles)->pluck('path')->all();
        abort_unless(in_array($this->selectedFile, $allowed, true), 422);

        $this->runImport($this->selectedFile);
    }

    public function importUploadedFile(): void
    {
        $this->authorizePermission('invoices-create');
        $this->validate([
            'uploadFile' => ['required', 'file', 'mimes:xlsx,csv', 'max:20480'],
        ]);

        $stored = $this->uploadFile->store('invoices-imports');
        $path = storage_path('app/'.$stored);

        try {
            $this->runImport($path);
        } finally {
            @unlink($path);
            $this->reset('uploadFile');
        }
    }

    private function runImport(string $path): void
    {
        $this->logs = [];
        $this->log('Bắt đầu import: '.basename($path));

        try {
            $count = $this->importService->import(
                $path,
                (bool) $this->vatIn ? 'purchase' : 'sold',
                fn ($message) => $this->log($message)
            );
            $this->log("🎯 Import hoàn tất: {$count} hóa đơn mới.");
        } catch (\Throwable $exception) {
            $this->log('❌ '.$exception->getMessage());
        }
    }

    private function statusKey(): string
    {
        return 'invoices:gdt-sync:'.$this->syncId;
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }

    public function render()
    {
        return view('Invoices::livewire.search-hoadon');
    }
}
