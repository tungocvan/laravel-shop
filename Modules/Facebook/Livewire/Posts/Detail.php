<?php

namespace Modules\Facebook\Livewire\Posts;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Modules\Facebook\Services\FacebookPostService;

class Detail extends Component
{
    public int $id;

    private FacebookPostService $posts;

    public function boot(FacebookPostService $posts): void
    {
        $this->posts = $posts;
    }

    public function mount(int $id): void
    {
        $this->id = $id;
    }

    public function render(): View
    {
        $this->authorizePermission('facebook.posts.view');

        return view('Facebook::livewire.posts.detail', [
            'post' => $this->posts->find($this->id),
        ]);
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();

        abort_unless($user, 403);
        Gate::forUser($user)->authorize($permission);
    }
}
