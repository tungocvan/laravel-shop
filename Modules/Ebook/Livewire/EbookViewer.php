<?php

namespace Modules\Ebook\Livewire;

use Livewire\Component;
use Modules\Ebook\Services\EbookDocumentService;
use Modules\Ebook\Services\EbookFolderService;
use Modules\Ebook\Services\MarkdownService;

class EbookViewer extends Component
{
    public int $documentId;

    public bool $readingMode = false;

    public function mount(int $documentId): void
    {
        $this->authorizeAdmin('ebook.view');
        $this->documentId = $documentId;
    }

    public function toggleReadingMode(): void
    {
        $this->authorizeAdmin('ebook.view');
        $this->readingMode = ! $this->readingMode;
    }

    public function render()
    {
        $documents = app(EbookDocumentService::class);
        $document = $documents->find($this->documentId);
        $rendered = app(MarkdownService::class)->render($document, $documents->content($document));

        return view('Ebook::livewire.ebook-viewer', [
            'document' => $document,
            'tree' => app(EbookFolderService::class)->tree(),
            'breadcrumbs' => $this->breadcrumbs($document->folder),
            'html' => $rendered['html'],
            'toc' => $rendered['toc'],
        ]);
    }

    private function breadcrumbs($folder): array
    {
        $items = [];
        $current = $folder;

        while ($current !== null) {
            array_unshift($items, [
                'id' => (int) $current->id,
                'name' => $current->name,
            ]);
            $current = $current->parent()->first();
        }

        return $items;
    }

    private function authorizeAdmin(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}
