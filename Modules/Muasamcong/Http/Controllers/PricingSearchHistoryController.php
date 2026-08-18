<?php

namespace Modules\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Muasamcong\Services\PricingSearchSnapshotService;

class PricingSearchHistoryController extends Controller
{
    public function destroy(Request $request, PricingSearchSnapshotService $snapshots): RedirectResponse
    {
        $validated = $request->validate([
            'keyword' => ['required', 'string', 'min:2', 'max:200'],
        ]);

        $deleted = $snapshots->delete($validated['keyword']);

        return redirect()
            ->route('muasamcong.index')
            ->with('status', $deleted ? 'Đã xóa tra cứu đã lưu.' : 'Tra cứu đã lưu không còn tồn tại.');
    }

    public function clear(PricingSearchSnapshotService $snapshots): RedirectResponse
    {
        $deleted = $snapshots->clear();

        return redirect()
            ->route('muasamcong.index')
            ->with('status', "Đã xóa {$deleted} tra cứu đã lưu.");
    }
}
