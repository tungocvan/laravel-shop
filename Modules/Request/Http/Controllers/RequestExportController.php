<?php

namespace Modules\Request\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Request\Application\Services\PlanRequestExport;
use Modules\Request\Application\Services\RequestExportQuery;
use Modules\Request\Application\Services\StartRequestExport;
use Modules\Request\Domain\Enums\ExportStatus;
use Modules\Request\Models\RequestExportJob;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class RequestExportController extends Controller
{
    public function store(Request $request, PlanRequestExport $planner, StartRequestExport $starter): RedirectResponse
    {
        $user = auth('admin')->user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'format' => ['required', 'in:csv,xlsx'],
            'status' => ['nullable', 'string'],
            'idempotency_key' => ['required', 'string', 'max:191'],
        ]);

        $filters = filled($validated['status'] ?? null) ? ['status' => $validated['status']] : [];
        $plan = $planner->plan($user, $filters);
        $export = $starter->handle($user, $plan, $validated['format'], $validated['idempotency_key']);

        return redirect()
            ->route('request.admin.reports', $filters)
            ->with('request_export_message', $export->status === ExportStatus::Ready
                ? __('Request::request.exports.ready_message')
                : __('Request::request.exports.queued_message'));
    }

    public function download(string $exportPublicId, RequestExportQuery $query): StreamedResponse
    {
        $user = auth('admin')->user();
        abort_unless($user, 403);

        $export = RequestExportJob::query()->where('public_id', $exportPublicId)->firstOrFail();

        abort_unless((int) $export->requested_by === (int) $user->getAuthIdentifier(), 404);
        abort_unless($this->hasPermission($user, 'request.export'), 403);
        abort_unless($this->scopeStillAuthorized($export->authorization_scope_json, $query->authorizationScopeFor($user)), 403);
        abort_unless($export->status === ExportStatus::Ready, 404);
        abort_if($export->expires_at === null || $export->expires_at->isPast(), 410);
        abort_unless(filled($export->storage_disk) && filled($export->storage_path), 404);
        abort_unless(Storage::disk($export->storage_disk)->exists($export->storage_path), 404);

        return Storage::disk($export->storage_disk)->response(
            $export->storage_path,
            'request-export-'.$export->public_id.'.'.$export->format,
            [
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    private function scopeStillAuthorized(array $snapshot, array $current): bool
    {
        if ((int) ($snapshot['user_id'] ?? 0) !== (int) ($current['user_id'] ?? 0)) {
            return false;
        }

        foreach (['view_all', 'view_own', 'view_participant'] as $capability) {
            if (($snapshot[$capability] ?? false) === true && ($current[$capability] ?? false) !== true) {
                return false;
            }
        }

        return true;
    }

    private function hasPermission(mixed $user, string $permission): bool
    {
        if (method_exists($user, 'checkPermissionTo')) {
            return $user->checkPermissionTo($permission, 'admin');
        }

        return method_exists($user, 'can') && $user->can($permission);
    }
}
