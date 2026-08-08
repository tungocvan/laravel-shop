<?php

namespace Modules\Admin\Livewire\Database;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Quan ly co so du lieu')]
class TableList extends Component
{
    public bool $databaseActionsDisabled = true;

    public string $search = '';

    public array $selectedTables = [];

    public bool $selectAll = false;

    public ?string $loadingAction = null;

    public array $backupFiles = [];

    public ?string $selectedBackupFile = null;

    public bool $showRestoreModal = false;

    public bool $isRestoring = false;

    public function updatedSearch(): void
    {
        $this->resetSelection();
    }

    public function updatedSelectAll(): void
    {
        $this->resetSelection();
    }

    public function backupFull(): void
    {
        $this->denyDatabaseAction();
    }

    public function exportTable($tableName): void
    {
        $this->denyDatabaseAction();
    }

    public function restoreTable($tableName): void
    {
        $this->denyDatabaseAction();
    }

    public function truncateTable($tableName): void
    {
        $this->denyDatabaseAction();
    }

    public function dropTable($tableName): void
    {
        $this->denyDatabaseAction();
    }

    public function openRestoreModal(): void
    {
        $this->denyDatabaseAction();
    }

    public function restoreDatabase(): void
    {
        $this->denyDatabaseAction();
    }

    public function render()
    {
        return view('Admin::livewire.database.table-list', [
            'tables' => [],
        ]);
    }

    private function resetSelection(): void
    {
        $this->selectedTables = [];
        $this->selectAll = false;
    }

    private function denyDatabaseAction(): void
    {
        abort(403, 'Database administration is disabled until P0 controls are implemented.');
    }
}
