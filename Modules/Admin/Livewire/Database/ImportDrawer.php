<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire\Database;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Admin\Services\DatabaseService;

class ImportDrawer extends Component
{
    use WithFileUploads;

    public $sqlFile;

    public function save(DatabaseService $service)
    {
        $this->validate([
            'sqlFile' => 'required|file|mimetypes:text/plain,application/sql,text/x-sql|max:51200', // Max 50MB
        ]);

        try {
            $path = $this->sqlFile->getRealPath();
            $service->import($path);

            $this->dispatch('notify', ['type' => 'success', 'message' => 'Import dữ liệu thành công!']);
            $this->dispatch('backup-updated'); // Refresh lại danh sách table
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('Admin::livewire.database.import-drawer');
    }
}
