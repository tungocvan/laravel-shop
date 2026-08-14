<?php

namespace Modules\Ebook\Livewire\Document;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Ebook\Models\EbookDocument;
use Modules\Ebook\Models\EbookFolder;
use Modules\Ebook\Services\EbookDocumentService;

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
    public $upload;

    public function mount(): void
    {
        $this->authorizeAdmin('ebook.view');
    }

    public function edit(int $id): void
    {
        $this->authorizeAdmin('ebook.update');
        $document = app(EbookDocumentService::class)->find($id);
        $this->documentId = (int) $document->id;
        $this->folderId = (int) $document->folder_id;
        $this->title = $document->title;
        $this->slug = $document->slug;
        $this->description = (string) ($document->description ?? '');
        $this->content = app(EbookDocumentService::class)->content($document);
        $this->sortOrder = (int) $document->sort_order;
        $this->isActive = (bool) $document->is_active;
        $this->expectedHash = $document->content_hash;
    }

    public function save(): void
    {
        $permission = $this->documentId ? 'ebook.update' : 'ebook.create';
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
        if ($this->documentId) {
            $payload['expected_hash'] = $this->expectedHash;
            $service->update($this->documentId, $payload);
        } else {
            $service->create($payload);
        }

        $this->resetForm();
        session()->flash('ebook_document_success', 'Đã lưu tài liệu.');
    }

    public function uploadMarkdown(): void
    {
        $this->authorizeAdmin('ebook.upload');
        $this->validate([
            'folderId' => ['required', 'integer', 'exists:ebook_folders,id'],
            'upload' => ['required', 'file', 'extensions:md', 'max:'.config('ebook.ebook.upload_max_kb', 2048)],
        ]);

        app(EbookDocumentService::class)->upload((int) $this->folderId, $this->upload);
        $this->reset('upload');
        session()->flash('ebook_document_success', 'Đã upload tài liệu Markdown.');
    }

    public function delete(int $id): void
    {
        $this->authorizeAdmin('ebook.delete');
        app(EbookDocumentService::class)->delete($id);
        if ($this->documentId === $id) {
            $this->resetForm();
        }
        session()->flash('ebook_document_success', 'Đã xóa tài liệu.');
    }

    public function resetForm(): void
    {
        $this->reset(['documentId', 'title', 'slug', 'description', 'content', 'sortOrder', 'expectedHash', 'upload']);
        $this->isActive = true;
    }

    public function render()
    {
        return view('Ebook::livewire.document.document-index', [
            'folders' => EbookFolder::query()->orderBy('name')->get(['id', 'name']),
            'documents' => EbookDocument::query()->with('folder:id,name')->orderBy('sort_order')->orderBy('title')->paginate(10),
        ]);
    }

    private function authorizeAdmin(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}
