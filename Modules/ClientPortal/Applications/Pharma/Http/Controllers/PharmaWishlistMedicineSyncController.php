<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\Pharma\Models\LookupWishlist;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Services\RegistrationWishlistMedicineSync;
use RuntimeException;

final class PharmaWishlistMedicineSyncController extends Controller
{
    public function preview(Request $request, string $sourceType, string $sourceId,
        ApplicationRegistry $registry, RegistrationWishlistMedicineSync $sync): View|RedirectResponse
    {
        $favorite = $this->favorite($request, $registry, $sourceType, $sourceId);
        if (in_array($sourceType, ['dav_price', 'traditional_price'], true)) {
            return $this->pricePreview($favorite);
        }
        try {
            $candidate = $sync->candidate($favorite);
        } catch (RuntimeException $exception) {
            report($exception);
            throw ValidationException::withMessages(['medicine' => 'Không thể xác minh dữ liệu nguồn. Vui lòng thử lại.']);
        }
        $match = $sync->inspect($candidate);
        $units = Medicine::query()->whereNotNull('unit')->where('unit', '!=', '')
            ->distinct()->orderBy('unit')->pluck('unit')->all();

        return view('ClientPortal::applications.pharma.lookup-wishlist-import-preview',
            compact('favorite', 'candidate', 'match', 'units'));
    }

    public function store(Request $request, string $sourceType, string $sourceId,
        ApplicationRegistry $registry, RegistrationWishlistMedicineSync $sync): RedirectResponse
    {
        $favorite = $this->favorite($request, $registry, $sourceType, $sourceId);
        abort_unless(in_array($sourceType, ['dav_registration', 'traditional_registration'], true), 404);
        $input = $request->validate([
            'unit' => ['required', 'string', 'max:255'],
            'confirmed' => ['accepted'],
        ]);
        try {
            $medicine = $sync->import($favorite, $input['unit']);
        } catch (RuntimeException $exception) {
            report($exception);
            throw ValidationException::withMessages(['medicine' => 'Không thể xác minh nguồn công bố. Vui lòng thử lại.']);
        }

        return redirect()->route('client.pharma.products.edit', $medicine->id)
            ->with('success', 'Đã tạo thuốc từ công bố. Vui lòng kiểm tra và hoàn thiện hồ sơ.');
    }

    private function favorite(Request $request, ApplicationRegistry $registry,
        string $sourceType, string $sourceId): LookupWishlist
    {
        $user = $request->user('web');
        abort_unless($user && $registry->find('pharma')
            && $registry->userCan($user, 'client.pharma.dav-prices.view')
            && $registry->userCan($user, 'client.pharma.products.create'), 403);

        return LookupWishlist::query()->where('user_id', $user->id)
            ->where('source_type', $sourceType)->where('source_id', $sourceId)->firstOrFail();
    }

    private function pricePreview(LookupWishlist $favorite): RedirectResponse
    {
        $context = $favorite->search_context ?? [];
        $q = trim((string) ($context['q'] ?? ''));
        $page = (int) ($context['page'] ?? 0);
        if ($q === '' || mb_strlen($q) > 120 || $page < 1 || $page > 1000) {
            throw ValidationException::withMessages(['medicine' => 'Thiếu ngữ cảnh nguồn. Vui lòng lưu lại từ kết quả tra cứu.']);
        }

        $route = $favorite->source_type === 'dav_price'
            ? 'client.pharma.dav-prices.import.preview'
            : 'client.pharma.traditional-prices.import.preview';

        // Use the existing source re-fetch, matcher, conflict detection and confirmation.
        return redirect()->route($route, ['q' => $q, 'page' => $page, 'source_id' => $favorite->source_id]);
    }
}
