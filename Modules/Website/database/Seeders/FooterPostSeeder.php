<?php

namespace Modules\Website\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Website\Models\Post;
use Modules\Website\Models\Category;
use Illuminate\Support\Str;
use Carbon\Carbon;
// php artisan db:seed --class="Modules\Website\database\Seeders\FooterPostSeeder"
class FooterPostSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo Danh mục riêng cho các trang này (để dễ quản lý, không lẫn với Tin tức)
        $category = Category::firstOrCreate(
            ['slug' => 'pages'],
            [
                'name' => 'Trang Tĩnh',
                'type' => 'post',
                'is_active' => false // Ẩn khỏi menu chính nếu cần
            ]
        );

        // 2. Danh sách các trang cần tạo cho Footer
        $pages = [
            // --- NHÓM VỀ FLEXBIZ ---
            [
                'name' => 'Câu Chuyện Thương Hiệu',
                'slug' => 'cau-chuyen-thuong-hieu',
                'summary' => 'Hành trình từ một startup nhỏ đến nền tảng thương mại điện tử hàng đầu.',
                'content' => '
                    <h3>Khởi nguồn</h3>
                    <p>FlexBiz được thành lập vào năm 2020 với sứ mệnh đơn giản: mang đến trải nghiệm mua sắm trực tuyến đẳng cấp và tin cậy cho người tiêu dùng Việt Nam.</p>
                    <h3>Sứ mệnh</h3>
                    <p>Chúng tôi không chỉ bán sản phẩm, chúng tôi bán sự an tâm và phong cách sống. Mỗi sản phẩm trên FlexBiz đều được tuyển chọn kỹ lưỡng từ các thương hiệu uy tín toàn cầu.</p>
                    <h3>Tầm nhìn</h3>
                    <p>Trở thành biểu tượng niềm tin hàng đầu về thương mại điện tử tại Đông Nam Á vào năm 2030.</p>
                '
            ],
            [
                'name' => 'Tuyển Dụng',
                'slug' => 'tuyen-dung',
                'summary' => 'Gia nhập đội ngũ FlexBiz và cùng chúng tôi kiến tạo tương lai.',
                'content' => '
                    <p>Tại FlexBiz, con người là tài sản quý giá nhất. Chúng tôi luôn chào đón những tài năng trẻ, nhiệt huyết và dám nghĩ dám làm.</p>
                    <ul>
                        <li>Môi trường làm việc mở, sáng tạo (Open Office).</li>
                        <li>Lương thưởng cạnh tranh, review tăng lương 6 tháng/lần.</li>
                        <li>Bảo hiểm sức khỏe cao cấp PVI.</li>
                    </ul>
                    <p>Vui lòng gửi CV về email: <strong>hr@flexbiz.com</strong></p>
                '
            ],
            [
                'name' => 'Liên Hệ Hợp Tác',
                'slug' => 'lien-he-hop-tac',
                'summary' => 'Cùng nhau phát triển mạng lưới kinh doanh bền vững.',
                'content' => '<p>Bạn là nhà cung cấp? Bạn muốn đưa sản phẩm lên FlexBiz? Hãy liên hệ bộ phận kinh doanh qua hotline <strong>1900 123 456</strong>.</p>'
            ],

            // --- NHÓM HỖ TRỢ KHÁCH HÀNG ---
            [
                'name' => 'Trung Tâm Trợ Giúp',
                'slug' => 'trung-tam-tro-giup',
                'summary' => 'Quy trình đặt hàng đơn giản chỉ với 3 bước.',
                'content' => '
                    <ol>
                        <li><strong>Tìm kiếm sản phẩm:</strong> Sử dụng thanh tìm kiếm hoặc duyệt qua danh mục.</li>
                        <li><strong>Thêm vào giỏ:</strong> Chọn màu sắc, kích thước và nhấn "Thêm vào giỏ".</li>
                        <li><strong>Thanh toán:</strong> Nhập địa chỉ nhận hàng và chọn phương thức thanh toán an toàn.</li>
                    </ol>
                '
            ],
            [
                'name' => 'Hướng Dẫn Mua Hàng',
                'slug' => 'huong-dan-mua-hang',
                'summary' => 'Quy trình đặt hàng đơn giản chỉ với 3 bước.',
                'content' => '
                    <ol>
                        <li><strong>Tìm kiếm sản phẩm:</strong> Sử dụng thanh tìm kiếm hoặc duyệt qua danh mục.</li>
                        <li><strong>Thêm vào giỏ:</strong> Chọn màu sắc, kích thước và nhấn "Thêm vào giỏ".</li>
                        <li><strong>Thanh toán:</strong> Nhập địa chỉ nhận hàng và chọn phương thức thanh toán an toàn.</li>
                    </ol>
                '
            ],
            [
                'name' => 'Chính Sách Vận Chuyển',
                'slug' => 'chinh-sach-van-chuyen',
                'summary' => 'Thông tin về phí ship và thời gian giao hàng.',
                'content' => '
                    <p>Chúng tôi hợp tác với các đơn vị vận chuyển hàng đầu (GHTK, GHN, Viettel Post) để đảm bảo hàng hóa đến tay bạn nhanh nhất.</p>
                    <ul>
                        <li><strong>Nội thành:</strong> 1-2 ngày (Freeship đơn > 500k).</li>
                        <li><strong>Ngoại thành:</strong> 3-5 ngày.</li>
                    </ul>
                '
            ],
            [
                'name' => 'Chính Sách Đổi Trả',
                'slug' => 'chinh-sach-doi-tra',
                'summary' => 'Cam kết hài lòng 100% hoặc hoàn tiền.',
                'content' => '
                    <p>Khách hàng được quyền đổi trả sản phẩm trong vòng <strong>30 ngày</strong> kể từ ngày nhận hàng nếu:</p>
                    <ul>
                        <li>Sản phẩm bị lỗi sản xuất.</li>
                        <li>Sản phẩm không đúng mô tả.</li>
                        <li>Kích cỡ không vừa (hỗ trợ đổi size).</li>
                    </ul>
                '
            ],
            [
                'name' => 'Chính Sách Bảo Mật',
                'slug' => 'chinh-sach-bao-mat',
                'summary' => 'Chúng tôi coi trọng và bảo vệ dữ liệu cá nhân của bạn.',
                'content' => '<p>FlexBiz cam kết không chia sẻ thông tin cá nhân của khách hàng với bất kỳ bên thứ ba nào ngoại trừ các đối tác vận chuyển để phục vụ đơn hàng.</p>'
            ],
            [
                'name' => 'Điều Khoản Dịch Vụ',
                'slug' => 'dieu-khoan-dich-vu',
                'summary' => 'Các quy định chung khi sử dụng website FlexBiz.',
                'content' => '<p>Bằng việc truy cập website, bạn đồng ý tuân thủ các quy định về bản quyền, hành vi sử dụng và quy tắc cộng đồng của chúng tôi.</p>'
            ],
        ];

        // 3. Loop và tạo Post (Dùng updateOrCreate để tránh trùng lặp khi chạy lại seeder)
        foreach ($pages as $page) {
            $post = Post::updateOrCreate(
                ['slug' => $page['slug']], // Điều kiện tìm kiếm
                [
                    'name' => $page['name'],
                    'summary' => $page['summary'],
                    'content' => $page['content'],
                    'thumbnail' => 'https://images.unsplash.com/photo-1486312338219-ce68d2c6f44d?auto=format&fit=crop&w=1200&q=80', // Ảnh chung
                    'is_featured' => false,
                    'status' => 'published',
                    'views' => 0,
                    'user_id' => 1, // Admin
                    'published_at' => Carbon::now(),
                ]
            );

            // Gắn vào danh mục "Pages"
            $post->categories()->syncWithoutDetaching([$category->id]);
        }

        $this->command->info('✅ Đã tạo các trang tĩnh cho Footer (Chính sách, Giới thiệu...).');
    }
}
