# Muasamcong Module Information

> Cập nhật 2026-08-16. Đây là inventory kỹ thuật hiện tại. Xem `AI_HANDOFF.md` để có bối cảnh nghiệp vụ, endpoint đã điều tra và các quyết định quan trọng.

## Purpose

`Modules/Muasamcong` tích hợp ứng dụng với dữ liệu đấu thầu tại `muasamcong.mpi.gov.vn`, đồng thời có persistence cục bộ để lưu snapshot tra cứu, wishlist, lịch sử nhà thầu/KQLCNT/HSMT snapshot và các thuốc đã chọn đồng bộ.

## Active Features

- Smart Pricing search theo thuốc/hoạt chất/TBMT/winner upstream hỗ trợ.
- Database-first search snapshots + `Tìm kiếm mới` để force refresh upstream.
- Full-page loading cho TBMT lớn, sau đó local pagination 20 rows/page.
- Local filters: tên thuốc, hoạt chất, nhóm thuốc, đơn vị trúng thầu.
- Cross-page checkbox selection và selected-items review trước sync.
- Persist selected pricing results.
- Manage synced pricing results: search, paginate, edit winner/KQLCNT metadata, bulk delete.
- Wishlist persistence theo user.
- Contractor participation history.
- KQLCNT/contract/winner metadata.
- HSMT detail + medicine catalogue parsing + snapshot.
- HSMT search/XLSX export.
- Privileged upstream integration configuration and environment doctor.
- Authenticated internal pricing API.

## Architecture

Canonical loader:

```text
Modules\ModuleServiceProvider
```

Không dùng giả định `nwidart/laravel-modules`.

Preferred flow:

```text
Route -> Controller/Page Blade -> Livewire -> Service -> DB hoặc upstream HTTP
```

## Admin Routes

| Method | URI | Name | Purpose |
|---|---|---|---|
| GET | `/admin/muasamcong` | `muasamcong.index` | Smart Pricing |
| GET | `/admin/muasamcong/contractors` | `muasamcong.contractors` | Lịch sử nhà thầu |
| GET | `/admin/muasamcong/hsmt` | `muasamcong.hsmt` | HSMT search/export |
| GET | `/admin/muasamcong/synced` | `muasamcong.synced` | Thuốc đã đồng bộ |
| GET | `/admin/muasamcong/wishlist` | `muasamcong.wishlist` | Wishlist |
| GET | `/admin/muasamcong/config` | `muasamcong.config` | Integration config |

API:

```text
GET  /api/muasamcong
POST /api/muasamcong/search-pricing
```

Web search pages dùng `auth:admin` + `view_muasamcong`; config dùng `muasamcong.config.manage`. Sync mutations dùng permission chuyên biệt theo code hiện tại.

## Main Livewire Components

- `TracuuThuoctrungthau` — pricing search, snapshot reuse, filter, pagination, cross-page selection, sync, wishlist interaction.
- `ContractorHistory` — contractor lookup/history + KQLCNT/HSMT interactions.
- `SyncedPricingList` — quản lý dữ liệu đã sync.
- `SearchHsmt` — HSMT search/export.
- `ConfigManager` — cấu hình integration + doctor.

## Services

### `MuaSamCongService`

HTTP integration nền tảng: tạo payload, validate destination, attach token/cookie sau host validation, gọi Smart Pricing/HSMT endpoints, normalize response/errors.

### `PricingTbmtPaginationService`

Nhận page đầu Smart Pricing cho TBMT, xác định total, tải các page tiếp theo, merge/chống duplicate. Không được regression về page 0-only.

### `PricingSearchSnapshotService`

Normalize keyword, persist full search response, `searched_at`, `last_accessed_at`, `access_count`; keyword cũ ưu tiên database.

### `PricingResultSyncService`

Map các source rows được checkbox vào `PricingResult`, giữ `raw_payload`, `synced_by`, `synced_at`.

### `PricingWishlistService`

Wishlist per-user với snapshot thuốc; unique theo user + source row.

### `ContractorHistoryService`

Lấy lịch sử gói thầu nhà thầu đã tham dự; hỗ trợ date range và pagination upstream theo code hiện tại.

### `KqlcntService`

Lấy/normalize KQLCNT, contract, all winners; hỗ trợ dữ liệu `contractorCode/contractorName` trực tiếp hoặc `contractorPassList` JSON string tùy response.

### `HsmtDetailService`

