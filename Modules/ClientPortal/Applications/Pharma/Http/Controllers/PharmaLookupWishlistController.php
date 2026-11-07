<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Pharma\Models\LookupWishlist;
use Modules\Pharma\Services\DavDrugRegistrationLookup;
use Modules\Pharma\Services\DavPublicPriceLookup;
use Modules\Pharma\Services\LookupWishlistService;
use Modules\Pharma\Services\MohTraditionalPriceLookup;
use Modules\Pharma\Services\TraditionalDrugRegistrationLookup;
use RuntimeException;

final class PharmaLookupWishlistController extends Controller
{
    public function index(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings
    ): View {
        $userId = $this->authorizeView($request, $registry);
        $filter = $request->validate(['source' => ['nullable', Rule::in(array_merge(array_keys(LookupWishlistService::SOURCES), ['public_award']))]]);
        $source = $filter['source'] ?? '';
        $new = DB::table('pharma_lookup_wishlists')
            ->select(['source_id', 'medicine_name', 'registration_number', 'snapshot', 'search_context', 'created_at'])
            ->selectRaw('source_type')
            ->where('user_id', $userId);
        $award = DB::table('pharma_award_wishlists')
            ->select(['source_id', 'medicine_name', DB::raw('NULL AS registration_number'), 'snapshot', DB::raw('NULL AS search_context'), 'created_at'])
            ->selectRaw("'public_award' AS source_type")
            ->where('user_id', $userId);
        if ($source !== '') {
            if ($source === 'public_award') {
                $new->whereRaw('1 = 0');
            } else {
                $new->where('source_type', $source);
                $award->whereRaw('1 = 0');
            }
        }
        $saved = DB::query()->fromSub($new->unionAll($award), 'wishlist_items')
            ->orderByDesc('created_at')->orderBy('source_type')->orderBy('source_id')
            ->paginate(20)->withQueryString();

        $application = $registry->find('pharma');

        return view('ClientPortal::applications.pharma.lookup-wishlist', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'saved' => $saved,
            'source' => $source,
            'sources' => LookupWishlistService::SOURCES + ['public_award' => 'Thuốc trúng thầu'],
        ]);
    }

    public function show(
        Request $request,
        string $sourceType,
        string $sourceId,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings
    ): View {
        $userId = $this->authorizeView($request, $registry);
        abort_unless(array_key_exists($sourceType, LookupWishlistService::SOURCES), 404);
        $favorite = LookupWishlist::query()->where('user_id', $userId)
            ->where('source_type', $sourceType)->where('source_id', $sourceId)->firstOrFail();
        $application = $registry->find('pharma');

        return view('ClientPortal::applications.pharma.lookup-wishlist-detail', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'favorite' => $favorite,
            'sourceLabel' => LookupWishlistService::SOURCES[$sourceType],
            'sourceRoute' => $this->sourceRoute($sourceType),
        ]);
    }

    public function toggle(
        Request $request,
        ApplicationRegistry $registry,
        LookupWishlistService $wishlist,
        DavPublicPriceLookup $davPrice,
        MohTraditionalPriceLookup $traditionalPrice,
        DavDrugRegistrationLookup $davRegistration,
        TraditionalDrugRegistrationLookup $traditionalRegistration
    ): RedirectResponse {
        $userId = $this->authorizeView($request, $registry);
        $input = $request->validate([
            'source_type' => ['required', Rule::in(array_keys(LookupWishlistService::SOURCES))],
            'source_id' => ['required', 'string', 'max:160'],
            'q' => ['required', 'string', 'min:1', 'max:200'],
            'page' => ['required', 'integer', 'between:1,1000'],
            'field' => ['nullable', Rule::in(array_keys(DavDrugRegistrationLookup::FIELDS))],
        ]);
        $source = $input['source_type'];
        $sourceId = $input['source_id'];
        if ($wishlist->remove($userId, $source, $sourceId)) {
            return back()->with('success', 'Đã bỏ thuốc khỏi danh sách quan tâm.');
        }

        try {
            $rows = match ($source) {
                'dav_price' => $davPrice->search($input['q'], (int) $input['page'])['items'],
                'traditional_price' => $traditionalPrice->search($input['q'], (int) $input['page'])['items'],
                'dav_registration' => $davRegistration->search($input['field'] ?? 'name', $input['q'], (int) $input['page'])['items'],
                'traditional_registration' => $traditionalRegistration->search($input['field'] ?? 'name', $input['q'], (int) $input['page'])['items'],
            };
        } catch (RuntimeException $exception) {
            report($exception);
            throw ValidationException::withMessages(['source_id' => 'Không thể xác minh thuốc từ nguồn công bố. Vui lòng thử lại sau.']);
        }
        $item = collect($rows)->first(static fn (array $row): bool =>
            is_scalar($row['source_id'] ?? null) && hash_equals((string) $row['source_id'], $sourceId));
        if ($item === null) {
            throw ValidationException::withMessages(['source_id' => 'Thuốc không còn trong kết quả nguồn; vui lòng tra cứu lại.']);
        }
        $wishlist->save($userId, $source, $item, [
            'q' => $input['q'],
            'page' => (int) $input['page'],
            'field' => $input['field'] ?? 'name',
        ]);

        return back()->with('success', 'Đã thêm thuốc vào danh sách quan tâm.');
    }

    public function remove(
        Request $request,
        string $sourceType,
        string $sourceId,
        ApplicationRegistry $registry,
        LookupWishlistService $wishlist
    ): RedirectResponse {
        $userId = $this->authorizeView($request, $registry);
        abort_unless(array_key_exists($sourceType, LookupWishlistService::SOURCES), 404);
        $wishlist->remove($userId, $sourceType, $sourceId);

        return redirect()->route('client.pharma.lookups.wishlist')
            ->with('success', 'Đã bỏ thuốc khỏi danh sách quan tâm.');
    }

    private function sourceRoute(string $source): string
    {
        return match ($source) {
            'dav_price' => 'client.pharma.dav-prices',
            'traditional_price' => 'client.pharma.traditional-prices',
            'dav_registration' => 'client.pharma.drug-registrations',
            'traditional_registration' => 'client.pharma.traditional-drug-registrations',
        };
    }

    private function authorizeView(Request $request, ApplicationRegistry $registry): int
    {
        $user = $request->user('web');
        abort_unless($registry->find('pharma') !== null && $user
            && $registry->userCan($user, 'client.pharma.dav-prices.view'), 403);

        return (int) $user->id;
    }
}
