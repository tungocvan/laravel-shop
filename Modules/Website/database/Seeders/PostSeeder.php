<?php

namespace Modules\Website\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Modules\Website\Models\Post;
use Modules\Website\Models\Category;
// php artisan db:seed --class="Modules\Website\database\Seeders\PostSeeder"
class PostSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Dọn dẹp dữ liệu cũ
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Post::truncate();
        DB::table('category_post')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Lấy danh mục
        $categories = Category::where('type', 'post')->get();
        if ($categories->isEmpty()) {
            $this->command->error('❌ Vui lòng chạy PostCategorySeeder trước!');
            return;
        }

        // 3. Kho ảnh chất lượng cao (Unsplash)
        $images = [
            'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1486312338219-ce68d2c6f44d?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1515378791036-0648a3ef77b2?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1501504905252-473c47e087f8?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=80',
        ];

        // 4. Tạo bài viết
        for ($i = 1; $i <= 41; $i++) {
            $name = "Bài viết chuyên sâu về xu hướng " . ($i * 10) . ": " . Str::random(5);
            if($i==41) {
                $name = "Thể lệ chương trình khuyến mãi tháng 10";
            }
            
            $slug = Str::slug($name); // Đảm bảo slug chuẩn URL

            // Nội dung giả lập HTML
            $content = '
                <p class="lead">Đây là phần mở đầu (Sapo) cực kỳ hấp dẫn, tóm tắt nội dung chính của bài viết để thu hút người đọc.</p>
                <h2>1. Vấn đề đặt ra là gì?</h2>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam.</p>
                <blockquote>"Sáng tạo là thông minh và vui vẻ." - Albert Einstein</blockquote>
                <h2>2. Giải pháp đột phá</h2>
                <p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p>
                <ul>
                    <li>Điểm mạnh thứ nhất.</li>
                    <li>Điểm mạnh thứ hai.</li>
                    <li>Điểm mạnh thứ ba.</li>
                </ul>
                <p>Kết luận lại, chúng ta cần thay đổi tư duy để bắt kịp xu hướng.</p>
            ';

            $post = Post::create([
                'name' => $name,
                'slug' => $slug,
                'summary' => 'Tóm tắt ngắn gọn khoảng 150 ký tự về nội dung bài viết để hiển thị trên thẻ bài viết ngoài trang chủ.',
                'content' => $content,
                'thumbnail' => $images[array_rand($images)],
                'is_featured' => ($i === 1), // Bài đầu tiên là Featured
                'status' => 'published',
                'views' => rand(100, 5000),
                'user_id' => 1,
                'published_at' => Carbon::now()->subDays(rand(0, 30)),
                'meta_title' => $name,
                'meta_description' => 'Mô tả SEO...'
            ]);

            // Gắn vào 1 danh mục ngẫu nhiên
            $post->categories()->attach($categories->random()->id);
        }

        $this->command->info('✅ Đã tạo 40 bài viết chuẩn chỉnh.');
    }
}
