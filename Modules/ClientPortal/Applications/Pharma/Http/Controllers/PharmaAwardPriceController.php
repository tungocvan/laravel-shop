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

final class PharmaAwardPriceController extends Controller
{
    public function index(Request $request, PublicAwardPriceLookup $lookup, ApplicationRegistry $registry, ClientPortalSettingsService $settings): View
    {
        $this->authorizeView($request, $registry);
        $validated = $request->validate([
            'keyword' => ['nullable', 'string', 'min:2', 'max:200'],
            'page' => ['nullable', 'integer', 'between:1,100'],
            'fresh' => ['nullable', 'boolean'],
            'unit' => ['nullable', 'string', 'max:100'],
        ]);
        $keyword = trim((string) ($validated['keyword'] ?? ''));
        $page = (int) ($validated['page'] ?? 1);
        $result = $keyword === '' ? null : $lookup->search($keyword, $page, (bool) ($validated['fresh'] ?? false));
        $items = $result['items'] ?? [];
        $unit = trim((string) ($validated['unit'] ?? ''));
        if ($unit !== '') {
            $items = array_values(array_filter($items, fn (array $item): bool => mb_strtolower(trim((string) ($item['donViTinh'] ?? ''))) === mb_strtolower($unit)));
        }
        $prices = array_values(array_map('floatval', array_filter(array_column($items, 'donGia'), 'is_numeric')));
        if ($result !== null) {
        $result['items'] = $items;
        $result['stats'] = [
            'count' => count($items),
            'min' => $prices ? min($prices) : null,
            'max' => $prices ? max($prices) : null,
            'avg' => $prices ? array_sum($prices) / count($prices) : null,
        ];
        }

        $application = $registry->find('pharma');
        return view('ClientPortal::applications.pharma.award-prices', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'keyword' => $keyword, 'page' => $page, 'unit' => $unit,
            'result' => $result,
            'canSync' => $registry->userCan($request->user('web'), 'client.pharma.products.create'),
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
