<?php

namespace Modules\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Muasamcong\Services\PricingWishlistQueryService;

class PricingWishlistController extends Controller
{
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    private const DEFAULT_PER_PAGE = 25;

    public function __invoke(Request $request, PricingWishlistQueryService $queries): View
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user !== null, 403);

        $keyword = trim((string) $request->query('q', ''));
        $requestedPerPage = (int) $request->query('per_page', self::DEFAULT_PER_PAGE);
        $perPage = in_array($requestedPerPage, self::PER_PAGE_OPTIONS, true)
            ? $requestedPerPage
            : self::DEFAULT_PER_PAGE;

        $items = $queries
            ->query((int) $user->getAuthIdentifier(), $keyword)
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('Muasamcong::wishlist', compact('items', 'keyword', 'perPage'));
    }
}
