<?php

namespace Modules\Ebook\Livewire;

use Livewire\Component;
use Modules\Ebook\Services\EbookAccessService;
use Modules\Ebook\Services\EbookDocumentService;
use Modules\Ebook\Services\EbookEngagementService;
use Modules\Ebook\Services\EbookNavigationService;
use Modules\Ebook\Services\MarkdownService;

class EbookViewer extends Component
{
    public int $documentId;

    public bool $readingMode = false;

    public function mount(int $documentId): void
    {
        $this->authorizeAdmin('ebook.view');
        $this->documentId = $documentId;

        $document = app(EbookDocumentService::class)->find($documentId);
        app(EbookAccessService::class)->authorizeView(auth('admin')->user(), $document);

        app(EbookEngagementService::class)->recordRecent(
            (int) auth('admin')->id(),
            $documentId
        );
    }

    public function toggleReadingMode(): void
    {
        $this->authorizeAdmin('ebook.view');
        $this->readingMode = ! $this->readingMode;
    }

    public function toggleFavorite(): void
    {
        $this->authorizeAdmin('ebook.update');
        app(EbookEngagementService::class)->toggleFavorite($this->documentId);
    }

    public function render()
    {
        $documents = app(EbookDocumentService::class);
        $document = $documents->find($this->documentId);
        app(EbookAccessService::class)->authorizeView(auth('admin')->user(), $document);

        $rendered = app(MarkdownService::class)->render($document, $documents->content($document));
        $navigation = app(EbookNavigationService::class);

        return view('Ebook::livewire.ebook-viewer', [
            'document' => $document,
            'breadcrumbs' => $navigation->breadcrumbs($document),
            'documentPicker' => $navigation->documentPicker(),
            'adjacent' => $navigation->adjacent($document),
            'html' => $rendered['html'],
            'toc' => $rendered['toc'],
        ]);
    }

    private function authorizeAdmin(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}
