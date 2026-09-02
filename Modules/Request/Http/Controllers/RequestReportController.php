<?php

namespace Modules\Request\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Request\Application\Services\DeleteCompletedRequest;
use Modules\Request\Application\Services\RequestExportQuery;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Models\RequestExportJob;
use Modules\Request\Models\RequestGroup;
use Modules\Request\Models\RequestType;

final class RequestReportController extends Controller
{
    public function __invoke(Request $httpRequest, RequestExportQuery $query): View
    {
        $user = auth('admin')->user();
        abort_unless($user, 403);

        $allowedStatuses = array_column(RequestStatus::cases(), 'value');
        $pageSizes = collect(config('request.settings.page_sizes', [10, 25, 50, 100]))
            ->map(fn (mixed $size): int => (int) $size)
            ->filter(fn (int $size): bool => $size > 0 && $size <= (int) config('request.settings.max_page_size', 100))
            ->unique()
            ->sort()
            ->values()
            ->all();
        $defaultPageSize = (int) config('request.settings.default_page_size', 25);
        if (! in_array($defaultPageSize, $pageSizes, true)) {
            $defaultPageSize = $pageSizes[0] ?? 25;
        }

        $validated = $httpRequest->validate([
            'status' => ['nullable', 'string', Rule::in($allowedStatuses)],
            'type_public_id' => ['nullable', 'ulid'],
            'group_public_id' => ['nullable', 'ulid'],
            'created_from' => ['nullable', 'date_format:Y-m-d'],
            'created_to' => ['nullable', 'date_format:Y-m-d', Rule::when($httpRequest->filled('created_from'), 'after_or_equal:created_from')],
            'per_page' => ['nullable', 'integer', Rule::in($pageSizes)],
        ]);
        $filters = collect($validated)
            ->only(['status', 'type_public_id', 'group_public_id', 'created_from', 'created_to'])
            ->filter(fn (mixed $value): bool => filled($value))
            ->all();
        $filtersWithoutStatus = collect($filters)->except('status')->all();
        $perPage = (int) ($validated['per_page'] ?? $defaultPageSize);

        $requests = $query->queryFor($user, $filters)->paginate($perPage)->withQueryString();
        $statusCounts = [];

        foreach (RequestStatus::cases() as $case) {
            $statusCounts[$case->value] = $query->queryFor($user, $filtersWithoutStatus + ['status' => $case->value])->count();
        }

        $authorizedTypeIds = $query->queryFor($user)
            ->reorder()
            ->select('request_instances.request_type_id')
            ->distinct()
            ->pluck('request_type_id');
        $types = RequestType::query()
            ->with('group:id,public_id,name')
            ->whereIn('id', $authorizedTypeIds)
            ->orderBy('name')
            ->get(['id', 'public_id', 'request_group_id', 'name']);
        $groups = RequestGroup::query()
            ->whereIn('id', $types->pluck('request_group_id')->filter()->unique())
            ->orderBy('name')
            ->get(['id', 'public_id', 'name']);

        $exports = RequestExportJob::query()
            ->where('requested_by', (int) $user->getAuthIdentifier())
            ->latest('id')
            ->limit(10)
            ->get();

        return view('Request::admin.reports', [
            'requests' => $requests,
            'exports' => $exports,
            'filters' => $filters,
            'filtersWithoutStatus' => $filtersWithoutStatus,
            'statusCounts' => $statusCounts,
            'selectedStatus' => $filters['status'] ?? '',
            'statuses' => RequestStatus::cases(),
            'types' => $types,
            'groups' => $groups,
            'pageSizes' => $pageSizes,
            'perPage' => $perPage,
            'filteredCount' => $requests->total(),
            'totalCount' => array_sum($statusCounts),
            'pendingCount' => $statusCounts[RequestStatus::Pending->value] ?? 0,
            'terminalCount' => collect([RequestStatus::Approved, RequestStatus::Rejected, RequestStatus::Cancelled])
                ->sum(fn (RequestStatus $status): int => $statusCounts[$status->value] ?? 0),
            'activeFilterCount' => count($filters),
            'refreshedAt' => now()->timezone((string) config('app.timezone', 'UTC')),
            'canExport' => $this->hasPermission($user, 'request.export'),
            'canDeleteRequests' => $this->hasPermission($user, 'request.instance.delete'),
            'exportAllowed' => $requests->total() <= (int) config('request.exports.max_rows', 100000),
            'syncRowLimit' => (int) config('request.exports.sync_row_limit', 500),
            'maxRows' => (int) config('request.exports.max_rows', 100000),
        ]);
    }

    public function destroy(string $requestPublicId, DeleteCompletedRequest $delete): RedirectResponse
    {
        $user = auth('admin')->user();
        abort_unless($user && $this->hasPermission($user, 'request.instance.delete'), 403);
        $delete->handle($requestPublicId, (int) $user->getAuthIdentifier());

        return redirect()->route('request.admin.reports')->with('request_success', 'Đã xóa đề nghị đã kết thúc; dấu vết quản trị vẫn được lưu trong audit.');
    }

    private function hasPermission(mixed $user, string $permission): bool
    {
        if (method_exists($user, 'checkPermissionTo')) {
            return $user->checkPermissionTo($permission, 'admin');
        }

        return method_exists($user, 'can') && $user->can($permission);
    }
}
