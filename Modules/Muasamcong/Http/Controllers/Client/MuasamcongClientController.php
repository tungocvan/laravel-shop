<?php

namespace Modules\Muasamcong\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\ClientApplicationRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Muasamcong\Services\MuaSamCongService;

class MuasamcongClientController extends Controller
{
    public function dashboard(Request $request, ClientApplicationRegistry $registry): View
    {
        $application = $registry->find('muasamcong');
        abort_if($application === null, 404);

        $user = $request->user('web');
        $features = collect($application['features'] ?? [])
            ->filter(function (array $feature) use ($registry, $user): bool {
                $permission = $feature['permission'] ?? null;

                return $permission === null || ($user !== null && $registry->userCan($user, $permission));
            })
            ->values();

        return view('Muasamcong::client.dashboard', compact('application', 'features'));
    }

    public function drugPricing(Request $request, MuaSamCongService $service): View
    {
        $validated = $request->validate([
            'keyword' => ['nullable', 'string', 'min:2', 'max:200'],
        ]);

        $keyword = trim((string) ($validated['keyword'] ?? ''));
        $result = null;
        $items = collect();
        $summary = [
            'total' => 0,
            'lowest_price' => null,
            'average_price' => null,
            'highest_price' => null,
        ];

        if ($keyword !== '') {
            $result = $service->searchPricing($keyword);

            if ($result['success'] ?? false) {
                $items = collect($result['data']['items'] ?? [])
                    ->filter(fn (mixed $item): bool => is_array($item))
                    ->values();

                $prices = $items
                    ->pluck('donGia')
                    ->filter(fn (mixed $price): bool => is_numeric($price))
                    ->map(fn (mixed $price): float => (float) $price);

                $summary = [
                    'total' => (int) ($result['data']['total'] ?? $items->count()),
                    'lowest_price' => $prices->isEmpty() ? null : $prices->min(),
                    'average_price' => $prices->isEmpty() ? null : $prices->avg(),
                    'highest_price' => $prices->isEmpty() ? null : $prices->max(),
                ];
            }
        }

        return view('Muasamcong::client.drug-pricing', compact(
            'keyword',
            'result',
            'items',
            'summary'
        ));
    }
}
