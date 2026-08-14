<?php

namespace Modules\Ebook\Livewire\Document;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Ebook\Models\EbookDocument;
use Modules\Ebook\Models\EbookFolder;
use Modules\Ebook\Services\EbookAccessService;
use Modules\Ebook\Services\EbookDocumentService;
use Modules\Ebook\Services\MarkdownService;

class DocumentIndex extends Component
{
    use WithFileUploads;

    public ?int $documentId = null;
    public ?int $folderId = null;
    public string $title = '';
    public string $slug = '';
    public string $description = '';
    public string $content = '';
    public int $sortOrder = 0;
    public bool $isActive = true;
    public ?string $expectedHash = null;
    public string $workspace = 'editor';
    public string $editorMode = 'source';
    public $upload;

    public function mount(): void
    {
        $this->authorizeAdmin('ebook.view');
    }

    public function showEditor(): void
    {
        $this->authorizeAdmin('ebook.view');
        $this->workspace = 'editor';
    }

    public function showList(): void
    {
        $this->authorizeAdmin('ebook.view');
        $this->workspace = 'list';
    }

    public function showSource(): void
    {
        $this->authorizeAdmin('ebook.view');
        $this->editorMode = 'source';
    }

    public function showSplit(): void
    {
        $this->authorizeAdmin('ebook.view');
        $this->editorMode = 'split';
    }

    public function showPreview(): void
    {
        $this->authorizeAdmin('ebook.view');
        $this->editorMode = 'preview';
    }

    #[On('ebook-start-new-document')]
    public function startNew(): void
    {
        $this->authorizeAdmin('ebook.create');
        $this->resetForm();
        $this->dispatch('ebook-focus-document-title');
    }

    public function edit(int $id): void
    {
        $this->authorizeAdmin('ebook.update');
        $document = app(EbookDocumentService::class)->find($id);
        app(EbookAccessService::class)->authorizeView(auth('admin')->user(), $document);
        $this->hydrateFromDocument($document);
        $this->workspace = 'editor';
        $this->editorMode = 'source';
    }

    public function save(): void
    {
        $isCreating = $this->documentId === null;
        $permission = $isCreating ? 'ebook.create' : 'ebook.update';
        $this->authorizeAdmin($permission);
        $data = $this->validate([
            'folderId' => ['required', 'integer', 'exists:ebook_folders,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'sortOrder' => ['integer', 'min:0'],
            'isActive' => ['boolean'],
        ]);

        $payload = [
            'folder_id' => $data['folderId'],
            'title' => $data['title'],
            'description' => $data['description'],
            'content' => $data['content'],
            'sort_order' => $data['sortOrder'],
            'is_active' => $data['isActive'],
        ];

        if (filled($data['slug'] ?? null)) {
            $payload['slug'] = $data['slug'];
        }

        $service = app(EbookDocumentService::class);
        if ($isCreating) {
            $document = $service->create($payload);
            $document->viewers()->syncWithoutDetaching([(int) auth('admin')->id()]);
        } else {
            $current = $service->find((int) $this->documentId);
            app(EbookAccessService::class)->authorizeView(auth('admin')->user(), $current);
            $payload['expected_hash'] = $this->expectedHash;
            $document = $service->update((int) $this->documentId, $payload);
        }

        $currentMode = $this->editorMode;
        $this->hydrateFromDocument($document, preserveContent: true);
        $this->workspace = 'editor';
        $this->editorMode = $currentMode;
        $this->resetValidation();
        $this->reset('upload');

        session()->flash('ebook_document_success', 'Đã lưu tài liệu. Bạn có thể tiếp tục soạn thảo.');
    }

    public function uploadMarkdown(): void
    {
        $this->authorizeAdmin('ebook.upload');
        $this->validate([
            'folderId' => ['required', 'integer', 'exists:ebook_folders,id'],
            'upload' => ['required', 'file', 'extensions:md', 'max:'.config('ebook.ebook.upload_max_kb', 2048)],
        ]);

        $document = app(EbookDocumentService::class)->upload((int) $this->folderId, $this->upload);
        $document->viewers()->syncWithoutDetaching([(int) auth('admin')->id()]);
        $this->reset('upload');
        session()->flash('ebook_document_success', 'Đã upload tài liệu Markdown.');
    }

    public function delete(int $id): void
    {
        $this->authorizeAdmin('ebook.delete');
        $document = app(EbookDocumentService::class)->find($id);
        app(EbookAccessService::class)->authorizeView(auth('admin')->user(), $document);
        app(EbookDocumentService::class)->delete($id);
        if ($this->documentId === $id) {
            $this->resetForm();
        }
        session()->flash('ebook_document_success', 'Đã xóa tài liệu.');
    }

    public function resetForm(): void
    {
        $this->reset(['documentId', 'folderId', 'title', 'slug', 'description', 'content', 'sortOrder', 'expectedHash', 'upload']);
        $this->resetValidation();
        $this->isActive = true;
        $this->workspace = 'editor';
        $this->editorMode = 'source';
    }

    public function render()
    {
        $previewDocument = $this->documentId
            ? EbookDocument::query()->find($this->documentId)
            : null;

        if ($previewDocument !== null) {
            app(EbookAccessService::class)->authorizeView(auth('admin')->user(), $previewDocument);
        }

        $preview = app(MarkdownService::class)->renderPreview($this->content, $previewDocument);
        $documents = app(EbookAccessService::class)
            ->visibleDocuments(auth('admin')->user())
            ->with('folder:id,name')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate(10);

        return view('Ebook::livewire.document.document-index', [
            'folders' => EbookFolder::query()->orderBy('name')->get(['id', 'name']),
            'documents' => $documents,
            'previewHtml' => $preview['html'],
        ]);
    }

    private function hydrateFromDocument(EbookDocument $document, bool $preserveContent = false): void
    {
        $this->documentId = (int) $document->id;
        $this->folderId = (int) $document->folder_id;
        $this->title = $document->title;
        $this->slug = $document->slug;
        $this->description = (string) ($document->description ?? '');
        if (! $preserveContent) {
            $this->content = app(EbookDocumentService::class)->content($document);
        }
        $this->sortOrder = (int) $document->sort_order;
        $this->isActive = (bool) $document->is_active;
        $this->expectedHash = $document->content_hash;
    }

    private function authorizeAdmin(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}
