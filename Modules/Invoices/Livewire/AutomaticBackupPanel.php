<?php

namespace Modules\Invoices\Livewire;

use Livewire\Component;
use Modules\Invoices\Services\AutomaticInvoiceBackupService;

class AutomaticBackupPanel extends Component
{
    protected AutomaticInvoiceBackupService $backupService;

    public ?string $notice = null;
    public ?string $error = null;

    public function boot(AutomaticInvoiceBackupService $backupService): void
    {
        $this->backupService = $backupService;
    }

    public function runNow(): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can('invoices-download'), 403);
        $this->notice = null;
        $this->error = null;

        try {
            $run = $this->backupService->run(null, 'manual-auto');
            $this->notice = $run->status === 'skipped'
                ? 'Không có file mới cần backup.'
                : "Backup hoàn tất: {$run->files_count} file · {$run->emails_sent} email.";
        } catch (\Throwable $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function render()
    {
        return view('Invoices::livewire.automatic-backup-panel', [
            'enabled' => (bool) config('invoices.backup.automatic_enabled', false),
            'recipient' => (string) config('invoices.backup.recipient', ''),
            'scheduleDay' => (int) config('invoices.backup.schedule_day', 1),
            'scheduleTime' => (string) config('invoices.backup.schedule_time', '00:15'),
            'pendingCount' => count($this->backupService->pendingFiles()),
            'runs' => $this->backupService->recentRuns(5),
        ]);
    }
}
