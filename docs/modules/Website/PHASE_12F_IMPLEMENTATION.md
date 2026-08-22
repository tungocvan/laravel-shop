# Phase 12F — Website Settings Responsive Preview & Final Consolidation

## Mục tiêu

Phase 12F chốt Website Layout Settings bằng một Responsive Preview an toàn và audit lại các contract Phase 12A–12E.

## Responsive Preview

Vị trí: `/admin/website/settings` → `Bố cục Website`.

Preview có hai device mode:

- Desktop
- Mobile

Preview lấy state hiện tại của Livewire form thông qua Alpine entangle cho:

- `shell`
- `layoutPresentation`
- `design`
- `appearance`
- `features`

Preview mô phỏng:

- Header master visibility
- Homepage master visibility
- Footer master visibility
- Maintenance state + title/message
- Global colors
- Body/Main background
- Main container width/alignment
- Desktop/Mobile padding
- PWA/browser theme color + application name
- Chat Widget visibility/position
- Back to Top visibility/position

Preview là schematic preview, không phải storefront iframe. Không mount Livewire component con, không đăng ký Service Worker và không chạy PWA runtime trong Admin.

## PWA final audit

Admin copy đã được đồng bộ với runtime Phase 12D–12E:

- Website manifest: `/website-manifest.webmanifest`
- PWA version sync: `/website-pwa-version.json`
- Service Worker: `/service-worker.js`

Static `/manifest.webmanifest` của Client Portal không phải Website storefront manifest và không được Website Settings ghi đè.

## Website Theme v2

Website Design Theme v2 bao phủ nhóm visual an toàn:

- `design`
- `layout`
- `appearance`
- `features.chat_position`
- `features.back_to_top_position`

Theme không chứa identity/operational/security settings như Logo, Favicon, SEO, maintenance enable state, scripts hay Analytics.

Theme v1 vẫn được hỗ trợ. Apply v1 chỉ nạp `design`; không thay đổi Layout/Appearance/Features hiện tại.

## Runtime boundaries

Phase 12 giữ các boundary sau:

- `website.shell`: master visibility + storefront maintenance.
- `website.layout`: Website main-shell presentation.
- `website.design`: global design tokens.
- `website.appearance`: PWA/browser visual metadata.
- `website.features`: floating widget visibility/position.
- Header/Footer/Homepage tiếp tục có registry/layout/theme riêng.

## Validation và UX

Website Settings tuân theo:

- `ADMIN_UI_INPUT_STANDARD.md`
- `ADMIN_OPERATION_VALIDATION_STANDARD.md`

Mutation cần confirm modal; success/failure cần feedback modal; validation lỗi hiển thị inline tại field phù hợp.

## Regression tests

Targeted Phase 12F:

```bash
php artisan test \
  tests/Feature/Website/WebsiteSettingsResponsivePreviewConfigurationTest.php \
  tests/Feature/Website/WebsiteDesignThemeSchemaV2ConfigurationTest.php \
  tests/Feature/Website/WebsitePwaRuntimeSyncConfigurationTest.php \
  tests/Feature/Website/WebsiteDynamicManifestConfigurationTest.php \
  tests/Feature/Website/WebsiteAppearanceConfigurationTest.php \
  tests/Feature/Website/WebsiteLayoutPresentationConfigurationTest.php \
  tests/Feature/Website/WebsiteShellControlsConfigurationTest.php \
  tests/Feature/Website/WebsiteGlobalDesignTokensConfigurationTest.php \
  tests/Feature/Website/WebsiteFrontendLayoutDecompositionConfigurationTest.php
```

## UI acceptance

1. Mở `Bố cục Website` và đổi Desktop/Mobile preview.
2. Thử bật/tắt Header/Homepage/Footer và maintenance.
3. Thử đổi layout container/padding/background.
4. Thử đổi design colors.
5. Thử đổi PWA theme color/application name.
6. Thử đổi vị trí Chat/Back to Top.
7. Xác nhận preview không tạo request Service Worker/Livewire component con.
8. Save và kiểm tra storefront thật.
9. Export/import Website Theme v2 rồi Apply + Save lại.

Phase 12F hoàn tất khi targeted tests và UI acceptance đều PASS.
