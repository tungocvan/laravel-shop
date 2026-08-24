<?php

namespace Modules\Request\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Request\Application\Services\RequestExportQuery;
use Modules\Request\Domain\Enums\RequestStatus;

final class RequestReportController extends Controller
{
    public function __invoke(Request $httpRequest, RequestExportQuery $query): View
    {
        $user = auth('admin')->user();
        abort_unless($user, 403);

        $status = $httpRequest->string('status')->toString();
        $allowedStatuses = array_column(RequestStatus::cases(), 'value');
        $filters = in_array($status, $allowedStatuses, true) ? ['status' => $status] : [];

        $requests = $query->queryFor($user, $filters)->paginate(25)->withQueryString();
        $statusCounts = [];

        foreach (RequestStatus::cases() as $case) {
            $statusCounts[$case->value] = $query->queryFor($user, ['status' => $case->value])->count();
        }

        return view('Request::admin.reports', [
            'requests' => $requests,
            'statusCounts' => $statusCounts,
            'selectedStatus' => $filters['status'] ?? '',
            'statuses' => RequestStatus::cases(),
            'syncRowLimit' => (int) config('request.exports.sync_row_limit', 500),
            'maxRows' => (int) config('request.exports.max_rows', 100000),
        ]);
    }
}
