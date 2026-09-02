<?php

namespace Modules\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Modules\Muasamcong\Models\PricingResult;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SyncedPricingScopedExportController extends Controller
{
    private const EXPORT_PERMISSION = 'muasamcong.pricing.sync';

    private const MAX_EXPORT_ROWS = 5000;

    public function __invoke(Request $request): BinaryFileResponse
    {
        $this->authorizeExport();

        $validated = $request->validate([
            'selected_ids' => ['nullable', 'array', 'max:'.self::MAX_EXPORT_ROWS],
            'selected_ids.*' => ['required', 'integer', 'min:1'],
            'export_profile_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $selectedIds = array_values(array_unique(array_filter(
            array_map('intval', (array) ($validated['selected_ids'] ?? [])),
            static fn (int $id): bool => $id > 0
        )));

        if ($selectedIds === []) {
            $count = PricingResult::query()->count();
            abort_if($count === 0, 422, 'Không có dữ liệu đồng bộ để xuất Excel.');
            abort_if(
                $count > self::MAX_EXPORT_ROWS,
                422,
                'Phạm vi xuất vượt quá '.self::MAX_EXPORT_ROWS.' dòng. Hãy chọn các dòng cần xuất trước khi tiếp tục.'
            );

            $selectedIds = PricingResult::query()->orderBy('id')->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        }

        $request->merge(['selected_ids' => $selectedIds]);

        return app(SyncedPricingExportController::class)($request);
    }

    private function authorizeExport(): void
    {
        $user = Auth::guard('admin')->user();

        abort_unless($user !== null && Gate::forUser($user)->allows(self::EXPORT_PERMISSION), 403);
    }
}
