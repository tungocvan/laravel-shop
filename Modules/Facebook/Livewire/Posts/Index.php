<?php

namespace Modules\Facebook\Livewire\Posts;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Facebook\Models\FacebookPost;
use Modules\Facebook\Services\FacebookPostService;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public int|string $perPage = 10;

    public array $perPageOptions = [10, 25, 50, 100, 'All'];

    private FacebookPostService $posts;

    public function boot(FacebookPostService $posts): void
    {
        $this->posts = $posts;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function publish(int $id): void
    {
        $this->authorizePermission('facebook.posts.publish');
        $this->posts->queueNow($id);
        session()->flash('success', 'Bài đăng đã được đưa vào hàng đợi.');
    }

    public function retry(int $id): void
    {
        $this->authorizePermission('facebook.posts.retry');
        $this->posts->queueNow($id);
        session()->flash('success', 'Đã đưa bài thất bại vào queue để thử lại.');
    }

    public function cancel(int $id): void
    {
        $this->authorizePermission('facebook.posts.update');
        $this->posts->cancel($id);
        session()->flash('success', 'Đã hủy bài đăng.');
    }

    public function duplicate(int $id): void
    {
        $this->authorizePermission('facebook.posts.create');
        $copy = $this->posts->duplicate($id);
        session()->flash('success', 'Đã nhân bản thành bản nháp mới.');
        $this->redirectRoute('admin.facebook.posts.edit', ['id' => $copy->id]);
    }

    public function delete(int $id): void
    {
        $this->authorizePermission('facebook.posts.delete');
        $this->posts->deleteDraft($id);
        session()->flash('success', 'Đã xóa bài nháp.');
    }

    public function render(): View
    {
        $this->authorizePermission('facebook.posts.view');

        return view('Facebook::livewire.posts.index', [
            'posts' => $this->posts->paginate([
                'search' => $this->search,
                'status' => $this->status,
            ], $this->perPage),
            'statuses' => FacebookPost::STATUSES,
        ]);
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();

        abort_unless($user, 403);
        Gate::forUser($user)->authorize($permission);
    }
}
