<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Admin\Services\DatabaseService;
use Illuminate\Http\Request;

class DatabaseController extends Controller
{
    protected $dbService;

    public function __construct(DatabaseService $dbService)
    {
        $this->dbService = $dbService;
    }

    public function index()
    {
        return view('Admin::pages.database');
    }

    public function download($filename)
    {
        // ACL Check (Ví dụ: chỉ Super Admin mới được tải)
        // $this->authorize('download_database');

        $path = $this->dbService->getDownloadPath($filename);

        if (!$path) {
            abort(404, 'File backup không tồn tại.');
        }

        return response()->download($path);
    }
}

