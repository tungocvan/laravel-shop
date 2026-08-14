<?php

namespace Modules\Ebook\Livewire;

use Livewire\Component;
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
        $rendered = app(MarkdownService::class)->render($document, $documents->content($document));
        $navigation = app(EbookNavigationService::class);

        return view('Ebook::livewire.ebook-viewer', [
            'document' => $document,
            'tree' => $navigation->tree(),
            'breadcrumbs' => $navigation->breadcrumbs($document),
            'html' => $rendered['html'],
            'toc' => $rendered['toc'],
        ]);
    }

    private function authorizeAdmin(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}
