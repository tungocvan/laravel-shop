# Modules/Muasamcong

Module tích hợp dữ liệu Hệ thống mạng đấu thầu quốc gia (`muasamcong.mpi.gov.vn`) cho tra cứu thuốc/giá trúng thầu, HSMT, lịch sử nhà thầu, KQLCNT, snapshot dữ liệu, wishlist và quản lý các thuốc đã đồng bộ.

> **Tài liệu chuẩn để tiếp quản:**
>
> 1. `docs/modules/Muasamcong/README.md`
> 2. `docs/modules/Muasamcong/AI_HANDOFF.md`
> 3. `docs/modules/Muasamcong/INFORMATION.md`
> 4. code + tests hiện tại
>
> Các tài liệu lịch sử cũ có thể không phản ánh persistence/routes/features mới. Code/tests là source of truth cuối cùng.

## Kiến trúc

Repository dùng module architecture riêng qua `Modules\ModuleServiceProvider`; không giả định `nwidart/laravel-modules`.

Luồng chuẩn:

```text
Route -> Controller/Page Blade -> Livewire -> Service -> DB hoặc upstream HTTP
```

## Chức năng hiện tại

- Smart Pricing theo thuốc/hoạt chất/TBMT và winner upstream cung cấp.
- Database-first search snapshots; keyword cũ không gọi API lại.
- `Tìm kiếm mới` để force upstream refresh.
- Full-page loading cho TBMT lớn + local pagination.
- Filter theo thuốc/hoạt chất/nhóm/winner.
- Cross-page checkbox selection + selected-items review + sync.
- Persist thuốc đã chọn vào `muasamcong_pricing_results`.
- Quản lý danh sách đã đồng bộ: search, paginate, edit winner/KQLCNT metadata, bulk delete.
- Wishlist per-user.
- Contractor history.
- KQLCNT/contract/all-winner metadata.
- HSMT detail/catalogue parsing + snapshot/reuse.
- HSMT search/XLSX export.
- Secure integration config/environment doctor.

## Admin routes

```text
/admin/muasamcong
/admin/muasamcong/contractors
/admin/muasamcong/hsmt
/admin/muasamcong/synced
/admin/muasamcong/wishlist
/admin/muasamcong/config
```

API:

```text
GET  /api/muasamcong
POST /api/muasamcong/search-pricing
```

## Persistence

Module hiện CÓ database persistence. Các domain quan trọng gồm:

- `PricingResult`
- `PricingSearchSnapshot`
- `PricingWishlist`
- `ContractorBid`
- `KqlcntRecord`
- HSMT/KQLCNT server snapshots theo migrations/code hiện tại

Không dựa vào tài liệu cũ từng ghi `module không có migration/database`.

## Winner rule

- Có `winningName` upstream => giữ và hiển thị.
- Thiếu winner => `Nguồn không cung cấp`, không suy đoán.
- Chưa có mapping đáng tin cậy giữa winner và exact lot/medicine.
- Không được tự gán all-winners của TBMT cho từng thuốc.

## Security

- Không hard-code/commit/log token/cookie.
- Secret không được hydrate vào Livewire public state/HTML/JS.
- HTTPS + approved host `muasamcong.mpi.gov.vn`.
- SSL verification production phải bật.
- Endpoint/schema/token/cookie upstream có thể thay đổi.

## Pre-merge

```bash
php artisan optimize:clear
php artisan migrate:status
php artisan route:list --path=muasamcong
vendor/bin/pint --test Modules/Muasamcong tests/Feature/Muasamcong
php artisan test tests/Feature/Muasamcong
```

Sau targeted PASS, chạy full project regression trước merge main.

Đọc `docs/modules/Muasamcong/AI_HANDOFF.md` để có toàn bộ endpoint/payload đã điều tra, case thực tế, invariant UI/UX và vấn đề còn mở.
