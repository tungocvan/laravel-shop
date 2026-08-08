<?php

namespace Modules\Post\database\seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Category\Models\Category;
use Modules\Post\Models\Post;
use Modules\Post\Models\Tag;

class PostSampleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            DB::table('category_types')->updateOrInsert(
                ['type' => 'post'],
                [
                    'title' => 'Danh mục Bài viết',
                    'icon' => 'newspaper',
                    'sort_order' => 2,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $categoryIds = $this->seedCategories();
            $tagIds = $this->seedTags();

            $this->seedPosts($categoryIds, $tagIds);
        });
    }

    private function seedCategories(): array
    {
        $categories = [
            [
                'name' => 'Tin tức y tế',
                'slug' => 'tin-tuc-y-te',
                'children' => [
                    ['name' => 'Cập nhật ngành dược', 'slug' => 'cap-nhat-nganh-duoc'],
                    ['name' => 'Chính sách sức khỏe', 'slug' => 'chinh-sach-suc-khoe'],
                ],
            ],
            [
                'name' => 'Sống khỏe',
                'slug' => 'song-khoe',
                'children' => [
                    ['name' => 'Dinh dưỡng hằng ngày', 'slug' => 'dinh-duong-hang-ngay'],
                    ['name' => 'Vận động và phục hồi', 'slug' => 'van-dong-va-phuc-hoi'],
                ],
            ],
            [
                'name' => 'Dược phẩm',
                'slug' => 'duoc-pham',
                'children' => [
                    ['name' => 'Sử dụng thuốc an toàn', 'slug' => 'su-dung-thuoc-an-toan'],
                    ['name' => 'Tủ thuốc gia đình', 'slug' => 'tu-thuoc-gia-dinh'],
                ],
            ],
            [
                'name' => 'Chăm sóc cá nhân',
                'slug' => 'cham-soc-ca-nhan',
                'children' => [
                    ['name' => 'Da liễu cơ bản', 'slug' => 'da-lieu-co-ban'],
                    ['name' => 'Chăm sóc mẹ và bé', 'slug' => 'cham-soc-me-va-be'],
                ],
            ],
        ];

        $ids = [];
        $sortOrder = 1;

        foreach ($categories as $category) {
            $parent = $this->updateOrCreateCategory($category['name'], $category['slug'], null, $sortOrder++);
            $ids[$category['slug']] = $parent->id;

            foreach ($category['children'] as $child) {
                $childModel = $this->updateOrCreateCategory(
                    $child['name'],
                    $child['slug'],
                    $parent->id,
                    $sortOrder++
                );

                $ids[$child['slug']] = $childModel->id;
            }
        }

        return $ids;
    }

    private function updateOrCreateCategory(string $name, string $slug, ?int $parentId, int $sortOrder): Category
    {
        $storedSlug = $this->postCategorySlug($slug);

        return Category::query()->updateOrCreate(
            [
                'slug' => $storedSlug,
            ],
            [
                'name' => $name,
                'type' => 'post',
                'type_title' => 'Danh mục Bài viết',
                'parent_id' => $parentId,
                'description' => 'Danh mục mẫu cho bài viết thuộc nhóm '.$name.'.',
                'is_active' => true,
                'sort_order' => $sortOrder,
                'meta_title' => $name,
                'meta_description' => 'Các bài viết thuộc nhóm '.$name.'.',
            ]
        );
    }

    private function postCategorySlug(string $slug): string
    {
        $existingPostCategory = Category::query()
            ->where('type', 'post')
            ->where('slug', $slug)
            ->first();

        if ($existingPostCategory) {
            return $slug;
        }

        if (! Category::query()->where('slug', $slug)->exists()) {
            return $slug;
        }

        $base = 'post-'.$slug;
        $candidate = $base;
        $counter = 2;

        while (Category::query()->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    private function seedTags(): array
    {
        $tags = [
            'suc-khoe' => 'Sức khỏe',
            'duoc-pham' => 'Dược phẩm',
            'dinh-duong' => 'Dinh dưỡng',
            'phong-benh' => 'Phòng bệnh',
            'tu-van' => 'Tư vấn',
            'gia-dinh' => 'Gia đình',
            'me-va-be' => 'Mẹ và bé',
            'da-lieu' => 'Da liễu',
            'van-dong' => 'Vận động',
            'inafo' => 'INAFO',
        ];

        $ids = [];

        foreach ($tags as $slug => $name) {
            $ids[$slug] = Tag::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            )->id;
        }

        return $ids;
    }

    private function seedPosts(array $categoryIds, array $tagIds): void
    {
        $userId = User::query()->value('id');

        foreach ($this->posts() as $index => $item) {
            $slug = Str::slug($item['title']);
            $status = $item['status'] ?? ($index % 7 === 0 ? 'draft' : 'published');

            $post = Post::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $item['title'],
                    'summary' => $item['summary'],
                    'content' => $this->contentFor($item['title'], $item['summary']),
                    'thumbnail' => 'https://placehold.co/900x506?text='.rawurlencode($item['title']),
                    'is_featured' => $index < 5,
                    'status' => $status,
                    'views' => 120 + ($index * 37),
                    'user_id' => $userId,
                    'published_at' => $status === 'published' ? now()->subDays($index + 1) : null,
                    'meta_title' => $item['title'],
                    'meta_description' => $item['summary'],
                ]
            );

            $post->categories()->sync(
                collect($item['category_slugs'])
                    ->map(fn (string $slug) => $categoryIds[$slug] ?? null)
                    ->filter()
                    ->values()
                    ->all()
            );

            $post->tags()->sync(
                collect($item['tag_slugs'])
                    ->map(fn (string $slug) => $tagIds[$slug] ?? null)
                    ->filter()
                    ->values()
                    ->all()
            );
        }
    }

    private function posts(): array
    {
        return [
            [
                'title' => '5 nguyên tắc đọc nhãn thuốc trước khi sử dụng',
                'summary' => 'Những thông tin quan trọng cần kiểm tra trên nhãn thuốc để dùng đúng và an toàn.',
                'category_slugs' => ['su-dung-thuoc-an-toan', 'duoc-pham'],
                'tag_slugs' => ['duoc-pham', 'tu-van', 'suc-khoe'],
            ],
            [
                'title' => 'Tủ thuốc gia đình nên có gì trong mùa mưa',
                'summary' => 'Gợi ý các nhóm vật dụng và thuốc thông dụng cần chuẩn bị cho gia đình.',
                'category_slugs' => ['tu-thuoc-gia-dinh', 'duoc-pham'],
                'tag_slugs' => ['gia-dinh', 'phong-benh', 'duoc-pham'],
            ],
            [
                'title' => 'Dinh dưỡng giúp tăng đề kháng cho người bận rộn',
                'summary' => 'Các thói quen ăn uống đơn giản giúp cơ thể duy trì nền tảng miễn dịch tốt.',
                'category_slugs' => ['dinh-duong-hang-ngay', 'song-khoe'],
                'tag_slugs' => ['dinh-duong', 'suc-khoe', 'phong-benh'],
            ],
            [
                'title' => 'Khi nào nên đo huyết áp tại nhà',
                'summary' => 'Các tình huống nên theo dõi huyết áp và cách ghi nhận chỉ số đúng hơn.',
                'category_slugs' => ['song-khoe', 'tu-thuoc-gia-dinh'],
                'tag_slugs' => ['suc-khoe', 'gia-dinh', 'tu-van'],
            ],
            [
                'title' => 'Chăm sóc da nhạy cảm trong thời tiết nóng ẩm',
                'summary' => 'Cách lựa chọn sản phẩm dịu nhẹ và hạn chế kích ứng khi thời tiết thay đổi.',
                'category_slugs' => ['da-lieu-co-ban', 'cham-soc-ca-nhan'],
                'tag_slugs' => ['da-lieu', 'suc-khoe', 'tu-van'],
            ],
            [
                'title' => 'Những dấu hiệu cần bổ sung nước và điện giải',
                'summary' => 'Nhận biết sớm tình trạng mất nước nhẹ trong sinh hoạt và vận động hằng ngày.',
                'category_slugs' => ['van-dong-va-phuc-hoi', 'song-khoe'],
                'tag_slugs' => ['van-dong', 'suc-khoe', 'dinh-duong'],
            ],
            [
                'title' => 'Lưu ý khi dùng thuốc cho trẻ nhỏ',
                'summary' => 'Các nguyên tắc an toàn phụ huynh nên nhớ khi sử dụng thuốc cho trẻ.',
                'category_slugs' => ['cham-soc-me-va-be', 'su-dung-thuoc-an-toan'],
                'tag_slugs' => ['me-va-be', 'duoc-pham', 'tu-van'],
                'status' => 'draft',
            ],
            [
                'title' => 'Cập nhật xu hướng chuyển đổi số trong nhà thuốc',
                'summary' => 'Những thay đổi trong vận hành, dữ liệu và trải nghiệm khách hàng ngành dược.',
                'category_slugs' => ['cap-nhat-nganh-duoc', 'tin-tuc-y-te'],
                'tag_slugs' => ['duoc-pham', 'inafo', 'tu-van'],
            ],
            [
                'title' => 'Phòng bệnh hô hấp khi giao mùa',
                'summary' => 'Các việc nhỏ giúp giảm nguy cơ mắc bệnh hô hấp khi nhiệt độ thay đổi.',
                'category_slugs' => ['song-khoe', 'chinh-sach-suc-khoe'],
                'tag_slugs' => ['phong-benh', 'suc-khoe', 'gia-dinh'],
            ],
            [
                'title' => 'Cách bảo quản thuốc trong gia đình',
                'summary' => 'Nhiệt độ, độ ẩm và vị trí cất giữ thuốc ảnh hưởng trực tiếp đến chất lượng thuốc.',
                'category_slugs' => ['tu-thuoc-gia-dinh', 'su-dung-thuoc-an-toan'],
                'tag_slugs' => ['duoc-pham', 'gia-dinh', 'tu-van'],
            ],
            [
                'title' => 'Thói quen vận động nhẹ cho dân văn phòng',
                'summary' => 'Một số bài tập ngắn giúp giảm căng cơ và cải thiện tuần hoàn trong ngày làm việc.',
                'category_slugs' => ['van-dong-va-phuc-hoi', 'song-khoe'],
                'tag_slugs' => ['van-dong', 'suc-khoe', 'phong-benh'],
            ],
            [
                'title' => 'Vai trò của vitamin D với sức khỏe xương',
                'summary' => 'Vitamin D hỗ trợ hấp thu canxi và góp phần duy trì hệ xương chắc khỏe.',
                'category_slugs' => ['dinh-duong-hang-ngay', 'song-khoe'],
                'tag_slugs' => ['dinh-duong', 'suc-khoe', 'phong-benh'],
            ],
            [
                'title' => 'Mẹo xây dựng thực đơn lành mạnh trong tuần',
                'summary' => 'Cách chuẩn bị thực đơn cân bằng, dễ áp dụng cho gia đình bận rộn.',
                'category_slugs' => ['dinh-duong-hang-ngay', 'song-khoe'],
                'tag_slugs' => ['dinh-duong', 'gia-dinh', 'suc-khoe'],
            ],
            [
                'title' => 'Những sai lầm thường gặp khi tự mua thuốc',
                'summary' => 'Các thói quen cần tránh để giảm nguy cơ dùng sai thuốc hoặc sai liều.',
                'category_slugs' => ['su-dung-thuoc-an-toan', 'duoc-pham'],
                'tag_slugs' => ['duoc-pham', 'tu-van', 'phong-benh'],
                'status' => 'hidden',
            ],
            [
                'title' => 'Chăm sóc sức khỏe người cao tuổi tại nhà',
                'summary' => 'Các điểm cần theo dõi thường xuyên để hỗ trợ người cao tuổi sống khỏe hơn.',
                'category_slugs' => ['song-khoe', 'tu-thuoc-gia-dinh'],
                'tag_slugs' => ['gia-dinh', 'suc-khoe', 'tu-van'],
            ],
            [
                'title' => 'Làm sạch da đúng cách sau khi dùng kem chống nắng',
                'summary' => 'Quy trình làm sạch phù hợp giúp hạn chế bít tắc và bảo vệ hàng rào da.',
                'category_slugs' => ['da-lieu-co-ban', 'cham-soc-ca-nhan'],
                'tag_slugs' => ['da-lieu', 'suc-khoe', 'tu-van'],
            ],
            [
                'title' => 'Chuẩn bị sức khỏe cho trẻ trước mùa tựu trường',
                'summary' => 'Những việc phụ huynh có thể chuẩn bị để trẻ thích nghi tốt hơn khi đi học.',
                'category_slugs' => ['cham-soc-me-va-be', 'song-khoe'],
                'tag_slugs' => ['me-va-be', 'phong-benh', 'gia-dinh'],
            ],
            [
                'title' => 'Hướng dẫn đọc chỉ số SpO2 cơ bản',
                'summary' => 'Cách hiểu chỉ số SpO2 và những lưu ý khi sử dụng thiết bị đo tại nhà.',
                'category_slugs' => ['tu-thuoc-gia-dinh', 'song-khoe'],
                'tag_slugs' => ['suc-khoe', 'gia-dinh', 'tu-van'],
            ],
            [
                'title' => 'An toàn khi dùng thực phẩm bổ sung',
                'summary' => 'Thực phẩm bổ sung cần được dùng đúng mục tiêu và không thay thế điều trị y khoa.',
                'category_slugs' => ['dinh-duong-hang-ngay', 'su-dung-thuoc-an-toan'],
                'tag_slugs' => ['dinh-duong', 'duoc-pham', 'tu-van'],
            ],
            [
                'title' => 'INAFO chia sẻ tiêu chuẩn tư vấn khách hàng tại nhà thuốc',
                'summary' => 'Những điểm cốt lõi trong quy trình tư vấn giúp khách hàng nhận thông tin rõ ràng hơn.',
                'category_slugs' => ['cap-nhat-nganh-duoc', 'tin-tuc-y-te'],
                'tag_slugs' => ['inafo', 'duoc-pham', 'tu-van'],
            ],
        ];
    }

    private function contentFor(string $title, string $summary): string
    {
        return '<p><strong>'.$title.'</strong></p>'
            .'<p>'.$summary.'</p>'
            .'<p>Nội dung mẫu này dùng để kiểm thử giao diện danh sách, chi tiết bài viết, bộ lọc danh mục và SEO metadata trong môi trường phát triển.</p>'
            .'<p>Khi triển khai dữ liệu thật, hãy thay thế bằng nội dung đã được kiểm duyệt chuyên môn và phù hợp với quy định truyền thông y tế.</p>';
    }
}
