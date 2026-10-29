<?php

namespace Modules\Demo\Http\Controllers\Projects\HotelUniform;

use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class HotelUniformAssetController extends Controller
{
    private const ASSETS = [
        'css/app.css' => 'text/css; charset=UTF-8',
        'js/app.js' => 'text/javascript; charset=UTF-8',
        'js/integrations.js' => 'text/javascript; charset=UTF-8',
        'shared/menu-editor.css' => 'text/css; charset=UTF-8',
        'shared/menu-editor.js' => 'text/javascript; charset=UTF-8',
    ];

    public function __invoke(string $asset): BinaryFileResponse
    {
        abort_unless(array_key_exists($asset, self::ASSETS), 404);
        $path = str_starts_with($asset, 'shared/')
            ? base_path('Modules/Demo/resources/assets/shared/'.substr($asset, 7))
            : base_path('Modules/Demo/resources/assets/projects/hotel-uniform/'.$asset);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => self::ASSETS[$asset],
            'Cache-Control' => 'public, max-age=300, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
