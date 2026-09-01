<?php

namespace Modules\ClientPortal\Applications\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\ClientPortal\Applications\Muasamcong\Models\PublicShare;

class MuasamcongShareManagementController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user('web');
        abort_if($user === null, 401);

        $shares = PublicShare::query()
            ->where('created_by', $user->getKey())
            ->where('application_key', 'muasamcong')
            ->where('feature_key', 'drug-pricing')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('ClientPortal::applications.muasamcong.shares', compact('shares'));
    }

    public function updateExpiry(Request $request, PublicShare $share): RedirectResponse
    {
        $this->authorizeOwner($request, $share);
        $validated = $request->validate(['expiry' => ['required', 'in:7,30,never']]);

        $share->forceFill([
            'expires_at' => match ($validated['expiry']) {
                '7' => now()->addDays(7),
                '30' => now()->addDays(30),
                default => null,
            },
        ])->save();

        return back()->with('status', 'Đã cập nhật thời hạn link chia sẻ.');
    }

    public function revoke(Request $request, PublicShare $share): RedirectResponse
    {
        $this->authorizeOwner($request, $share);
        $share->forceFill(['revoked_at' => now()])->save();

        return back()->with('status', 'Đã thu hồi link chia sẻ.');
    }

    private function authorizeOwner(Request $request, PublicShare $share): void
    {
        $user = $request->user('web');
        abort_if(
            $user === null
            || (int) $share->created_by !== (int) $user->getKey()
            || $share->application_key !== 'muasamcong'
            || $share->feature_key !== 'drug-pricing',
            403
        );
    }
}
