<?php

namespace Modules\Post\Livewire\Posts;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Post\Models\Post;
use Modules\Post\Services\PostService;

class PostForm extends Component
{
    use WithFileUploads;

    public $postId;

    public bool $isEdit = false;

    public $name;

    public $slug;

    public $summary;

    public $content;

    public $thumbnail;

    public $new_thumbnail;

    public string $status = 'published';

    public bool $is_featured = false;

    public $meta_title;

    public $meta_description;

    public array $selectedCategories = [];

    public string $inputTags = '';

    public function mount(?int $id = null): void
    {
        $posts = app(PostService::class);

        if (! $id) {
            $this->authorizeAdmin('create_post');

            return;
        }

        $this->authorizeAdmin('edit_post');

        $post = $posts->findForEdit($id);

        $this->postId = $post->id;
        $this->isEdit = true;
        $this->name = $post->name;
        $this->slug = $post->slug;
        $this->summary = $post->summary;
        $this->content = $post->content;
        $this->thumbnail = $post->thumbnail;
        $this->status = $post->status;
        $this->is_featured = (bool) $post->is_featured;
        $this->meta_title = $post->meta_title;
        $this->meta_description = $post->meta_description;
        $this->selectedCategories = $post->categories->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->inputTags = $post->tags->pluck('name')->implode(', ');
    }

    public function updatedName($value): void
    {
        if (! $this->isEdit || blank($this->slug)) {
            $this->slug = Str::slug($value);
        }

        if (blank($this->meta_title)) {
            $this->meta_title = $value;
        }
    }

    public function updatedSummary($value): void
    {
        if (blank($this->meta_description)) {
            $this->meta_description = $value;
        }
    }

    public function save()
    {
        $posts = app(PostService::class);

        $this->authorizeAdmin($this->isEdit ? 'edit_post' : 'create_post');

        $validated = $this->validate($this->rules());

        $payload = array_merge($validated, [
            'category_ids' => $this->selectedCategories,
            'tags' => $this->inputTags,
            'thumbnail' => $this->thumbnail,
            'new_thumbnail' => $this->new_thumbnail,
            'user_id' => auth('admin')->id(),
        ]);

        if ($this->isEdit) {
            $posts->update((int) $this->postId, $payload);
        } else {
            $posts->create($payload);
        }

        session()->flash('success', $this->isEdit ? 'Cập nhật bài viết thành công.' : 'Thêm bài viết mới thành công.');

        return redirect()->route('admin.posts.index');
    }

    public function render()
    {
        $posts = app(PostService::class);

        return view('Post::livewire.posts.post-form', [
            'categories' => $posts->postCategoryOptions(),
        ]);
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new Post())->getTable(), 'slug')->ignore($this->postId),
            ],
            'summary' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'status' => ['required', Rule::in(PostService::STATUSES)],
            'is_featured' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'selectedCategories' => ['array'],
            'selectedCategories.*' => ['integer'],
            'inputTags' => ['nullable', 'string', 'max:1000'],
            'new_thumbnail' => ['nullable', 'image', 'max:2048'],
        ];
    }

    private function authorizeAdmin(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}
