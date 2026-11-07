<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Pharma\Services\DavDomesticGmpCrawler;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PharmaDomesticGmpController extends Controller
{
    public function index(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        DavDomesticGmpCrawler $crawler
    ): View {
        [$application, $feature] = $this->authorizedFeature($request, $registry);
        $publication = null;
        $error = null;
        try {
            $publication = $crawler->latest();
        } catch (RuntimeException $exception) {
            report($exception);
            $error = 'Không thể kiểm tra bài công bố GMP mới nhất trên DAV. Vui lòng thử lại sau.';
        }

        return view('ClientPortal::applications.pharma.domestic-gmp', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'featurePresentation' => $settings->featurePresentation($application['key'], $feature),
            'publication' => $publication,
            'error' => $error,
        ]);
    }

    public function download(
        Request $request,
        string $group,
        ApplicationRegistry $registry,
        DavDomesticGmpCrawler $crawler
    ): BinaryFileResponse {
        $this->authorizedFeature($request, $registry);
        abort_unless(in_array($group, ['manufacturers', 'packaging', 'business_certificates'], true), 404);
        try {
            // Re-discover from the official category. Never accept an arbitrary URL
            // from a browser, and never serve a stale attachment as the latest.
            $publication = $crawler->latest();
            $file = $publication['files'][$group] ?? null;
            abort_if($file === null, 404);
            $bytes = $crawler->download($file['url']);
        } catch (RuntimeException $exception) {
            report($exception);
            abort(503, 'Không thể tải hoặc xác minh Excel từ DAV.');
        }

        $hash = hash('sha256', $bytes);
        $path = 'pharma/dav-domestic-gmp/'.substr($hash, 0, 2).'/'.$hash.'.xlsx';
        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            $disk->put($path, $bytes);
        }

        return response()->download(
            $disk->path($path),
            $group.'-dav-gmp-'.$hash.'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
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
