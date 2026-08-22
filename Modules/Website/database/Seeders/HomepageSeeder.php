<?php

namespace Modules\Website\database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Website\Services\HomepageContentWriteService;

class HomepageSeeder extends Seeder
{
    /**
     * Seed a complete, production-shaped Homepage demo configuration.
     *
     * Run standalone after CategorySeeder/ProductSeeder:
     * php artisan db:seed --class="Modules\\Website\\database\\Seeders\\HomepageSeeder"
     */
    public function run(): void
    {
        $categoryIds = Category::query()
            ->where('type', 'product')
            ->orderBy('id')
            ->limit(8)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $featuredIds = Product::query()
            ->where('is_active', true)
            ->orderByDesc('is_featured')
            ->orderByDesc('sold_count')
            ->orderBy('id')
            ->limit(8)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $sectionOrder = [
            'show_hero',
            'show_categories',
            'show_flash_sale',
            'show_featured',
            'show_new_arrivals',
            'show_best_sellers',
            'show_promo_banner',
            'show_blog_highlight',
            'show_trust_badges',
            'show_newsletter',
        ];

        $layout = collect($sectionOrder)
            ->mapWithKeys(fn (string $key): array => [$key => 'all'])
            ->all();

        $sectionTypes = [
            'hero' => 'hero',
            'categories' => 'category_grid',
            'flash_sale' => 'flash_sale',
            'featured' => 'product_grid',
            'new_arrivals' => 'product_grid',
            'best_sellers' => 'product_grid',
            'promo_banner' => 'promo_banner',
            'blog_highlight' => 'post_grid',
            'trust_badges' => 'trust_badges',
            'newsletter' => 'newsletter',
        ];

        $values = [];
        foreach ($layout as $key => $visibility) {
            $values['home_'.$key] = $visibility;
        }

        $values += [
            'home_category_ids' => $categoryIds,
            'home_featured_ids' => $featuredIds,
            'home_new_arrivals_count' => 10,
            'home_best_sellers_count' => 8,
            'home_blog_count' => 4,
            'home_promo_banner' => [
                'show' => true,
                'image' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1600&q=85',
                'title' => 'Ưu đãi đặc biệt cuối tuần',
                'sub_title' => 'Khám phá hàng ngàn sản phẩm được tuyển chọn với mức giá hấp dẫn dành riêng cho bạn.',
                'btn_text' => 'Mua ngay',
                'link' => '/product',
                'details_link' => '/product',
            ],
            'home_trust_badges' => [
                [
                    'icon' => 'fa-solid fa-truck-fast',
                    'title' => 'Giao hàng nhanh',
                    'sub_title' => 'Miễn phí vận chuyển cho đơn đủ điều kiện',
                ],
                [
                    'icon' => 'fa-solid fa-shield-halved',
                    'title' => 'Thanh toán an toàn',
                    'sub_title' => 'Thông tin thanh toán luôn được bảo mật',
                ],
                [
                    'icon' => 'fa-solid fa-rotate-left',
                    'title' => 'Đổi trả dễ dàng',
                    'sub_title' => 'Hỗ trợ đổi trả theo chính sách cửa hàng',
                ],
                [
                    'icon' => 'fa-solid fa-headset',
                    'title' => 'Hỗ trợ tận tâm',
                    'sub_title' => 'Đội ngũ tư vấn luôn sẵn sàng hỗ trợ',
                ],
            ],
            'home_newsletter' => [
                'show' => true,
                'badge' => 'Tham gia cộng đồng',
                'title' => 'Nhận ưu đãi dành riêng cho thành viên',
                'description' => 'Đăng ký để nhận thông tin sản phẩm mới, chương trình khuyến mãi và các ưu đãi nổi bật từ cửa hàng.',
            ],
        ];

        DB::transaction(function () use ($values, $sectionOrder, $layout, $sectionTypes): void {
            app(HomepageContentWriteService::class)->save(
                $values,
                $sectionOrder,
                $layout,
                $sectionTypes
            );
        });

        $this->command?->info(sprintf(
            '✅ HomepageSeeder: Đã cấu hình 10 sections, %d danh mục và %d sản phẩm nổi bật.',
            count($categoryIds),
            count($featuredIds)
        ));
    }
}
