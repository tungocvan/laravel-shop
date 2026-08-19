<?php

namespace Modules\ClientPortal\Applications\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\ClientPortal\Applications\Muasamcong\Services\ClientPricingSearchService;
use Modules\Muasamcong\Models\PricingWishlist;

class MuasamcongWishlistController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user('web');
        abort_if($user === null, 401);
        $keyword = trim((string) $request->query('q', ''));

        $wishlists = PricingWishlist::query()
            ->where('user_id', $user->getKey())
            ->when($keyword !== '', fn ($query) => $query->where(function ($nested) use ($keyword): void {
                $needle = '%'.$keyword.'%';
                $nested->where('medicine_name', 'like', $needle)
                    ->orWhere('active_ingredient', 'like', $needle)
                    ->orWhere('ma_tbmt', 'like', $needle);
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('ClientPortal::applications.muasamcong.wishlist', compact('wishlists', 'keyword'));
    }

    public function store(Request $request, ClientPricingSearchService $search): RedirectResponse
    {
        $user = $request->user('web');
        abort_if($user === null, 401);
        $validated = $request->validate([
            'keyword' => ['required', 'string', 'min:2', 'max:200'],
            'source_id' => ['required', 'uuid'],
        ]);

        $keyword = trim($validated['keyword']);
        $sourceId = $validated['source_id'];
        $result = $search->search($keyword, $user->getKey())['result'];
        abort_unless($result['success'] ?? false, 404);
        $item = collect($result['data']['items'] ?? [])->first(fn ($row): bool => is_array($row) && (string) ($row['id'] ?? '') === $sourceId);
        abort_unless(is_array($item), 404);

        PricingWishlist::query()->updateOrCreate(
            ['user_id' => $user->getKey(), 'source_id' => $sourceId],
            [
                'search_keyword' => $keyword,
                'medicine_name' => $item['tenThuoc'] ?? null,
                'active_ingredient' => $item['tenHoatChat'] ?? null,
                'strength' => $item['nongDo'] ?? null,
                'medicine_group' => $item['nhomThuoc'] ?? $item['groupMedicine'] ?? null,
                'ma_tbmt' => $item['maTbmt'] ?? null,
                'snapshot' => $item,
            ]
        );

        return back()->with('status', 'Đã thêm thuốc vào danh sách quan tâm.');
    }

    public function destroy(Request $request, PricingWishlist $wishlist): RedirectResponse
    {
        $user = $request->user('web');
        abort_if($user === null || (int) $wishlist->user_id !== (int) $user->getKey(), 403);
        $wishlist->delete();

        return back()->with('status', 'Đã bỏ thuốc khỏi danh sách quan tâm.');
    }
}
