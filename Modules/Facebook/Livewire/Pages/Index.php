<?php

namespace Modules\Facebook\Livewire\Pages;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Facebook\Services\FacebookPageService;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $isActive = '';

    public int|string $perPage = 10;

    public array $perPageOptions = [10, 25, 50, 100, 'All'];

    private FacebookPageService $pages;

    public function boot(FacebookPageService $pages): void
    {
        $this->pages = $pages;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingIsActive(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function verify(int $id): void
    {
        $this->authorizePermission('facebook.pages.manage');
        $result = $this->pages->verifyById($id);
        session()->flash($result->valid ? 'success' : 'error', $result->message);
    }

    public function toggle(int $id, bool $active): void
    {
        $this->authorizePermission('facebook.pages.manage');
        $this->pages->toggleActive($id, $active);
        session()->flash('success', 'Đã cập nhật trạng thái Fanpage.');
    }

    public function setDefault(int $id): void
    {
        $this->authorizePermission('facebook.pages.manage');
        $this->pages->setDefault($id);
        session()->flash('success', 'Đã chọn Fanpage mặc định.');
    }

    public function render(): View
    {
        $this->authorizePermission('facebook.pages.manage');

        return view('Facebook::livewire.pages.index', [
            'pages' => $this->pages->paginate([
                'search' => $this->search,
                'is_active' => $this->isActive,
            ], $this->perPage),
        ]);
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();

        abort_unless($user, 403);
        Gate::forUser($user)->authorize($permission);
    }
}
