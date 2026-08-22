# Phase 11F — Structured Content Consolidation

## Mục tiêu

Đưa `WebsitePage / WebsiteSection / WebsiteSectionItem` thành source-of-truth của Homepage khi structured page đã tồn tại, đồng thời giữ `home_*` settings như compatibility mirror trong rollback window.

## Read path

Admin và frontend cùng đọc qua `HomepageContentService`:

- visibility/order/type từ `WebsiteSection`;
- Categories và Featured Products từ `WebsiteSectionItem` reference IDs;
- Trust Badges từ `WebsiteSectionItem.config`;
- New Arrivals / Best Sellers / Blog limits từ `WebsiteSection.config`;
- Promo Banner và Newsletter từ `WebsiteSection.config`.

Nếu structured Homepage chưa tồn tại thì service mới fallback về legacy `home_*` settings.

## Write path

`HomepageContentWriteService` publish theo thứ tự:

1. nếu chưa có structured Homepage thì chạy legacy backfill một lần;
2. sync Builder layout/visibility/order;
3. sync business content trực tiếp vào structured sections/items;
4. mirror cùng dữ liệu về `home_*` settings để rollback/tương thích;
5. clear Homepage composition cache.

Structured data vì vậy là canonical write target từ Phase 11F trở đi.

## Structured writer

`HomepageStructuredContentService` quản lý:

- `categories` → category reference items;
- `featured` → product reference items;
- `trust_badges` → item config;
- `new_arrivals`, `best_sellers`, `blog_highlight` → `limit` config;
- `promo_banner`, `newsletter` → section config.

Không lưu renderer path trong database.

## Compatibility contract

Phase 11F chưa xóa `home_*` vì cần rollback an toàn. Legacy settings chỉ được dùng khi structured page/section chưa tồn tại. Việc xóa hẳn compatibility mirror cần một phase migration riêng sau khi production đã ổn định đủ lâu.

## Test

```bash
php artisan test \
  tests/Feature/Website/WebsiteHomepageStructuredContentConfigurationTest.php \
  tests/Feature/Website/WebsiteHomepageLayoutThemeConfigurationTest.php \
  tests/Feature/Website/WebsiteHomepagePresentationConfigurationTest.php \
  tests/Feature/Website/WebsiteHomepageBuilderStateConfigurationTest.php
```

Sau đó có thể chạy toàn bộ scope Homepage:

```bash
php artisan test tests/Feature/Website/WebsiteHomepage*
```
