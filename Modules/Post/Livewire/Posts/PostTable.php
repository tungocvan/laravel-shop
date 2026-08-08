<?php

namespace Modules\Post\Livewire\Posts;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Post\Services\ImportExport;
use Modules\Post\Services\PostService;

class PostTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterCategory = '';

    public string $filterStatus = '';

    public int $perPage = 10;

    public array $selected = [];

    public bool $selectAll = false;

    public bool $isImporting = false;

    public array $perPageOptions = PostService::PER_PAGE_OPTIONS;

    public function mount(): void
    {
        $this->authorizeAdmin('view_post');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedFilterCategory(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedPerPage(): void
    {
        $posts = app(PostService::class);

        $this->perPage = $posts->normalizePerPage($this->perPage);
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatingPage(): void
    {
        $this->resetSelection();
    }

    public function resetSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll(bool $value): void
    {
        $posts = app(PostService::class);

        $this->selected = $value ? $posts->currentPageIds($this->filters()) : [];
    }

    public function deleteSelected(): void
    {
        $posts = app(PostService::class);

        $this->authorizeAdmin('delete_post');

        $this->validate([
            'selected' => ['required', 'array'],
            'selected.*' => ['integer'],
        ]);

        $deleted = $posts->bulkDelete($this->selected);

        $this->resetSelection();
        session()->flash('success', "Đã xóa {$deleted} bài viết được chọn.");
    }

    public function clone(int $id): void
    {
        $posts = app(PostService::class);

        $this->authorizeAdmin('view_post');
        $this->authorizeAdmin('create_post');

        $posts->clone($id, auth('admin')->id());

        session()->flash('success', 'Đã nhân bản bài viết thành công.');
    }

    public function export()
    {
        $importExport = app(ImportExport::class);

        $this->authorizeAdmin('view_post');

        $path = $importExport->export(array_merge($this->filters(), [
            'ids' => $this->selected,
        ]));

        return Storage::disk('public')->download($path);
    }

    public function delete(int $id): void
    {
        $posts = app(PostService::class);

        $this->authorizeAdmin('delete_post');

        $posts->delete($id);

        $this->resetSelection();
        session()->flash('success', 'Đã xóa bài viết.');
    }

    public function render()
    {
        $posts = app(PostService::class);

        return view('Post::livewire.posts.post-table', [
            'posts' => $posts->paginateForAdmin($this->filters()),
            'categories' => $posts->postCategoryOptions(),
        ]);
    }

    private function filters(): array
    {
        return [
            'search' => $this->search,
            'category_id' => $this->filterCategory,
            'status' => $this->filterStatus,
            'per_page' => $this->perPage,
        ];
    }

    private function authorizeAdmin(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}
