# Phase 12 — Website Layout & Global Design System Analysis

## Mục tiêu

Phase 12 chuẩn hóa lớp layout toàn Website tại `Modules/Website/resources/views/layouts/frontend.blade.php` và khu vực quản trị `/admin/website/settings` theo cùng nguyên tắc đã áp dụng cho Header, Footer và Homepage:

- Không hardcode các tham số presentation có thể cấu hình an toàn.
- Có default tối ưu trong config và settings chỉ override các giá trị được whitelist.
- Tách rõ global design tokens, website shell layout, branding, SEO, PWA/browser appearance và privileged scripts.
- UI Admin tuân `docs/modules/Website/ADMIN_UI_INPUT_STANDARD.md`.
- Mọi cấu hình visual phải được normalize/validate qua service trước khi render CSS/HTML.
- Giữ runtime/security contract trong code, không cho Admin thay arbitrary Blade/view/script paths.
- Hỗ trợ preview-first, responsive preview và Website Design Themes với Export/Import JSON.

## Hiện trạng

### `frontend.blade.php`

Layout hiện chứa nhiều trách nhiệm cùng lúc:

- HTML document shell.
- SEO/OpenGraph metadata.
- PWA/browser metadata.
- trusted header scripts và analytics.
- realtime bootstrap.
- design tokens.
- Vite/Livewire assets.
- Header/Main/Footer orchestration.
- global toast.
- service worker registration.

Một số giá trị presentation/app identity còn hardcode, ví dụ:

- `theme-color = #0f172a`.
- `application-name = INAFO Client Portal`.
- `apple-mobile-web-app-title = INAFO`.
- `/manifest.webmanifest`.
- main content `py-8`.
- toast position/duration/styles.
- `/service-worker.js`.

### `/admin/website/settings`

Admin hiện chỉ có ba tab:

- SEO.
- Logo & giao diện.
- Nâng cao.

Nó quản lý site name, logo, favicon, SEO metadata, analytics và header scripts nhưng chưa quản lý Global Design Tokens, Website shell presentation, PWA/browser appearance, responsive preview hoặc themes.

### Global Design Tokens

`Modules/Website/Config/design.php` đã có default tốt cho:

- typography.
- font sizes.
- line heights.
- colors.
- container widths.
- radius.
- shadows.

`partials/design-tokens.blade.php` đã phát ra CSS variables nhưng runtime hiện chủ yếu nhận `config('website.design')`, vì vậy Admin chưa override được design system.

## Nguyên tắc kiến trúc

### Config là default, Settings là override

Runtime design phải resolve theo flow:

```text
Config defaults
    +
website.design settings
    ↓
WebsiteDesignService
    ↓
normalized + sanitized design tokens
    ↓
partials/design-tokens.blade.php
```

Không render raw arbitrary CSS từ DB.

### Phân tách Design và Layout

`website.design` chịu trách nhiệm token dùng xuyên suốt toàn storefront.

`website.layout` chịu trách nhiệm presentation của shell `frontend.blade.php`.

Header/Footer/Homepage tiếp tục giữ presentation riêng và có thể inherit global tokens.

### Không cho Admin cấu hình runtime internals

Các phần sau tiếp tục system-controlled:

- charset và viewport.
- Vite assets.
- Livewire styles/scripts.
- realtime bootstrap.
- accessibility IDs/skip link contract.
- arbitrary Blade includes/view paths.
- raw renderer paths.
- CSRF/security internals.
- manifest/service-worker system path.

Admin chỉ được bật/tắt behavior an toàn hoặc chỉnh presentation whitelist.

## Đề xuất cấu trúc Settings

### `website.design`

```php
[
    'typography' => [
        'font_family_body',
        'font_family_heading',
        'font_family_mono',
        'base_font_size',
        'font_size' => [
            'xs', 'sm', 'base', 'lg', 'xl', '2xl', '3xl', '4xl',
        ],
        'line_height' => [
            'tight', 'normal', 'relaxed',
        ],
        'font_weight' => [
            'normal', 'medium', 'semibold', 'bold',
        ],
    ],
    'colors' => [
        'primary', 'secondary', 'background', 'surface', 'text',
        'muted', 'border', 'success', 'warning', 'danger',
    ],
    'layout' => [
        'container_width' => [
            'compact', 'standard', 'wide', 'full',
        ],
        'default_container',
        'radius' => [
            'sm', 'md', 'lg', 'xl',
        ],
        'shadow' => [
            'none', 'soft', 'medium', 'strong',
        ],
    ],
]
```

### `website.layout`

```php
[
    'mode' => 'basic',
    'body' => [
        'background' => 'inherit',
        'text_color' => 'inherit',
    ],
    'main' => [
        'container' => 'full',
        'padding_top' => 32,
        'padding_bottom' => 32,
        'padding_x' => 0,
        'min_height' => 'screen',
    ],
    'content' => [
        'max_width' => null,
        'alignment' => 'center',
    ],
    'scroll' => [
        'smooth' => true,
    ],
    'notifications' => [
        'position' => 'bottom-left',
        'duration' => 4000,
        'max_width' => 384,
    ],
]
```

### `website.pwa`

```php
[
    'application_name' => null,
    'short_name' => null,
    'theme_color' => '#0f172a',
    'background_color' => '#ffffff',
    'apple_web_app_title' => null,
    'apple_status_bar_style' => 'default',
    'manifest_enabled' => true,
    'service_worker_enabled' => true,
]
```

Manifest/service-worker path vẫn system-controlled.

## Admin Settings Hub

`/admin/website/settings` được tổ chức lại thành:

```text
Cài đặt Website
├── Nhận diện
├── Thiết kế toàn site
├── Layout
├── PWA & trình duyệt
├── SEO
├── Themes
└── Nâng cao
```

