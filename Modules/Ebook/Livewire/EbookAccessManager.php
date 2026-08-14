<?php

namespace Modules\Ebook\Livewire;

use App\Models\User;
use Livewire\Component;
use Modules\Ebook\Models\EbookDocument;

class EbookAccessManager extends Component
{
    public ?int $documentId = null;
    public string $documentSearch = '';
    public string $userSearch = '';
    public array $viewerIds = [];

    public function mount(): void
    {
        $this->authorizeAdmin('ebook.update');
    }

    public function selectDocument(int $documentId): void
    {
        $this->authorizeAdmin('ebook.update');
        $document = EbookDocument::query()->findOrFail($documentId);
        $this->documentId = (int) $document->id;
        $this->viewerIds = $document->viewers()->pluck('users.id')->map(fn ($id): int => (int) $id)->all();
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->authorizeAdmin('ebook.update');
        $data = $this->validate([
            'documentId' => ['required', 'integer', 'exists:ebook_documents,id'],
            'viewerIds' => ['array'],
            'viewerIds.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $document = EbookDocument::query()->findOrFail((int) $data['documentId']);
        $document->viewers()->sync(collect($data['viewerIds'] ?? [])->map(fn ($id): int => (int) $id)->unique()->values()->all());

        session()->flash('ebook_access_success', 'Đã cập nhật danh sách người được xem tài liệu.');
    }

    public function render()
    {
        $documentSearch = trim($this->documentSearch);
        $userSearch = trim($this->userSearch);

        $documents = EbookDocument::query()
            ->with('folder:id,name')
            ->withCount('viewers')
            ->when($documentSearch !== '', function ($query) use ($documentSearch): void {
                $query->where(function ($inner) use ($documentSearch): void {
                    $inner->where('title', 'like', '%'.$documentSearch.'%')
                        ->orWhere('file_name', 'like', '%'.$documentSearch.'%');
                });
            })
            ->orderBy('title')
            ->limit(50)
            ->get(['id', 'folder_id', 'title', 'file_name']);

        $users = User::query()
            ->when($userSearch !== '', function ($query) use ($userSearch): void {
                $query->where(function ($inner) use ($userSearch): void {
                    $inner->where('name', 'like', '%'.$userSearch.'%')
                        ->orWhere('email', 'like', '%'.$userSearch.'%');
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'email']);

        $selectedDocument = $this->documentId
            ? EbookDocument::query()->with('folder:id,name')->find($this->documentId)
            : null;

        return view('Ebook::livewire.ebook-access-manager', compact('documents', 'users', 'selectedDocument'));
    }

    private function authorizeAdmin(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}
