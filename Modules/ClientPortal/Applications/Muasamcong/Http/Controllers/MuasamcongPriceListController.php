<?php

namespace Modules\ClientPortal\Applications\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\ClientPortal\Jobs\GeneratePriceListExport;
use Modules\ClientPortal\Jobs\SendPriceListExportEmail;
use Modules\ClientPortal\Models\PriceListExport;
use Modules\Muasamcong\Models\PricingResult;
use Modules\Muasamcong\Models\PricingWishlist;
use Modules\Muasamcong\Models\SyncedExportProfile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MuasamcongPriceListController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user('web');
        abort_if(! $user, 401);

        $source = in_array($request->query('source'), ['synced', 'wishlist'], true)
            ? $request->query('source')
            : 'synced';

        $profiles = SyncedExportProfile::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $inactiveProfilesCount = 0;
        $items = $source === 'wishlist'
            ? PricingWishlist::where('user_id', $user->getKey())->latest()->paginate(20)->withQueryString()
            : PricingResult::latest('synced_at')->paginate(20)->withQueryString();
        $exports = PriceListExport::where('user_id', $user->getKey())->latest()->limit(10)->get();
        $canExport = $user->can('client.muasamcong.price-list.export');

        return view('ClientPortal::applications.muasamcong.price-list', compact(
            'source',
            'profiles',
            'inactiveProfilesCount',
            'items',
            'exports',
            'canExport'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->exportUser($request);
        $data = $request->validate([
            'source' => 'required|in:synced,wishlist',
            'profile_id' => 'required|integer|exists:muasamcong_synced_export_profiles,id',
            'selected_ids' => 'required|array|min:1|max:200',
            'selected_ids.*' => 'required|string|max:64',
        ]);

        $profile = SyncedExportProfile::findOrFail($data['profile_id']);
        $ids = array_values(array_unique($data['selected_ids']));

        if ($data['source'] === 'wishlist') {
            $allowed = PricingWishlist::where('user_id', $user->getKey())
                ->whereIn('id', $ids)
                ->pluck('id')
                ->map(fn ($value) => (string) $value)
                ->all();
        } else {
            $allowed = PricingResult::whereIn('source_id', $ids)
                ->pluck('source_id')
                ->map(fn ($value) => (string) $value)
                ->all();
        }

        abort_if(count($allowed) !== count($ids), 403);

        $export = PriceListExport::create([
            'user_id' => $user->getKey(),
            'profile_id' => $profile->id,
            'source' => $data['source'],
            'selected_ids' => $ids,
            'status' => 'queued',
        ]);

        GeneratePriceListExport::dispatch($export->id);

        return back()->with('status', 'Đã đưa yêu cầu xuất Bảng Giá vào Queue bằng cấu hình “'.$profile->name.'”.');
    }

    public function status(Request $request, PriceListExport $export): JsonResponse
    {
        $this->owner($request, $export);

        return response()->json([
            'status' => $export->status,
            'items_count' => $export->items_count,
            'error' => $export->error_message,
            'download_url' => $export->status === 'completed'
                ? route('client.muasamcong.price-list.download', $export)
                : null,
        ]);
    }

    public function download(Request $request, PriceListExport $export): BinaryFileResponse
    {
        $this->owner($request, $export);
        abort_unless(
            $export->status === 'completed'
            && $export->file_path
            && Storage::disk('local')->exists($export->file_path),
            404
        );

        return response()->download(
            Storage::disk('local')->path($export->file_path),
            $export->file_name
        );
    }

    public function share(Request $request, PriceListExport $export): JsonResponse
    {
        $this->exportUser($request);
        $this->owner($request, $export);
        abort_unless($export->status === 'completed', 409);

        if (! $export->share_token) {
            $export->update(['share_token' => Str::random(64)]);
        }

        return response()->json([
            'url' => route('public.muasamcong.price-list', $export->share_token),
        ]);
    }

    public function publicDownload(string $token): BinaryFileResponse
    {
        $export = PriceListExport::where('share_token', $token)
            ->where('status', 'completed')
            ->firstOrFail();

        abort_unless(
            $export->file_path && Storage::disk('local')->exists($export->file_path),
            404
        );

        return response()->download(
            Storage::disk('local')->path($export->file_path),
            $export->file_name
        );
    }

    public function email(Request $request, PriceListExport $export): RedirectResponse
    {
        $this->exportUser($request);
        $this->owner($request, $export);
        abort_unless($export->status === 'completed', 409);

        $data = $request->validate([
            'email' => 'required|email|max:200',
        ]);

        SendPriceListExportEmail::dispatch($export->id, $data['email']);

        return back()->with('status', 'Đã đưa email Bảng Giá vào Queue gửi.');
    }

    private function owner(Request $request, PriceListExport $export): void
    {
        $user = $request->user('web');
        abort_if(! $user || (int) $export->user_id !== (int) $user->getKey(), 403);
    }

    private function exportUser(Request $request)
    {
        $user = $request->user('web');
        abort_if(! $user, 401);
        abort_unless($user->can('client.muasamcong.price-list.export'), 403);

        return $user;
    }
}