Parse `lcnt_tbmt_hsmt`, đọc các form như `BD.DT.02.1854`, `BD.MT.02.1220`, `BD_DATA_TABLE`, extract catalogue/medicine rows.

### `HsmtSnapshotService`

Persist/reuse HSMT catalogue snapshot để không gọi upstream mỗi lần modal mở; refresh có chủ đích.

### `MuasamcongConfigService`

Allowlisted env mutation, URL validation, SSL/security policy và upstream config management.

## Models / Persistence

### `PricingResult`

Bảng: `muasamcong_pricing_results`.

Lưu dữ liệu thuốc đã chọn đồng bộ, gồm drug metadata, pricing, TBMT, buyer/investor, winner code/name, decision metadata, manufacturer/country, `raw_payload`, sync audit fields.

### `PricingWishlist`

Bảng: `muasamcong_pricing_wishlists`.

Wishlist per user, snapshot row và search keyword.

### `PricingSearchSnapshot`

Bảng: `muasamcong_pricing_search_snapshots`.

Cache/lịch sử search theo normalized keyword + hash; lưu full result payload và source/loaded totals.

### `ContractorBid`

Persistence lịch sử tham dự thầu được đồng bộ/chuẩn hóa bởi contractor history workflow.

### `KqlcntRecord`

Persistence KQLCNT và server-side snapshots liên quan contract/winners/HSMT catalogue theo fields/migrations hiện tại.

Trước khi thay schema phải đọc toàn bộ migrations `Modules/Muasamcong/database/migrations` và Models hiện hành.

## Important Data Rules

- `winningName` upstream có => giữ và hiển thị (đã thấy dữ liệu 2026).
- `winningName` thiếu => không suy đoán; UI dùng `Nguồn không cung cấp`.
- Danh sách winner của TBMT KHÔNG đồng nghĩa winner của từng medicine.
- Hiện chưa có mapping đáng tin cậy contractor ↔ lot/medicine.
- HSMT medicine catalogue và contract winner data là hai nguồn độc lập cho tới khi tìm được join key xác thực.

## Upstream Findings to Preserve

### `list-contract-for-po`

Payload `{"notifyNo":"IB..."}`. Có case trả contract/winners (ví dụ `IB2500539527`), nhưng `IB2600099293` đã test HTTP 200 + empty array.

### `lcnt_tbmt_hsmt`

Payload dạng `{"id":"<notifyId>","processApply":"LDT"}`. Case `IB2600008930` trả 285 medicine rows có `lotNo`/`medicineCode` nhưng không có contractor/winner fields trên từng row.

### `get-result-replace`

Đã test một `resultId`; chỉ trả `replaceResultsList` rỗng/không có mapping winner-lot cần thiết trong case đó.

Chi tiết xem `AI_HANDOFF.md`.

## UI Contracts

- Input search có border rõ theo Admin UI standard.
- Pricing result local pagination = 20 rows/page.
- Selection tồn tại xuyên trang.
- Header checkbox = current page only.
- Winner modal phải search + giới hạn render ban đầu.
- KQLCNT/HSMT modal phải scroll trong viewport, footer truy cập được, bảng wide horizontal-scroll.
- Các danh sách lớn tách page riêng: contractor history, synced, wishlist.

## Security

- HTTPS only, approved host `muasamcong.mpi.gov.vn`.
- Không hard-code/commit/log token, cookie, Authorization.
- Không hydrate secret vào Livewire public state.
- Production SSL verification luôn bật.
- HTTP 200 vẫn phải inspect body/count/schema.
- Upstream endpoints/schema/token/cookie không có stability guarantee.

## Tests

Feature suite nằm trong:

```text
tests/Feature/Muasamcong/
tests/Feature/MuasamcongModuleTest.php
```

Coverage hiện có cho route authorization, contractor history, HSMT detail, KQLCNT, environment doctor, pricing sync, wishlist, search snapshots và module contracts.

## Pre-Merge Commands

```bash
php artisan optimize:clear
php artisan migrate:status
php artisan route:list --path=muasamcong
vendor/bin/pint --test Modules/Muasamcong tests/Feature/Muasamcong
php artisan test tests/Feature/Muasamcong
```

Sau targeted PASS, chạy full regression repository trước merge main.

## Known Limitation / Primary Open Problem

Chưa xác định được nguồn/khóa mapping chính xác:

```text
winning contractor -> exact PP/lotNo/medicine
```

Không implement heuristic mapping. Mọi AI tiếp quản phải đọc `AI_HANDOFF.md` trước khi tiếp tục phần này.
