<?php

namespace Modules\ClientPortal\Applications\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\ClientPortal\Applications\Muasamcong\Models\PublicShare;
use Modules\ClientPortal\Applications\Muasamcong\Services\ClientPricingSearchService;

class PublicDrugShareController extends Controller
{
    public function store(Request $request, ClientPricingSearchService $search): JsonResponse
    {
        $validated = $request->validate([
            'keyword' => ['required', 'string', 'min:2', 'max:200'],
            'source_id' => ['required', 'uuid'],
        ]);

        $user = $request->user('web');
        abort_if($user === null, 401);

        $searchResult = $search->search(trim($validated['keyword']), $user->getKey());
        $result = $searchResult['result'];
        abort_unless($result['success'] ?? false, 404);

        $item = collect($result['data']['items'] ?? [])->first(
            fn (mixed $row): bool => is_array($row) && (string) ($row['id'] ?? '') === $validated['source_id']
        );
        abort_unless(is_array($item), 404);

        $share = PublicShare::query()->create([
            'token' => Str::random(64),
            'created_by' => $user->getKey(),
            'application_key' => 'muasamcong',
            'feature_key' => 'drug-pricing',
            'source_id' => $validated['source_id'],
            'title' => (string) ($item['tenThuoc'] ?? 'Chi tiết thuốc trúng thầu'),
            'payload' => $this->publicPayload($item),
        ]);

        return response()->json([
            'url' => route('public.muasamcong.drug-share', $share->token),
            'title' => $share->title,
            'text' => $this->shareText($share->payload),
        ], 201);
    }

    public function show(string $token): View
    {
        $share = PublicShare::query()->where('token', $token)->firstOrFail();
        abort_unless($share->isAvailable(), 410);

        $share->forceFill([
            'views_count' => $share->views_count + 1,
            'last_viewed_at' => now(),
        ])->save();

        return view('ClientPortal::public.muasamcong.drug-share', [
            'share' => $share,
            'item' => $share->payload,
        ]);
    }

    public function revoke(Request $request, PublicShare $share): JsonResponse
    {
        $user = $request->user('web');
        abort_if($user === null || (int) $share->created_by !== (int) $user->getKey(), 403);

        $share->forceFill(['revoked_at' => now()])->save();

        return response()->json(['revoked' => true]);
    }

    private function publicPayload(array $item): array
    {
        $allowed = [
            'id', 'tenThuoc', 'tenHoatChat', 'nongDo', 'nhomThuoc', 'groupMedicine', 'duongDung', 'dangBaoChe', 'donViTinh',
            'quyCachDongGoi', 'hanDung', 'donGia', 'soLuong', 'winningName', 'winningCode', 'tenCdtBmt', 'maTbmt', 'soQuyetDinh',
            'ngayBanHanhQuyetDinh', 'ngayDangTaiKqlcnt', 'tenCoSoSanXuat', 'nuocSanXuat', 'gdklh_GPNK',
        ];

        return collect($item)->only($allowed)->all();
    }

    private function shareText(array $item): string
    {
        $parts = array_filter([
            $item['tenThuoc'] ?? null,
            isset($item['tenHoatChat']) ? 'Hoạt chất: '.$item['tenHoatChat'] : null,
            is_numeric($item['donGia'] ?? null) ? 'Giá trúng thầu: '.number_format((float) $item['donGia'], 0, ',', '.').' đ' : null,
            isset($item['maTbmt']) ? 'Mã TBMT: '.$item['maTbmt'] : null,
        ]);

        return implode("\n", $parts);
    }
}
