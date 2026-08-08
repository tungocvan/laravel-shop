<?php

namespace Modules\Product\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;

class ProductSampleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            DB::table('category_types')->updateOrInsert(
                ['type' => 'product'],
                [
                    'title' => 'Danh mục Sản phẩm',
                    'icon' => 'shopping-bag',
                    'sort_order' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $categoryIds = $this->seedCategories();
            $this->seedProducts($categoryIds);
        });
    }

    private function seedCategories(): array
    {
        $categories = [
            [
                'name' => 'Thực phẩm chức năng',
                'slug' => 'thuc-pham-chuc-nang',
                'children' => [
                    ['name' => 'Vitamin và khoáng chất', 'slug' => 'vitamin-va-khoang-chat'],
                    ['name' => 'Hỗ trợ tiêu hóa', 'slug' => 'ho-tro-tieu-hoa'],
                    ['name' => 'Hỗ trợ xương khớp', 'slug' => 'ho-tro-xuong-khop'],
                ],
            ],
            [
                'name' => 'Chăm sóc sức khỏe',
                'slug' => 'cham-soc-suc-khoe',
                'children' => [
                    ['name' => 'Thiết bị y tế gia đình', 'slug' => 'thiet-bi-y-te-gia-dinh'],
                    ['name' => 'Sát khuẩn và bảo vệ', 'slug' => 'sat-khuan-va-bao-ve'],
                ],
            ],
            [
                'name' => 'Dược mỹ phẩm',
                'slug' => 'duoc-my-pham',
                'children' => [
                    ['name' => 'Chăm sóc da', 'slug' => 'cham-soc-da'],
                    ['name' => 'Chăm sóc tóc', 'slug' => 'cham-soc-toc'],
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
        return Category::query()->updateOrCreate(
            [
                'type' => 'product',
                'slug' => $slug,
            ],
            [
                'name' => $name,
                'type_title' => 'Danh mục Sản phẩm',
                'parent_id' => $parentId,
                'description' => 'Danh mục mẫu cho sản phẩm '.$name.'.',
                'is_active' => true,
                'sort_order' => $sortOrder,
                'meta_title' => $name,
                'meta_description' => 'Các sản phẩm thuộc nhóm '.$name.'.',
            ]
        );
    }

    private function seedProducts(array $categoryIds): void
    {
        foreach ($this->products() as $index => $item) {
            $product = Product::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'title' => $item['title'],
                    'short_description' => $item['short_description'],
                    'description' => $item['description'],
                    'regular_price' => $item['regular_price'],
                    'sale_price' => $item['sale_price'],
                    'quantity' => $item['quantity'],
                    'sold_count' => $item['sold_count'],
                    'image' => $item['image'],
                    'gallery' => $item['gallery'],
                    'tags' => $item['tags'],
                    'is_active' => true,
                    'is_featured' => $index < 6,
                    'views' => $item['views'],
                    'affiliate_commission_rate' => $item['affiliate_commission_rate'],
                ]
            );

            $product->categories()->sync(
                collect($item['category_slugs'])
                    ->map(fn (string $slug) => $categoryIds[$slug] ?? null)
                    ->filter()
                    ->values()
                    ->all()
            );
        }
    }

    private function products(): array
    {
        $items = [
            ['Vitamin C 1000mg INAFO', 185000, 159000, 120, ['vitamin-va-khoang-chat', 'thuc-pham-chuc-nang'], ['vitamin-c', 'de-khang']],
            ['Viên uống D3 K2 Plus', 240000, 219000, 85, ['vitamin-va-khoang-chat', 'ho-tro-xuong-khop'], ['d3-k2', 'xuong-khop']],
            ['Omega 3 Fish Oil 1000mg', 320000, 285000, 64, ['thuc-pham-chuc-nang', 'vitamin-va-khoang-chat'], ['omega-3', 'tim-mach']],
            ['Men vi sinh Probiotic 10 chủng', 265000, 239000, 92, ['ho-tro-tieu-hoa', 'thuc-pham-chuc-nang'], ['probiotic', 'tieu-hoa']],
            ['Canxi Nano D3', 210000, 189000, 77, ['ho-tro-xuong-khop', 'vitamin-va-khoang-chat'], ['canxi', 'xuong-khop']],
            ['Glucosamine Joint Care', 390000, 349000, 58, ['ho-tro-xuong-khop', 'thuc-pham-chuc-nang'], ['glucosamine', 'khop']],
            ['Nước muối sinh lý 0.9%', 12000, null, 300, ['sat-khuan-va-bao-ve', 'cham-soc-suc-khoe'], ['nuoc-muoi', 've-sinh']],
            ['Dung dịch rửa tay khô 500ml', 68000, 59000, 180, ['sat-khuan-va-bao-ve', 'cham-soc-suc-khoe'], ['sat-khuan', 'bao-ve']],
            ['Khẩu trang y tế 4 lớp hộp 50 cái', 45000, 39000, 250, ['sat-khuan-va-bao-ve'], ['khau-trang', 'bao-ve']],
            ['Nhiệt kế điện tử đầu mềm', 145000, 129000, 45, ['thiet-bi-y-te-gia-dinh'], ['nhiet-ke', 'thiet-bi-y-te']],
            ['Máy đo huyết áp bắp tay', 890000, 799000, 22, ['thiet-bi-y-te-gia-dinh', 'cham-soc-suc-khoe'], ['huyet-ap', 'thiet-bi-y-te']],
            ['Máy đo SpO2 kẹp ngón', 420000, 379000, 35, ['thiet-bi-y-te-gia-dinh'], ['spo2', 'thiet-bi-y-te']],
            ['Kem dưỡng phục hồi da B5', 295000, 269000, 70, ['cham-soc-da', 'duoc-my-pham'], ['b5', 'phuc-hoi-da']],
            ['Sữa rửa mặt dịu nhẹ pH 5.5', 175000, 159000, 110, ['cham-soc-da', 'duoc-my-pham'], ['sua-rua-mat', 'da-nhay-cam']],
            ['Kem chống nắng SPF50+', 360000, 329000, 88, ['cham-soc-da'], ['chong-nang', 'spf50']],
            ['Serum Niacinamide 10%', 310000, 279000, 52, ['cham-soc-da'], ['serum', 'niacinamide']],
            ['Dầu gội giảm gàu dược liệu', 155000, 139000, 95, ['cham-soc-toc', 'duoc-my-pham'], ['dau-goi', 'giam-gau']],
            ['Dung dịch vệ sinh da đầu', 210000, 189000, 42, ['cham-soc-toc'], ['da-dau', 'cham-soc-toc']],
            ['Kẽm Zinc 15mg', 165000, 149000, 130, ['vitamin-va-khoang-chat'], ['kem', 'zinc']],
            ['Viên uống bổ sung sắt và acid folic', 198000, 179000, 74, ['vitamin-va-khoang-chat', 'thuc-pham-chuc-nang'], ['sat', 'acid-folic']],
        ];

        return collect($items)->map(function (array $item, int $index) {
            [$title, $regularPrice, $salePrice, $quantity, $categorySlugs, $tags] = $item;
            $slug = Str::slug($title);
            $imageNumber = ($index % 12) + 1;

            return [
                'title' => $title,
                'slug' => $slug,
                'short_description' => 'Sản phẩm mẫu '.$title.' dùng cho kiểm thử giao diện và dữ liệu.',
                'description' => 'Mô tả mẫu cho '.$title.'. Nội dung này có thể thay thế bằng thông tin chính thức khi nhập dữ liệu thật.',
                'regular_price' => $regularPrice,
                'sale_price' => $salePrice,
                'quantity' => $quantity,
                'sold_count' => 5 + ($index * 3),
                'image' => 'https://placehold.co/600x600?text='.rawurlencode($title),
                'gallery' => [
                    'https://placehold.co/600x600?text='.rawurlencode($title.' 1'),
                    'https://placehold.co/600x600?text='.rawurlencode($title.' 2'),
                    'https://placehold.co/600x600?text=INAFO+'.str_pad((string) $imageNumber, 2, '0', STR_PAD_LEFT),
                ],
                'tags' => array_values(array_unique(array_merge($tags, ['inafo', 'san-pham-mau']))),
                'category_slugs' => $categorySlugs,
                'views' => 100 + ($index * 17),
                'affiliate_commission_rate' => 3 + ($index % 5),
            ];
        })->all();
    }
}
