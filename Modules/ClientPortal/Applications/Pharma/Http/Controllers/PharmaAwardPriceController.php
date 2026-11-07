<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Pharma\Services\PublicAwardMedicineSync;
use Modules\Pharma\Services\PublicAwardPriceLookup;
use Modules\Pharma\Services\PublicAwardWishlistService;
use Modules\Pharma\Models\AwardWishlist;

final class PharmaAwardPriceController extends Controller
{
    public function index(Request $request, PublicAwardPriceLookup $lookup, ApplicationRegistry $registry, ClientPortalSettingsService $settings, PublicAwardWishlistService $wishlist): View
    {
        $this->authorizeView($request, $registry);
        $validated = $request->validate([
            'keyword' => ['nullable', 'string', 'min:2', 'max:200'],
            'page' => ['nullable', 'integer', 'between:1,100'],
            'fresh' => ['nullable', 'boolean'],
        ]);
        $keyword = trim((string) ($validated['keyword'] ?? ''));
        $page = (int) ($validated['page'] ?? 1);
        $result = $keyword === '' ? null : $lookup->search($keyword, $page, (bool) ($validated['fresh'] ?? false));
        $sourceIds = array_values(array_filter(array_map(
            static fn (array $item): string => is_scalar($item['id'] ?? null) ? (string) $item['id'] : '',
            $result['items'] ?? []
        )));
        $savedIds = $sourceIds === [] ? [] : $wishlist->savedIds((int) $request->user('web')->id, $sourceIds);

        $application = $registry->find('pharma');
        return view('ClientPortal::applications.pharma.award-prices', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'keyword' => $keyword, 'page' => $page, 'savedIds' => $savedIds,
            'result' => $result,
            'canSync' => $registry->userCan($request->user('web'), 'client.pharma.products.create'),
        ]);
    }

    public function wishlist(Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings): View
    {
        $this->authorizeView($request, $registry);
        $saved = AwardWishlist::query()->where('user_id', (int) $request->user('web')->id)
            ->latest()->paginate(20);

        $application = $registry->find('pharma');
        return view('ClientPortal::applications.pharma.award-wishlist', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'saved' => $saved,
        ]);
    }

    public function toggleWishlist(
        Request $request,
        ApplicationRegistry $registry,
        PublicAwardPriceLookup $lookup,
        PublicAwardWishlistService $wishlist
    ): RedirectResponse {
        $this->authorizeView($request, $registry);
        $input = $request->validate([
            'keyword' => ['required', 'string', 'min:2', 'max:200'],
            'page' => ['required', 'integer', 'between:1,100'],
            'source_id' => ['required', 'string', 'max:160'],
        ]);

        $userId = (int) $request->user('web')->id;
        if ($wishlist->remove($userId, $input['source_id'])) {
            return redirect()->back()->with('success', 'Đã bỏ thuốc khỏi danh sách quan tâm.');
        }

        $result = $lookup->search($input['keyword'], (int) $input['page']);
        if (! ($result['success'] ?? false)) {
            throw ValidationException::withMessages(['source_id' => 'Không thể xác minh dữ liệu nguồn.']);
        }
        $item = collect($result['items'])->first(static fn (array $row): bool =>
            is_scalar($row['id'] ?? null) && (string) $row['id'] === $input['source_id']);
        if (! is_array($item)) {
            throw ValidationException::withMessages(['source_id' => 'Bản ghi không còn thuộc trang kết quả này.']);
        }

        $saved = $wishlist->toggle($userId, $item, $input['keyword'], (int) $input['page']);

        return redirect()->route('client.pharma.award-prices', [
            'keyword' => $input['keyword'], 'page' => $input['page'],
        ])->with('success', $saved ? 'Đã lưu thuốc vào danh sách quan tâm.' : 'Đã bỏ thuốc khỏi danh sách quan tâm.');
    }

    public function showWishlist(
        Request $request,
        string $sourceId,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        PublicAwardMedicineSync $sync
    ): View {
        $this->authorizeView($request, $registry);

        // A favorite is private to its owner. Never load by source ID alone.
        $favorite = AwardWishlist::query()
            ->where('user_id', (int) $request->user('web')->id)
            ->where('source_id', $sourceId)
            ->firstOrFail();
        $item = $favorite->snapshot;
        abort_unless(is_array($item)
            && is_scalar($item['id'] ?? null)
            && (string) $item['id'] === $sourceId, 404);

        $application = $registry->find('pharma');

        return view('ClientPortal::applications.pharma.award-price-detail', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'item' => $item,
            'match' => $sync->inspect($item),
            'keyword' => $favorite->search_keyword,
            'page' => $favorite->source_page,
            'sourceId' => $sourceId,
            'canSync' => $registry->userCan($request->user('web'), 'client.pharma.products.create'),
            'fromWishlist' => true,
        ]);
    }

    public function show(Request $request, string $sourceId, ApplicationRegistry $registry, ClientPortalSettingsService $settings, PublicAwardPriceLookup $lookup, PublicAwardMedicineSync $sync): View
    {
        $this->authorizeView($request, $registry);
        $input = $request->validate([
            'keyword' => ['required', 'string', 'min:2', 'max:200'],
            'page' => ['nullable', 'integer', 'between:1,100'],
        ]);
        $page = (int) ($input['page'] ?? 1);
        $result = $lookup->search($input['keyword'], $page);
        abort_unless($result['success'] ?? false, 503, 'Không thể xác minh dữ liệu trúng thầu.');
        $item = collect($result['items'])->first(fn (array $row): bool =>
            is_scalar($row['id'] ?? null) && (string) $row['id'] === $sourceId);
        abort_unless(is_array($item), 404);

        $application = $registry->find('pharma');
        return view('ClientPortal::applications.pharma.award-price-detail', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'item' => $item,
            'match' => $sync->inspect($item),
            'keyword' => $input['keyword'],
            'page' => $page,
            'sourceId' => $sourceId,
            'canSync' => $registry->userCan($request->user('web'), 'client.pharma.products.create'),
            'fromWishlist' => false,
        ]);
    }

    public function preview(Request $request, ApplicationRegistry $registry, PublicAwardPriceLookup $lookup, PublicAwardMedicineSync $sync): View
    {
        $this->authorizeSync($request, $registry);
        $input = $this->validatedSelection($request);
        $items = $this->selectedItems($lookup, $input);
        $rows = array_map(fn (array $item): array => ['item' => $item, 'match' => $sync->inspect($item)], $items);
        $token = bin2hex(random_bytes(24));
        Cache::put('pharma:award-preview:'.hash('sha256', $token), [
            'user_id' => (int) $request->user('web')->id,
            'items' => $items,
        ], now()->addMinutes(10));

        return view('ClientPortal::applications.pharma.award-sync-preview', [
            'rows' => $rows, 'token' => $token, 'keyword' => $input['keyword'],
        ]);
    }

    public function sync(Request $request, ApplicationRegistry $registry, PublicAwardMedicineSync $sync): RedirectResponse
    {
        $this->authorizeSync($request, $registry);
        $input = $request->validate([
            'token' => ['required', 'string', 'size:48'],
            'confirmed' => ['accepted'],
        ]);
        $cacheKey = 'pharma:award-preview:'.hash('sha256', $input['token']);
        $preview = Cache::get($cacheKey);
        if (! is_array($preview) || (int) ($preview['user_id'] ?? 0) !== (int) $request->user('web')->id) {
            throw ValidationException::withMessages(['selected' => 'Phiên xác nhận đã hết hạn; vui lòng tra cứu và chọn lại.']);
        }
        $rows = $preview['items'] ?? [];
        // Validate all rows before any write. Never permit a review-only row.
        foreach ($rows as $item) {
            $match = $sync->inspect($item);
            if ($match['status'] === 'review') {
                throw ValidationException::withMessages(['selected' => $match['reason']]);
            }
        }

        $created = 0;
        $updated = 0;
        DB::transaction(function () use ($rows, $sync, &$created, &$updated): void {
            foreach ($rows as $item) {
                $outcome = $sync->sync($item, true);
                $outcome['created'] ? $created++ : $updated++;
            }
        });
        Cache::forget($cacheKey);

        return redirect()->route('client.pharma.award-prices')
            ->with('success', "Đã đồng bộ {$updated} nguồn vào thuốc hiện có và tạo {$created} thuốc mới cần kiểm tra.");
    }

    private function validatedSelection(Request $request): array
    {
        return $request->validate([
            'keyword' => ['required', 'string', 'min:2', 'max:200'],
            'page' => ['required', 'integer', 'between:1,100'],
            'selected' => ['required', 'array', 'min:1', 'max:20'],
            'selected.*' => ['required', 'string', 'max:160', 'distinct'],
        ]);
    }

    private function selectedItems(PublicAwardPriceLookup $lookup, array $input): array
    {
        $result = $lookup->search($input['keyword'], (int) $input['page']);
        if (! ($result['success'] ?? false)) {
            throw ValidationException::withMessages(['selected' => 'Không thể xác minh lại dữ liệu nguồn.']);
        }
        $indexed = [];
        foreach ($result['items'] as $item) {
            if (is_scalar($item['id'] ?? null)) {
                $indexed[(string) $item['id']] = $item;
            }
        }
        $items = [];
        foreach ($input['selected'] as $id) {
            if (! isset($indexed[$id])) {
                throw ValidationException::withMessages(['selected' => 'Bản ghi đã chọn không còn trong trang dữ liệu nguồn.']);
            }
            $items[] = $indexed[$id];
        }

        return $items;
    }

    private function authorizeView(Request $request, ApplicationRegistry $registry): void
    {
        abort_unless($registry->find('pharma') && $request->user('web')
            && $registry->userCan($request->user('web'), 'client.pharma.dav-prices.view'), 403);
    }

    private function authorizeSync(Request $request, ApplicationRegistry $registry): void
    {
        $this->authorizeView($request, $registry);
        abort_unless($registry->userCan($request->user('web'), 'client.pharma.products.create'), 403);
    }
}
