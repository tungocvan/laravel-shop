<?php

namespace Modules\Admin\Livewire\Posts;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads; // <--- 1. Import
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Modules\Website\Models\Post;
use Modules\Website\Models\Category;
use Modules\Website\Models\Tag;

class PostTable extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $filterCategory = '';
    public $filterStatus = '';
    
    // --- BIẾN MỚI ---
    public $selected = []; // Mảng chứa ID các bài viết được chọn
    public $selectAll = false; // Trạng thái checkbox "Chọn tất cả"
    
    public $importFile; 
    public $isImporting = false;

    // Reset khi search/filter
    public function updatedSearch() { $this->resetPage(); $this->resetSelection(); }
    public function updatedFilterCategory() { $this->resetPage(); $this->resetSelection(); }
    public function updatedFilterStatus() { $this->resetPage(); $this->resetSelection(); }
    
    // Khi chuyển trang thì bỏ chọn
    public function updatingPage() { $this->resetSelection(); }

    public function resetSelection()
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    // --- LOGIC 1: CHỌN TẤT CẢ ---
    public function updatedSelectAll($value)
    {
        if ($value) {
            // Chỉ chọn những bài viết đang hiển thị ở trang hiện tại (tối ưu hiệu năng)
            $this->selected = $this->getQuery()->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    // Helper query chung
    private function getQuery()
    {
        $query = Post::with(['author', 'categories']);
        if ($this->search) $query->where('name', 'like', '%' . $this->search . '%');
        if ($this->filterStatus) $query->where('status', $this->filterStatus);
        if ($this->filterCategory) {
            $query->whereHas('categories', function ($q) {
                $q->where('categories.id', $this->filterCategory);
            });
        }
        return $query->latest();
    }

    // --- LOGIC 2: XÓA HÀNG LOẠT ---
    public function deleteSelected()
    {
        if (empty($this->selected)) return;

        Post::whereIn('id', $this->selected)->delete();
        
        $this->resetSelection();
        session()->flash('success', 'Đã xóa các bài viết được chọn.');
    }
    
    // --- LOGIC 3: NHÂN BẢN BÀI VIẾT (CLONE) ---
    public function clone($id)
    {
        $original = Post::with(['categories', 'tags'])->find($id);
        if (!$original) return;

        DB::transaction(function () use ($original) {
            // 1. Sao chép bài viết
            $clone = $original->replicate();
            $clone->name = $original->name . ' (Copy)';
            $clone->slug = Str::slug($clone->name) . '-' . time(); // Đảm bảo slug unique
            $clone->status = 'draft'; // Nhân bản xong nên để nháp
            $clone->created_at = now();
            $clone->push(); // Lưu lại

            // 2. Sao chép quan hệ Categories
            $clone->categories()->sync($original->categories->pluck('id'));

            // 3. Sao chép quan hệ Tags
            $clone->tags()->sync($original->tags->pluck('id'));
        });

        session()->flash('success', 'Đã nhân bản bài viết thành công.');
    }

    // --- LOGIC 4: EXPORT JSON ---
    public function export()
    {
        $posts = $this->getQuery()->get()->map(function ($post) {
            return [
                'name' => $post->name,
                'summary' => $post->summary,
                'content' => $post->content,
                'status' => $post->status,
                'is_featured' => (bool)$post->is_featured,
                'thumbnail' => $post->thumbnail,
                'categories' => $post->categories->pluck('name')->toArray(),
                'tags' => $post->tags->pluck('name')->toArray(),
            ];
        });

        $fileName = 'posts-export-' . date('Y-m-d-His') . '.json';
        return response()->streamDownload(function () use ($posts) {
            echo $posts->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $fileName);
    }

    // --- LOGIC 5: IMPORT JSON ---
    public function import()
    {
        $this->validate(['importFile' => 'required|mimes:json,txt|max:5120']);

        try {
            $json = json_decode(file_get_contents($this->importFile->getRealPath()), true);
            
            if (!is_array($json)) throw new \Exception("File JSON không hợp lệ.");

            $countNew = 0;
            $countSkip = 0;

            DB::transaction(function () use ($json, &$countNew, &$countSkip) {
                foreach ($json as $item) {
                    $slug = Str::slug($item['name']);

                    // --- KIỂM TRA TRÙNG LẶP ---
                    // Nếu đã có bài viết cùng Slug HOẶC cùng Tên -> Bỏ qua
                    $exists = Post::where('slug', $slug)
                        ->orWhere('name', $item['name'])
                        ->exists();

                    if ($exists) {
                        $countSkip++;
                        continue; // Nhảy sang item tiếp theo
                    }

                    // --- TẠO MỚI ---
                    $post = Post::create([
                        'name' => $item['name'],
                        'slug' => $slug, // Sử dụng slug đã tạo
                        'summary' => $item['summary'] ?? '',
                        'content' => $item['content'] ?? '',
                        'status' => $item['status'] ?? 'draft',
                        'is_featured' => $item['is_featured'] ?? false,
                        'thumbnail' => $item['thumbnail'] ?? null, // Lưu link placehold.co trực tiếp
                        'user_id' => auth()->id(),
                        'published_at' => ($item['status'] == 'published') ? now() : null,
                    ]);

                    // Xử lý Categories
                    if (!empty($item['categories'])) {
                        $catIds = [];
                        foreach ($item['categories'] as $catName) {
                            $cat = Category::firstOrCreate(
                                ['name' => trim($catName), 'type' => 'post'],
                                ['slug' => Str::slug($catName)]
                            );
                            $catIds[] = $cat->id;
                        }
                        $post->categories()->sync($catIds);
                    }

                    // Xử lý Tags
                    if (!empty($item['tags'])) {
                        $tagIds = [];
                        foreach ($item['tags'] as $tagName) {
                            $tag = Tag::firstOrCreate(
                                ['name' => trim($tagName)],
                                ['slug' => Str::slug($tagName)]
                            );
                            $tagIds[] = $tag->id;
                        }
                        $post->tags()->sync($tagIds);
                    }
                    $countNew++;
                }
            });

            $this->isImporting = false;
            $this->importFile = null;
            session()->flash('success', "Import hoàn tất! Thêm mới: {$countNew}, Bỏ qua (trùng): {$countSkip}.");

        } catch (\Exception $e) {
            $this->addError('importFile', 'Lỗi: ' . $e->getMessage());
        }
    }

    // --- RENDER ---
    public function delete($id)
    {
        Post::find($id)->delete();
        session()->flash('success', 'Đã xóa bài viết.');
    }

    public function render()
    {
        $posts = $this->getQuery()->paginate(10);
        $categories = Category::where('type', 'post')->get();

        return view('Admin::livewire.posts.post-table', [
            'posts' => $posts,
            'categories' => $categories
        ]);
    }
}