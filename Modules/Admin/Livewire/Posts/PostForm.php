<?php

namespace Modules\Admin\Livewire\Posts;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Modules\Website\Models\Post;
use Modules\Website\Models\Category;
use Modules\Website\Models\Tag;

class PostForm extends Component
{
    use WithFileUploads;

    public $postId;
    public $isEdit = false;

    // Fields bảng wp_posts
    public $name;
    public $slug;
    public $summary;
    public $content;
    public $thumbnail; // Đường dẫn ảnh cũ
    public $new_thumbnail; // File upload mới
    
    public $status = 'published';
    public $is_featured = false;
    
    public $meta_title;
    public $meta_description;

    // Relations
    public $selectedCategories = []; // Mảng ID danh mục được chọn
    public $inputTags = ''; // String tags

    // Mount dữ liệu từ Controller truyền vào (qua View Wrapper)
    public function mount($id = null)
    {
        if ($id) {
            $this->postId = $id;
            $this->isEdit = true;
            
            // Load bài viết kèm danh mục và tags
            $post = Post::with('categories', 'tags')->findOrFail($id);

            $this->name = $post->name;
            $this->slug = $post->slug;
            $this->summary = $post->summary;
            $this->content = $post->content;
            $this->thumbnail = $post->thumbnail;
            $this->status = $post->status;
            $this->is_featured = (bool) $post->is_featured;
            $this->meta_title = $post->meta_title;
            $this->meta_description = $post->meta_description;

            // Load danh mục đã chọn (Pluck ID)
            $this->selectedCategories = $post->categories->pluck('id')->toArray();
            
            // Load tags (Implode name)
            $this->inputTags = $post->tags->pluck('name')->implode(', ');
        }
    }

    // Tự động tạo slug khi nhập tên (nếu đang tạo mới hoặc slug rỗng)
    public function updatedName($val)
    {
        // Tự động tạo Slug
        if (!$this->isEdit || empty($this->slug)) {
            $this->slug = Str::slug($val);
        }

        // Tự động điền Meta Title (Nếu ô này đang trống)
        if (empty($this->meta_title)) {
            $this->meta_title = $val;
        }
    }

    // 2. KHI NGƯỜI DÙNG GÕ TÓM TẮT
    public function updatedSummary($val)
    {
        // Tự động điền Meta Description (Nếu ô này đang trống)
        if (empty($this->meta_description)) {
            $this->meta_description = $val;
        }
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|max:255',
            'slug' => 'required|unique:wp_posts,slug,' . $this->postId,
            'status' => 'required|in:published,draft,hidden',
            'new_thumbnail' => 'nullable|image|max:2048',
        ]);

        // 1. Xử lý Upload Ảnh
        $imagePath = $this->thumbnail;
        if ($this->new_thumbnail) {
            $imagePath = $this->new_thumbnail->store('posts', 'public');
        }

        $finalMetaTitle = $this->meta_title ?: $this->name;
        $finalMetaDesc = $this->meta_description ?: $this->summary;

        // 2. Chuẩn bị dữ liệu cơ bản
        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'content' => $this->content,
            'thumbnail' => $imagePath,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'meta_title' => $finalMetaTitle,
            'meta_description' => $finalMetaDesc,
            'user_id' => Auth::id(),
        ];

        // 3. Xử lý logic ngày đăng (published_at)
        // Chỉ thêm published_at = now() nếu đây là bài viết MỚI (không phải edit)
        if (!$this->isEdit) {
            $data['published_at'] = now();
        }
        // Nếu là Edit ($this->isEdit = true), ta không thêm key 'published_at' vào mảng $data
        // thì Laravel sẽ tự động BỎ QUA cột đó, giữ nguyên giá trị cũ trong DB.

        // 4. Lưu bài viết
        $post = Post::updateOrCreate(
            ['id' => $this->postId],
            $data
        );

        // 5. Sync Categories
        $post->categories()->sync($this->selectedCategories);

        // 6. Sync Tags
        $tagIds = [];
        if (!empty($this->inputTags)) {
            $tagsArray = explode(',', $this->inputTags);
            foreach ($tagsArray as $tagName) {
                $tagName = trim($tagName);
                if ($tagName) {
                    $tag = Tag::firstOrCreate(
                        ['name' => $tagName],
                        ['slug' => Str::slug($tagName)]
                    );
                    $tagIds[] = $tag->id;
                }
            }
        }
        $post->tags()->sync($tagIds);

        session()->flash('success', $this->isEdit ? 'Cập nhật bài viết thành công.' : 'Thêm bài viết mới thành công.');
        return redirect()->route('admin.posts.index');
    }

    public function render()
    {
        // Lấy danh mục từ bảng 'categories' có type = 'post'
        $categories = Category::where('type', 'post')->get();

        return view('Admin::livewire.posts.post-form', [
            'categories' => $categories
        ]);
    }
}