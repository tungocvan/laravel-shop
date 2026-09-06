<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Pharma\Jobs\PersistOfficialSourceSnapshotJob;
use Modules\Pharma\Models\OfficialSourceFacility;
use Modules\Pharma\Models\OfficialSourceSyncBatch;
use Modules\Pharma\Services\OfficialFacilityImport\BhxhProvinceCatalog;

class OfficialSourceSyncController extends Controller
{
    public const BHXH_SNAPSHOT_SESSION = 'pharma.official_facilities.bhxh.last_snapshot';

    public function index(Request $request, BhxhProvinceCatalog $provinceCatalog): View
    {
        $search = trim((string) $request->string('search'));
        $source = trim((string) $request->string('source'));
        $province = trim((string) $request->string('province'));
        $partition = trim((string) $request->string('partition'));
        $status = trim((string) $request->string('status'));

        $query = OfficialSourceFacility::query()
            ->when($source !== '', fn ($builder) => $builder->where('source', $source))
            ->when($province !== '', fn ($builder) => $builder->where('province_name', $province))
            ->when($partition !== '', fn ($builder) => $builder->where('source_province_code', $partition))
            ->when(in_array($status, ['active', 'stale'], true), fn ($builder) => $builder->where('is_active', $status === 'active'))
            ->when($search !== '', function ($builder) use ($search): void {
                $like = '%'.$search.'%';
                $builder->where(function ($nested) use ($like): void {
                    $nested->where('external_id', 'like', $like)
                        ->orWhere('facility_name', 'like', $like)
                        ->orWhere('province_name', 'like', $like)
                        ->orWhere('source_province_code', 'like', $like)
                        ->orWhere('district_name', 'like', $like)
                        ->orWhere('source_district_code', 'like', $like);
                });
            })
            ->orderBy('province_name')
            ->orderBy('source_province_code')
            ->orderBy('facility_name');

        $partitionLabels = [];
        foreach ($provinceCatalog->codes() as $uiCode) {
            foreach ($provinceCatalog->partitionsFor($uiCode) as $item) {
                $partitionLabels[$item['source_code']] = $item['partition_name'];
            }
        }

        $partitionOptions = OfficialSourceFacility::query()
            ->select(['source_province_code', 'province_name'])
            ->when($province !== '', fn ($builder) => $builder->where('province_name', $province))
            ->distinct()
            ->orderBy('province_name')
            ->orderBy('source_province_code')
            ->get();

        return view('Pharma::pages.official-facilities.source', [
            'facilities' => $query->paginate($this->perPage($request))->withQueryString(),
            'batches' => OfficialSourceSyncBatch::query()->latest('id')->limit(20)->get(),
            'provinceOptions' => OfficialSourceFacility::query()
                ->whereNotNull('province_name')
                ->where('province_name', '<>', '')
                ->distinct()
                ->orderBy('province_name')
                ->pluck('province_name'),
            'partitionOptions' => $partitionOptions,
            'partitionLabels' => $partitionLabels,
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
            'sync_scope' => empty($snapshot['source_district_code']) ? 'source_partition' : 'district',
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

    public function status(OfficialSourceSyncBatch $batch): JsonResponse
    {
        return response()->json([
            'batch_id' => $batch->id,
            'status' => $batch->status,
            'fetched_count' => $batch->fetched_count,
            'created_count' => $batch->created_count,
            'updated_count' => $batch->updated_count,
            'unchanged_count' => $batch->unchanged_count,
            'stale_count' => $batch->stale_count,
            'completed_at' => optional($batch->completed_at)->toIso8601String(),
            'error_message' => $batch->error_message,
        ]);
    }

    private function perPage(Request $request): int
    {
        $value = $request->integer('per_page', 25);

        return in_array($value, [10, 25, 50, 100], true) ? $value : 25;
    }
}
