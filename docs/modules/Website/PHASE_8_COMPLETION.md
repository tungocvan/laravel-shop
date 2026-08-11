# Website Phase 8 — Release Gate Completion

## Trạng thái

- Implementation: `COMPLETE`
- Website focused gate: `PASS`
- Fresh migration gate: `PASS`
- Existing MySQL upgrade gate: `PASS`
- Production asset build: `PASS`
- HTTP smoke gate: `PASS`
- Full repository suite: `KNOWN UNRELATED FAILURES`
- UI release smoke gate: `PENDING USER VERIFICATION`
- Final status: `AWAITING UI APPROVAL`

## Cleanup

- Xóa bốn Website compatibility services có zero caller.
- Xóa duplicate AffiliateLevel model đặt sai module/namespace.
- Xác nhận duplicate Website models, nested services và dead compatibility files
  từ các phase trước không còn runtime caller.
- Chuẩn hóa namespace toàn bộ Website seeders theo Linux PSR-4; optimized
  autoload và gọi seeder theo class đều PASS.
- Không xóa `wp_settings`/homepage keys vì vẫn là active compatibility contract.

## Release gates

- `migrate:fresh` dùng SQLite `:memory:`: PASS.
- MySQL migration status/upgrade: PASS.
- Website/Product/Post/Order focused regression: PASS.
- Checkout stock, coupon, bank transfer và MoMo configuration tests: PASS.
- Authorization/security configuration tests: PASS.
- Composer optimized autoload cho Website: PASS.
- Vite production build: PASS.
- Homepage, catalog, detail, sitemap, 404 HTTP smoke: PASS.

## Repository-wide baseline

Full suite: `116 passed / 38 failed / 11.311 assertions`. Lỗi còn lại nằm ngoài
phạm vi Website ở PromptEngine, Admin/Admission route permissions, Invoices,
Pharma fixtures và Example test. Website release-focused suite vẫn PASS;
không sửa chéo domain trong Phase 8.

## Remaining production prerequisite

- Nâng Node.js từ `18.19.1` lên `20.19+` hoặc `22.12+`.
- Giữ rollback/database backup trước deploy.
