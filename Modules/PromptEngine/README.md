# PromptEngine

## Giới thiệu

PromptEngine là module tạo bản phân tích chữ Hán, thuyết minh tiếng Việt, kế hoạch thiết kế và prompt ảnh tiếng Anh cho Gemini, ChatGPT Image, Midjourney, Flux, Stable Diffusion hoặc chế độ generic. Module không tự suy đoán dữ liệu ngữ nguyên còn thiếu.

## Kiến trúc

Luồng xử lý: kiểm tra đầu vào → phân tích → lập kế hoạch hình ảnh → adapter nền tảng → kiểm soát chất lượng → lưu tùy chọn. Interface tách analyzer, planner, prompt provider, repository và image provider để có thể thay thế độc lập.

## Yêu cầu và cài đặt

Dự án hiện dùng Laravel 12/PHP 8.3+, module tuân theo custom `Modules\ModuleServiceProvider`. Autoload `Modules\\` đã có sẵn. Chạy:

```bash
composer dump-autoload
php artisan migrate
php artisan storage:link
```

Publish cấu hình hoặc template:

```bash
php artisan vendor:publish --tag=prompt-engine-config
php artisan vendor:publish --tag=prompt-engine-templates
```

Template tại `resources/prompt-engine` được ưu tiên hơn template mặc định. Không đặt PHP trong template; loader chỉ đọc Markdown, chặn traversal và thay biến dạng `{{NAME}}`.

## Sử dụng

PHP:

```php
$result = app(\Modules\PromptEngine\Services\PromptEngineService::class)->generate([
    'character' => '福', 'platform' => 'gemini', 'aspect_ratio' => '2:3',
]);
```

API: `POST /api/prompt-engine/generate` với JSON như trên. Để yêu cầu ảnh, gọi `POST /api/prompt-engine/generate-image`. Khi image generation tắt, response vẫn trả prompt và trạng thái `skipped`. Lỗi validation trả 422 theo Laravel.

Artisan:

```bash
php artisan prompt-engine:generate 福 --platform=gemini --json
php artisan prompt-engine:generate-image 醫 --provider=gemini --save
```

## Gemini và ChatGPT Image

Sao chép các biến `PROMPT_ENGINE_*`, `GEMINI_API_KEY` hoặc `OPENAI_API_KEY` từ `.env.example` sang `.env`; không commit khóa. Bật `PROMPT_ENGINE_IMAGE_GENERATION=true`. Provider có timeout/retry, kiểm tra MIME/kích thước và lưu file tên UUID trên disk đã chọn. Test phải dùng `Http::fake()`, không gọi API thật.

## Mở rộng

Thêm style: tạo Markdown trong `Templates/styles`, thêm tên vào `styles` config, rồi mở rộng planner nếu cần mapping riêng. Thêm platform: implement `PlatformPromptProviderInterface`, đăng ký instance trong `PromptEngineServiceProvider`, và thêm whitelist config. Thêm dữ liệu chữ đáng tin cậy trong `character_data`; mọi trường chưa kiểm chứng phải để `null`/rỗng.

Lưu database được điều khiển bởi `promptengine.config.storage.enabled` và request `save=true`. Chạy test bằng `php artisan test`. Khi lỗi, kiểm tra `storage/logs/laravel.log`, config cache, khóa/mô hình provider và quyền ghi disk; API không trả stack trace khi production.

Ví dụ output gồm `input`, `analysis`, `design_plan`, chín khóa trong `explanation_vi`, `image_prompt`, và tùy chọn `image`/`quality_report`.
