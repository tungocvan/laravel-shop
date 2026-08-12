# Website Phase 3B — Core Page/Section Schema

## Trạng thái

- Triển khai code: `HOÀN TẤT`
- SQLite fresh/constraint/rollback tests: `PASS`
- MySQL migration pretend: `PASS`
- MySQL production migration: `PASS — BATCH 2`
- Quyết định: `ĐÃ ĐÓNG`

## Schema additive

- `website_pages`: slug unique, publishing/template/SEO metadata.
- `website_sections`: page FK, stable key, type, position, enabled, variant, JSON config.
- `website_section_items`: section FK, canonical reference identity, position, enabled,
  JSON config.
- Cascade chỉ áp dụng bên trong ba bảng mới.
- Không soft-delete, không sửa bảng legacy và không chuyển read/write path hiện tại.

## Model contracts

- `WebsitePage` có status constants, published scope và ordered sections.
- `WebsiteSection` có JSON/boolean/integer casts, enabled scope và ordered items.
- `WebsiteSectionItem` có reference/config casts và enabled scope.

## Bằng chứng kiểm thử

```text
WebsiteContentSchemaTest: 5 PASS / 27 assertions
Full affected System + Website + User + Order gate:
44 PASS / 10.652 assertions
MySQL migrate --pretend: PASS
MySQL migrate:status: Ran / batch 2
git diff --check: PASS
```

Test xác nhận required columns/indexes, unique slug/page-key/reference, relationship
ordering, JSON casts, published scope, cascade delete và rollback không ảnh hưởng
bảng legacy.

## Bước cần phê duyệt

```bash
php8.3 artisan migrate \
  --path=Modules/Website/database/migrations/2026_08_11_150000_create_website_content_structure.php \
  --force
```

Migration đã tạo ba bảng trống; chưa thay đổi giao diện/runtime.