### Nhận diện

- Site Name.
- Logo.
- Favicon.

### Thiết kế toàn site

- Typography.
- Font family.
- Font sizes.
- Line heights.
- Colors.
- Container widths.
- Radius.
- Shadow.

### Layout

- Body/background inheritance.
- Main container.
- Main padding.
- Content max width/alignment.
- Scroll behavior.
- Global notification position/duration/max-width.

### PWA & trình duyệt

- Application name.
- Short name.
- Theme/background color.
- Apple title/status bar style.
- Manifest enabled.
- Service worker enabled.

### SEO

Giữ contract hiện tại:

- SEO title.
- Description.
- Canonical.
- Robots.
- OpenGraph image.

### Themes

Website Design Themes phải hỗ trợ:

- Save.
- Apply.
- Update.
- Rename.
- Delete.
- Export JSON.
- Import JSON.

### Nâng cao

- Analytics code.
- Header scripts.

Đây là privileged configuration và không nằm trong Website Design Theme.

## Website Design Theme contract

Setting:

```text
website.design_themes
```

Theme chỉ chứa:

```php
[
    'version' => 1,
    'name' => '...',
    'design' => [...],
    'layout' => [...],
    'pwa_appearance' => [...],
    'updated_at' => '...',
]
```

Theme không được chứa:

- SEO content.
- site name.
- logo/favicon.
- analytics/header scripts.
- Header layout/theme.
- Footer layout/theme.
- Homepage layout/content/theme.
- product/category IDs.
- arbitrary HTML/CSS/JS.

Import phải validate schema/version và reject unknown unsafe keys.

## Responsive Preview

Admin cần preview ba device:

```text
Desktop | Tablet | Mobile
```

Preview tối thiểu phải phản ánh:

- font body/heading.
- colors.
- body/surface/background.
- container width.
- main padding.
- radius/shadow.
- Header/Main/Footer proportions.
- notification position.

Preview là preview-first; chỉ `Lưu thay đổi` mới publish runtime settings.

## Layout decomposition

`frontend.blade.php` nên trở thành orchestration shell và tách các partial rõ trách nhiệm:

```text
layouts/frontend.blade.php
├── partials/layout/head-meta.blade.php
├── partials/layout/runtime-head.blade.php
├── partials/design-tokens.blade.php
├── partials/header.blade.php
├── main content
├── partials/footer.blade.php
├── partials/layout/global-toast.blade.php
└── partials/layout/runtime-footer.blade.php
```

Không bắt buộc tách file nếu một partial không tạo giá trị rõ ràng; mục tiêu là giảm hardcode và làm contract dễ test, không phải chia nhỏ máy móc.

## Phase roadmap

### Phase 12A — Website Layout Decomposition

- Tách orchestration/runtime concerns khỏi `frontend.blade.php`.
- Không thay đổi UI storefront.
- Khóa security/accessibility/runtime contract bằng focused tests.

### Phase 12B — Global Design Tokens Admin

- Tạo `WebsiteDesignService`.
- Resolve config defaults + settings overrides.
- Sanitize typography/colors/sizes/radius/shadows.
- Thêm Admin workspace `Thiết kế toàn site`.
- Tuân `ADMIN_UI_INPUT_STANDARD.md`.

### Phase 12C — Website Layout Presentation

- Tạo `WebsiteLayoutPresentationService`.
- Chuyển main/body/toast presentation khỏi hardcode.
- Thêm Admin workspace `Layout`.
- Giữ Header/Footer/Homepage presentation độc lập.

### Phase 12D — PWA & Browser Appearance

- Tạo normalized `website.pwa` contract.
- Loại hardcode INAFO/theme color khỏi frontend layout.
- Admin chỉ chỉnh appearance/enable flags an toàn.
- System paths không cho tùy biến arbitrary.

### Phase 12E — Responsive Preview

- Desktop/Tablet/Mobile preview trong `/admin/website/settings`.
- Preview sử dụng Builder state chưa publish.
- Không ghi settings khi chỉ chỉnh preview.

### Phase 12F — Website Design Themes + Export/Import

- Save/Apply/Update/Rename/Delete.
- Export/Import JSON versioned.
- Theme chỉ gồm design/layout/PWA appearance.
- Apply là preview-first; Save mới publish.

### Phase 12G — Cleanup, Tests & Production Hardening

- Rà hardcode còn lại trong global layout.
- Rà CSS variable injection/sanitization.
- Rà privileged scripts permission.
- Rà cache invalidation.
- Focused Website tests, không chạy toàn bộ project.
- UI regression Desktop/Tablet/Mobile.
- Chốt docs implementation và migration/rollback contract.

## Test strategy

Chỉ test Module Website và các module trực tiếp bị ảnh hưởng.

Không dùng `php artisan test` toàn project.

Mỗi subphase phải có focused configuration/runtime tests và UI acceptance trước khi chuyển phase tiếp theo.

## Acceptance criteria cuối Phase 12

Phase 12 chỉ được chốt khi:

1. `frontend.blade.php` không còn visual hardcode đáng kể ngoài system runtime contract.
2. Global design tokens có default + safe settings override.
3. Font family/font size/colors/container/radius/shadow quản trị được.
4. Website shell spacing/layout quản trị được.
5. PWA/browser appearance không còn branding hardcode.
6. Responsive Preview Desktop/Tablet/Mobile hoạt động.
7. Website Design Themes hỗ trợ Save/Apply/Update/Rename/Delete/Export/Import.
8. Theme không chứa business content hoặc privileged scripts.
9. Header/Footer/Homepage tiếp tục hoạt động và không bị global settings ghi đè sai contract.
10. UI Admin tuân `ADMIN_UI_INPUT_STANDARD.md`.
11. Focused tests + UI regression PASS.
