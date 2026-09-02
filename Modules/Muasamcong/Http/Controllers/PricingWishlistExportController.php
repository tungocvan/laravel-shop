<?php

namespace Modules\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Modules\Muasamcong\Models\PricingWishlist;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PricingWishlistExportController extends Controller
{
    private const EXPORT_PERMISSION = 'muasamcong.pricing.wishlist';

    private const MAX_EXPORT_ROWS = 2000;

    public function __invoke(Request $request): BinaryFileResponse
    {
        $user = $this->authorizedUser();

        $validated = $request->validate([
            'selected_ids' => ['nullable', 'array', 'max:'.self::MAX_EXPORT_ROWS],
            'selected_ids.*' => ['required', 'integer', 'min:1'],
        ]);

        $selectedIds = array_values(array_unique(array_filter(
            array_map('intval', (array) ($validated['selected_ids'] ?? [])),
            static fn (int $id): bool => $id > 0
        )));

        if ($selectedIds === []) {
            $query = PricingWishlist::query()->where('user_id', (int) $user->getAuthIdentifier());
            $count = (clone $query)->count();

            abort_if($count === 0, 422, 'Wishlist chưa có dữ liệu để xuất Excel.');
            abort_if(
                $count > self::MAX_EXPORT_ROWS,
                422,
                'Phạm vi xuất vượt quá '.self::MAX_EXPORT_ROWS.' dòng. Hãy chọn các dòng cần xuất trước khi tiếp tục.'
            );

            $selectedIds = $query->orderBy('id')->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        }

        $request->merge(['selected_ids' => $selectedIds]);

        return app(PricingWishlistBulkController::class)->export($request);
    }

    private function authorizedUser(): object
    {
        $user = Auth::guard('admin')->user();

        abort_unless($user !== null && Gate::forUser($user)->allows(self::EXPORT_PERMISSION), 403);

        return $user;
    }
}
