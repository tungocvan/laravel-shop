<?php

namespace Modules\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Modules\Muasamcong\Services\PricingSearchSnapshotService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PricingExportController extends Controller
{
    private const EXPORT_PERMISSION = 'muasamcong.pricing.sync';

    private const MAX_EXPORT_ROWS = 2000;

    public function __invoke(Request $request, PricingSearchSnapshotService $snapshots): BinaryFileResponse
    {
        $this->authorizeExport();

        $validated = $request->validate([
            'keyword' => ['required', 'string', 'min:2', 'max:200'],
            'selected_ids' => ['nullable', 'array', 'max:'.self::MAX_EXPORT_ROWS],
            'selected_ids.*' => ['required', 'string', 'max:100'],
        ]);

        $selectedIds = array_values(array_unique(array_filter(
            (array) ($validated['selected_ids'] ?? []),
            static fn (mixed $id): bool => is_string($id) && trim($id) !== ''
        )));

        if ($selectedIds === []) {
            $snapshot = $snapshots->find($validated['keyword']);
            abort_if($snapshot === null || ! is_array($snapshot->result_payload), 404, 'Không tìm thấy dữ liệu tra cứu đã lưu để xuất Excel.');

            $items = is_array($snapshot->result_payload['data']['items'] ?? null)
                ? $snapshot->result_payload['data']['items']
                : [];

            $selectedIds = collect($items)
                ->pluck('id')
                ->filter(static fn (mixed $id): bool => is_string($id) && trim($id) !== '')
                ->unique()
                ->values()
                ->all();

            abort_if($selectedIds === [], 422, 'Không có dữ liệu tra cứu hợp lệ để xuất Excel.');
            abort_if(
                count($selectedIds) > self::MAX_EXPORT_ROWS,
                422,
                'Phạm vi xuất vượt quá '.self::MAX_EXPORT_ROWS.' dòng. Hãy thu hẹp phạm vi hoặc chọn các dòng cần xuất.'
            );
        }

        $request->merge(['selected_ids' => $selectedIds]);

        return app(MuasamcongController::class)->exportSelectedPricing($request, $snapshots);
    }

    private function authorizeExport(): void
    {
        $user = Auth::guard('admin')->user();

        abort_unless($user !== null && Gate::forUser($user)->allows(self::EXPORT_PERMISSION), 403);
    }
}
