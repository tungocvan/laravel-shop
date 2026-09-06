<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Pharma\Jobs\PersistOfficialSourceSnapshotJob;
use Modules\Pharma\Models\OfficialSourceFacility;
use Modules\Pharma\Models\OfficialSourceSyncBatch;

class OfficialSourceSyncController extends Controller
{
    public const BHXH_SNAPSHOT_SESSION = 'pharma.official_facilities.bhxh.last_snapshot';

    public function index(Request $request): View
    {
        $query = OfficialSourceFacility::query()
            ->when($request->filled('source'), fn ($builder) => $builder->where('source', (string) $request->string('source')))
            ->when($request->filled('province'), fn ($builder) => $builder->where('source_province_code', (string) $request->string('province')))
            ->when($request->filled('status'), fn ($builder) => $builder->where('is_active', $request->string('status') === 'active'))
            ->when($request->filled('search'), function ($builder) use ($request): void {
                $search = '%'.(string) $request->string('search').'%';
                $builder->where(function ($nested) use ($search): void {
                    $nested->where('external_id', 'like', $search)
                        ->orWhere('facility_name', 'like', $search);
                });
            })
            ->orderBy('province_name')
            ->orderBy('facility_name');

        return view('Pharma::pages.official-facilities.source', [
            'facilities' => $query->paginate($this->perPage($request))->withQueryString(),
            'batches' => OfficialSourceSyncBatch::query()->latest('id')->limit(20)->get(),
            'provinceOptions' => OfficialSourceFacility::query()
                ->select(['source_province_code', 'province_name'])
                ->whereNotNull('province_name')
                ->distinct()
                ->orderBy('province_name')
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $snapshot = (array) $request->session()->get(self::BHXH_SNAPSHOT_SESSION, []);
        $facilities = $snapshot['facilities'] ?? [];

        if (($snapshot['source'] ?? null) !== 'bhxh' || ! is_array($facilities) || $facilities === []) {
            return response()->json([
                'message' => 'Không có snapshot BHXH hợp lệ để đồng bộ. Hãy tra cứu BHXH thành công trước.',
            ], 422);
        }

        $batch = OfficialSourceSyncBatch::query()->create([
            'source' => 'bhxh',
            'source_province_code' => (string) $snapshot['source_province_code'],
            'province_name' => (string) $snapshot['province_name'],
            'source_district_code' => $snapshot['source_district_code'] ?: null,
            'district_name' => $snapshot['district_name'] ?: null,
            'sync_scope' => empty($snapshot['source_district_code']) ? 'province' : 'district',
            'status' => 'QUEUED',
            'fetched_count' => count($facilities),
            'requested_by' => auth('admin')->id(),
        ]);

        PersistOfficialSourceSnapshotJob::dispatch($batch->id, $facilities);
        $request->session()->forget(self::BHXH_SNAPSHOT_SESSION);

        return response()->json([
            'message' => 'Đã đưa yêu cầu đồng bộ vào hàng đợi.',
            'batch_id' => $batch->id,
            'status' => $batch->status,
            'count' => count($facilities),
        ], 202);
    }

    private function perPage(Request $request): int
    {
        $value = $request->integer('per_page', 25);

        return in_array($value, [10, 25, 50, 100], true) ? $value : 25;
    }
}
