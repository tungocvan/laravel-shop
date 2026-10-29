<?php

namespace Modules\Demo\Http\Controllers\Projects\DebtManagement;

use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DebtManagementAssetController extends Controller
{
    private const ASSETS = [
        'css/app.css' => 'text/css; charset=UTF-8',
        'js/core.js' => 'text/javascript; charset=UTF-8',
        'js/menu-builder.js' => 'text/javascript; charset=UTF-8',
        'js/app.js' => 'text/javascript; charset=UTF-8',
        'js/server-datasets.js' => 'text/javascript; charset=UTF-8',
        'shared/menu-editor.css' => 'text/css; charset=UTF-8',
        'shared/menu-editor.js' => 'text/javascript; charset=UTF-8',
    ];

    public function __invoke(string $asset): BinaryFileResponse
    {
        abort_unless(isset(self::ASSETS[$asset]), 404);

        $path = base_path(str_starts_with($asset, 'shared/')
            ? 'Modules/Demo/resources/assets/'.$asset
            : 'Modules/Demo/resources/assets/projects/debt-management/'.$asset);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => self::ASSETS[$asset],
            'Cache-Control' => 'public, max-age=300, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
