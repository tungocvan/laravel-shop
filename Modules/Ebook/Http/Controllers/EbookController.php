<?php

namespace Modules\Ebook\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Ebook\Services\EbookDocumentService;
use Modules\Ebook\Services\MarkdownService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EbookController extends Controller
{
    public function index(): View
    {
        return view('Ebook::pages.ebook.index');
    }

    public function show(int $document): View
    {
        app(EbookDocumentService::class)->find($document);

        return view('Ebook::pages.ebook.show', [
            'documentId' => $document,
        ]);
    }

    public function asset(Request $request, int $document): StreamedResponse
    {
        $ebookDocument = app(EbookDocumentService::class)->find($document);
        $resolved = app(MarkdownService::class)->resolveAssetPath(
            $ebookDocument,
            (string) $request->query('path', '')
        );

        $extension = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
        abort_unless(in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true), 404);

        $disk = Storage::disk((string) config('ebook.ebook.disk', 'local'));
        abort_unless($disk->exists($resolved), 404);

        return $disk->response($resolved, basename($resolved), [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
