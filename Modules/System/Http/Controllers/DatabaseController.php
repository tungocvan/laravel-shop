<?php

namespace Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\System\Services\DatabaseService;

class DatabaseController extends Controller
{
    protected $dbService;

    public function __construct(DatabaseService $dbService)
    {
        $this->dbService = $dbService;
    }

    public function index(Request $request)
    {
        $this->authorizePermission('database.view');

        $allTables = $this->dbService->getAllTables();
        $modules = collect($allTables)
            ->pluck('module')
            ->filter(fn ($module) => is_string($module) && $module !== '' && $module !== 'Unknown')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $selectedModule = trim((string) $request->query('module', ''));
        if ($selectedModule !== '' && ! in_array($selectedModule, $modules, true)) {
            $selectedModule = '';
        }

        return view('System::pages.database', compact('modules', 'selectedModule'));
    }

    public function download($filename)
    {
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

        return redirect()->route('admin.system.database.index');
    }

    private function authorizePermission(string $permission): void
    {
        $user = auth('admin')->user() ?: auth()->user();

        abort_unless($user?->can($permission), 403);
    }
}
