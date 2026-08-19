<?php

namespace Modules\ClientPortal\Applications\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\ClientPortal\Models\SyncRequest;
use Modules\Muasamcong\Models\PricingSearchSnapshot;

class MuasamcongHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'type' => ['nullable', 'in:all,search,sync'],
            'status' => ['nullable', 'in:queued,processing,completed,failed'],
            'q' => ['nullable', 'string', 'max:200'],
        ]);

        $userId = $request->user('web')?->getKey();
        abort_if($userId === null, 401);

        $type = (string) ($validated['type'] ?? 'all');
        $status = (string) ($validated['status'] ?? '');
        $q = trim((string) ($validated['q'] ?? ''));

        $searches = collect();
        if ($type !== 'sync') {
            $searches = PricingSearchSnapshot::query()
                ->where('searched_by', $userId)
                ->when($q !== '', fn ($query) => $query->where('keyword', 'like', '%'.$q.'%'))
                ->orderByDesc('searched_at')
                ->limit(100)
                ->get();
        }

        $syncs = collect();
        if ($type !== 'search') {
            $syncs = SyncRequest::query()
                ->where('user_id', $userId)
                ->where('application_key', 'muasamcong')
                ->where('feature_key', 'drug-pricing')
                ->when($status !== '', fn ($query) => $query->where('status', $status))
                ->when($q !== '', fn ($query) => $query->where('keyword', 'like', '%'.$q.'%'))
                ->orderByDesc('created_at')
                ->limit(100)
                ->get();
        }

        $activities = $searches->map(fn ($row): array => [
            'type' => 'search',
            'keyword' => $row->keyword,
            'status' => null,
            'selected_count' => null,
            'inserted_count' => null,
            'duplicate_count' => null,
            'missing_count' => null,
            'error_message' => null,
            'occurred_at' => $row->searched_at ?? $row->created_at,
            'loaded_total' => (int) $row->loaded_total,
            'source_total' => (int) $row->source_total,
        ])->concat($syncs->map(fn ($row): array => [
            'type' => 'sync',
            'keyword' => $row->keyword,
            'status' => $row->status,
            'selected_count' => (int) $row->selected_count,
            'inserted_count' => (int) $row->inserted_count,
            'duplicate_count' => (int) $row->duplicate_count,
            'missing_count' => (int) $row->missing_count,
            'error_message' => $row->error_message,
            'occurred_at' => $row->created_at,
            'loaded_total' => null,
            'source_total' => null,
        ]))->sortByDesc(fn (array $item) => $item['occurred_at']?->getTimestamp() ?? 0)->values();

        return view('ClientPortal::applications.muasamcong.history', compact('activities', 'type', 'status', 'q'));
    }
}
