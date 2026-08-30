<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire\Database;

use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @deprecated Database administration is quarantined until a dedicated,
 * hardened System-owned operations boundary is explicitly approved.
 */
class BackupManager extends Component
{
    public bool $databaseActionsDisabled = true;

    #[On('backup-updated')]
    public function refresh(): void
    {
        // Intentionally no-op while the P0 database surface is quarantined.
    }

    public function restoreBackup(string $fileName): void
    {
        $this->denyDatabaseAction();
    }

    public function restore(string $fileName): void
    {
        $this->denyDatabaseAction();
    }

    public function render()
    {
        return view('Admin::livewire.database.backup-manager', [
            'backups' => [],
        ]);
    }

    private function denyDatabaseAction(): void
    {
        abort(403, 'Database administration is disabled until P0 controls are implemented.');
    }
}
