<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Pharma\Services\DavDomesticGmpCrawler;
use Modules\Pharma\Services\DavDomesticGmpSnapshot;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PharmaDomesticGmpController extends Controller
{
    public function index(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        DavDomesticGmpSnapshot $snapshots
    ): View {
        [$application, $feature] = $this->authorizedFeature($request, $registry);
        $snapshot = null;
        $error = null;
        try {
            $snapshot = $snapshots->current();
        } catch (RuntimeException $exception) {
            report($exception);
            $error = 'Dữ liệu GMP đã lưu không đầy đủ. Hãy kiểm tra cập nhật lại.';
        }

        return view('ClientPortal::applications.pharma.domestic-gmp', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'featurePresentation' => $settings->featurePresentation($application['key'], $feature),
            'snapshot' => $snapshot,
            'error' => $error,
        ]);
    }

    public function refresh(
        Request $request,
        ApplicationRegistry $registry,
        DavDomesticGmpCrawler $crawler,
        DavDomesticGmpSnapshot $snapshots
    ) {
        $this->authorizedFeature($request, $registry);
        try {
            $result = $snapshots->refresh($crawler);
        } catch (RuntimeException $exception) {
            report($exception);
            return redirect()->route('client.pharma.domestic-gmp')
                ->with('gmp_error', 'Không thể cập nhật đầy đủ ba Excel từ DAV. Dữ liệu đang sử dụng được giữ nguyên.');
        }

        return redirect()->route('client.pharma.domestic-gmp')
            ->with('gmp_success', $result['status'] === 'unchanged'
                ? 'Đã kiểm tra DAV: ba file Excel không thay đổi.'
                : 'Đã kiểm tra DAV và cập nhật thành công ba file Excel mới nhất.');
    }

    public function download(
        Request $request,
        string $group,
        ApplicationRegistry $registry,
        DavDomesticGmpSnapshot $snapshots
    ): BinaryFileResponse {
        $this->authorizedFeature($request, $registry);
        abort_unless(in_array($group, ['manufacturers', 'packaging', 'business_certificates'], true), 404);
        try {
            $snapshot = $snapshots->current();
            abort_if($snapshot === null, 404);
            $path = $snapshots->filePath($snapshot['version'], $group);
            $disk = Storage::disk('local');
            return response()->download(
                $disk->path($path),
                $group.'-dav-gmp-'.$snapshot['version'].'.xlsx',
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            );
        } catch (RuntimeException $exception) {
            report($exception);
            abort(503, 'Dữ liệu Excel GMP đã lưu không khả dụng.');
        }
    }

    private function authorizedFeature(Request $request, ApplicationRegistry $registry): array
    {
        $application = $registry->find('pharma');
        abort_unless($application !== null && $request->user('web')
            && $registry->userCan($request->user('web'), 'client.pharma.dav-prices.view'), 403);
        $feature = collect($application['features'] ?? [])->first(
            fn (array $candidate): bool => ($candidate['key'] ?? null) === 'domestic-gmp'
        );
        abort_if($feature === null, 404);

        return [$application, $feature];
    }
}
