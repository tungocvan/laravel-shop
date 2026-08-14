<?php

namespace Modules\Ebook\Livewire\Folder;

use Livewire\Component;
use Modules\Ebook\Services\EbookFolderService;

class FolderIndex extends Component
{
    public ?int $folderId = null;
    public ?int $parentId = null;
    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public int $sortOrder = 0;
    public bool $isActive = true;

    public function mount(): void
    {
        $this->authorizeAdmin('ebook.view');
    }

    public function edit(int $id): void
    {
        $this->authorizeAdmin('ebook.update');
        $folder = app(EbookFolderService::class)->find($id);

        $this->folderId = (int) $folder->id;
        $this->parentId = $folder->parent_id ? (int) $folder->parent_id : null;
        $this->name = $folder->name;
        $this->slug = $folder->slug;
        $this->description = (string) ($folder->description ?? '');
        $this->sortOrder = (int) $folder->sort_order;
        $this->isActive = (bool) $folder->is_active;
    }

    public function save(): void
    {
        $this->authorizeAdmin($this->folderId ? 'ebook.update' : 'ebook.create');

        $validated = $this->validate([
            'parentId' => ['nullable', 'integer', 'exists:ebook_folders,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sortOrder' => ['required', 'integer', 'min:0'],
            'isActive' => ['boolean'],
        ]);

        $payload = [
            'parent_id' => $validated['parentId'],
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'],
            'sort_order' => $validated['sortOrder'],
            'is_active' => $validated['isActive'],
        ];

        $folders = app(EbookFolderService::class);
        $this->folderId ? $folders->update($this->folderId, $payload) : $folders->create($payload);

        session()->flash('success', $this->folderId ? 'Đã cập nhật thư mục.' : 'Đã tạo thư mục.');
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $this->authorizeAdmin('ebook.delete');
        app(EbookFolderService::class)->delete($id);
        session()->flash('success', 'Đã xóa thư mục.');

        if ($this->folderId === $id) {
            $this->resetForm();
        }
    }

    public function resetForm(): void
    {
        $this->reset(['folderId', 'parentId', 'name', 'slug', 'description']);
        $this->sortOrder = 0;
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render()
    {
        $folders = app(EbookFolderService::class);

        return view('Ebook::livewire.folder.folder-index', [
            'tree' => $folders->tree(),
            'parentOptions' => $folders->options($this->folderId),
        ]);
    }

    private function authorizeAdmin(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}
