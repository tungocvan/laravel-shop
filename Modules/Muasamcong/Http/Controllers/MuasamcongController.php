<?php

namespace Modules\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Muasamcong\Models\PricingResult;
use Modules\Muasamcong\Models\PricingWishlist;

class MuasamcongController extends Controller
{
    public function index(): View
    {
        return view('Muasamcong::muasamcong');
    }

    public function contractors(): View
    {
        return view('Muasamcong::contractors');
    }

    public function hsmt(): View
    {
        return view('Muasamcong::hsmt');
    }

    public function synced(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));

        $items = PricingResult::query()
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($nested) use ($keyword): void {
                    $nested->where('ten_thuoc', 'like', "%{$keyword}%")
                        ->orWhere('ten_hoat_chat', 'like', "%{$keyword}%")
                        ->orWhere('nhom_thuoc', 'like', "%{$keyword}%")
                        ->orWhere('ma_tbmt', 'like', "%{$keyword}%")
                        ->orWhere('ten_cdt_bmt', 'like', "%{$keyword}%");
                });
            })
            ->orderByDesc('synced_at')
            ->paginate(20)
            ->withQueryString();

        return view('Muasamcong::synced', compact('items', 'keyword'));
    }

    public function wishlist(Request $request): View
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user !== null, 403);

        $keyword = trim((string) $request->query('q', ''));

        $items = PricingWishlist::query()
            ->where('user_id', (int) $user->getAuthIdentifier())
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($nested) use ($keyword): void {
                    $nested->where('medicine_name', 'like', "%{$keyword}%")
                        ->orWhere('active_ingredient', 'like', "%{$keyword}%")
                        ->orWhere('medicine_group', 'like', "%{$keyword}%")
                        ->orWhere('ma_tbmt', 'like', "%{$keyword}%")
                        ->orWhere('search_keyword', 'like', "%{$keyword}%");
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('Muasamcong::wishlist', compact('items', 'keyword'));
    }

    public function config(): View
    {
        return view('Muasamcong::config');
    }
}
