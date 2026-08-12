# Website Phase 3C — Homepage Backfill

## Trạng thái

- Backfill service/command: `HOÀN TẤT`
- SQLite dry-run/apply/idempotency tests: `PASS`
- Production dry-run: `PASS`
- Production apply: `PASS`
- Quyết định: `ĐÃ ĐÓNG`

## Ánh xạ

- Tạo page `home`, template `homepage`, trạng thái published.
- Tạo 10 section theo thứ tự hiện hữu: hero, categories, flash sale, featured,
  new arrivals, best sellers, blog, promo, trust badges, newsletter.
- Visibility, count và JSON config được chuyển từ `home_*`.
- Category/Product IDs hợp lệ trở thành ordered section items.
- Missing IDs chỉ được báo cáo và không làm hỏng backfill.
- Lệnh chạy lặp không tạo page/section/item trùng.
- `wp_settings` không bị sửa hoặc xóa.

## Production dry-run

```text
sections: 10
category_items: 0
product_items: 0
trust_badge_items: 0
missing category/product IDs: 0
```

## Kiểm thử

```text
HomepageBackfillServiceTest + WebsiteContentSchemaTest: 7 PASS / 40 assertions
Full affected gate: 46 PASS / 10.681 assertions
git diff --check: PASS
```

## Bước cần phê duyệt

```bash
php8.3 artisan website:backfill-homepage --apply
```

Lệnh chỉ ghi ba bảng structured mới; không đổi read path và không sửa legacy data.

Production apply đã tạo homepage và 10 section. Dry-run sau apply không báo orphan;
toàn bộ `wp_settings` được giữ nguyên.
