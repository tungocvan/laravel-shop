<?php

namespace Modules\Request\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

final class RequestDashboardController extends Controller
{
    public function __invoke(): View
    {
        $counts = [];
        foreach (['request_groups', 'request_types', 'request_instances', 'request_tasks', 'request_comments', 'request_attachments'] as $table) {
            $counts[$table] = Schema::hasTable($table) ? DB::table($table)->count() : 0;
        }

        $demoType = Schema::hasTable('request_types')
            ? DB::table('request_types')->where('code', 'REQUEST_UI_DEMO')->first()
            : null;

        $draftRequest = Schema::hasTable('request_instances')
            ? DB::table('request_instances')->where('request_number', 'DEMO-DRAFT-001')->first()
            : null;

        $pendingRequest = Schema::hasTable('request_instances')
            ? DB::table('request_instances')->where('request_number', 'DEMO-PENDING-001')->first()
            : null;

        return view('Request::dashboard', compact('counts', 'demoType', 'draftRequest', 'pendingRequest'));
    }
}
