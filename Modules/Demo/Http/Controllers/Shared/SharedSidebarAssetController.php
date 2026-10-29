<?php

namespace Modules\Demo\Http\Controllers\Shared;

use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class SharedSidebarAssetController extends Controller
{
    private const ASSETS = [
        'menu-builder.css' => 'text/css; charset=UTF-8',
        'menu-builder.js' => 'text/javascript; charset=UTF-8',
    ];

    public function __invoke(string $asset): BinaryFileResponse
    {
        abort_unless(isset(self::ASSETS[$asset]), 404);

        $path = base_path('Modules/Demo/resources/assets/shared/sidebar-menu/'.$asset);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => self::ASSETS[$asset],
            'Cache-Control' => 'public, max-age=300, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
