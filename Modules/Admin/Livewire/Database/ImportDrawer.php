<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire\Database;

use Livewire\Component;

/**
 * @deprecated Database administration is quarantined until a dedicated,
 * hardened System-owned operations boundary is explicitly approved.
 */
class ImportDrawer extends Component
{
    public bool $databaseActionsDisabled = true;

    public function save(): void
    {
        $this->denyDatabaseAction();
    }

    public function render()
    {
        return view('Admin::livewire.database.import-drawer');
    }

    private function denyDatabaseAction(): void
    {
        abort(403, 'Database administration is disabled until P0 controls are implemented.');
    }
}
