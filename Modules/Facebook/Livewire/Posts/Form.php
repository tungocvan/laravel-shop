<?php

namespace Modules\Facebook\Livewire\Posts;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Facebook\Models\FacebookPost;
use Modules\Facebook\Services\FacebookPageService;
use Modules\Facebook\Services\FacebookPostService;

class Form extends Component
{
    use WithFileUploads;

    public ?int $id = null;

    public ?string $scheduledAt = null;

    public $image;

    public array $state = [
        'facebook_page_id' => '',
        'title' => '',
        'message' => '',
        'post_type' => 'text',
        'link_url' => '',
    ];

    private FacebookPostService $posts;

    private FacebookPageService $pages;

    public function boot(FacebookPostService $posts, FacebookPageService $pages): void
    {
        $this->posts = $posts;
        $this->pages = $pages;
    }

    public function mount(?int $id = null): void
    {
        $this->id = $id;

        if (! $id) {
            $this->authorizePermission('facebook.posts.create');

            return;
        }

        $this->authorizePermission('facebook.posts.update');
        $post = $this->posts->find($id);
        $this->state = [
            'facebook_page_id' => (string) $post->facebook_page_id,
            'title' => (string) $post->title,
            'message' => (string) $post->message,
            'post_type' => (string) $post->post_type,
            'link_url' => (string) $post->link_url,
        ];
        $this->scheduledAt = $post->scheduled_at?->format('Y-m-d\TH:i');
    }

    public function saveDraft()
    {
        $data = $this->validate()['state'];
        $post = $this->persistDraft($data);
        session()->flash('success', $this->id ? 'Đã cập nhật bản nháp.' : 'Đã lưu bản nháp.');

        return redirect()->route('admin.facebook.posts.edit', ['id' => $post->id]);
    }

    public function publishNow()
    {
        $data = $this->validate()['state'];
        $post = $this->persistDraft($data);
        $this->posts->queueNow($post->id);

        return redirect()->route('admin.facebook.posts.index')->with('success', 'Bài đăng đã được đưa vào hàng đợi.');
    }

    public function schedulePost()
    {
        $this->validate(array_merge($this->rules(), [
            'scheduledAt' => ['required', 'date', 'after:now'],
        ]));

        $post = $this->id
            ? $this->posts->updateDraft($this->id, $this->state, $this->image)
            : $this->posts->createDraft($this->state, $this->image);

        $this->posts->schedule($post->id, $this->scheduledAt);

        return redirect()->route('admin.facebook.posts.index')->with('success', 'Đã lên lịch bài đăng.');
    }

    public function render(): View
    {
        return view('Facebook::livewire.posts.form', [
            'pageOptions' => $this->pages->activeOptions(),
            'types' => FacebookPost::TYPES,
        ]);
    }

    protected function rules(): array
    {
        return [
            'state.facebook_page_id' => ['required', Rule::exists('facebook_pages', 'id')->where('is_active', true)],
            'state.title' => ['nullable', 'string', 'max:255'],
            'state.message' => ['required_without:state.link_url,image', 'nullable', 'string', 'max:63206'],
            'state.post_type' => ['required', Rule::in(array_keys(FacebookPost::TYPES))],
            'state.link_url' => ['required_if:state.post_type,link', 'nullable', 'url', 'max:2000'],
            'image' => ['required_if:state.post_type,photo', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    private function persistDraft(array $data): FacebookPost
    {
        if ($this->id) {
            return $this->posts->updateDraft($this->id, $data, $this->image);
        }

        return $this->posts->createDraft($data, $this->image);
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();

        abort_unless($user, 403);
        Gate::forUser($user)->authorize($permission);
    }
}
