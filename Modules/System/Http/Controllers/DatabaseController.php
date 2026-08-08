<?php

namespace Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\System\Services\DatabaseService;

class DatabaseController extends Controller
{
    protected $dbService;

    public function __construct(DatabaseService $dbService)
    {
        $this->dbService = $dbService;
    }

    public function index()
    {
        $this->authorizePermission('database.view');

        return view('System::pages.database');
    }

    public function download($filename)
    {
        // ACL Check (Ví dụ: chỉ Super Admin mới được tải)
        // $this->authorize('download_database');

        $this->authorizePermission('database.download');

        $path = $this->dbService->getDownloadPath($filename);

        if (! $path) {
            abort(404, 'File backup không tồn tại.');
        }

        return response()->download($path);
    }

    public function backupRestore()
    {
        $this->authorizePermission('database.view');

        return view('System::pages.database-backup-restore');
    }

    private function authorizePermission(string $permission): void
    {
        $user = auth('admin')->user() ?: auth()->user();

        abort_unless($user?->can($permission), 403);
    }
}
