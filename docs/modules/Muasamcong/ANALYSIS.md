# Muasamcong Module Analysis

> Cập nhật 2026-08-16 sau khi module mở rộng từ integration stateless thành module có persistence, snapshots, contractor history, KQLCNT/HSMT và quản lý dữ liệu đã đồng bộ.
>
> Tài liệu bàn giao chi tiết nhất: `docs/modules/Muasamcong/AI_HANDOFF.md`.

## Executive Summary

`Modules/Muasamcong` hiện là một domain integration + persistence module cho dữ liệu đấu thầu/thuốc từ `muasamcong.mpi.gov.vn`.

Kiến trúc tổng thể vẫn phù hợp với repository và KHÔNG cần structural rebuild. Hướng đúng là tiếp tục incremental changes trên các service boundary hiện tại.

```text
Recommendation: No Structural Rebuild Required
```

## Architecture Assessment

Repository dùng `Modules\ModuleServiceProvider` làm canonical loader. Module-specific provider/config chỉ phục vụ cấu hình riêng; không chuyển sang `nwidart/laravel-modules`.

Luồng hiện tại:

```text
Admin Route
 -> thin Controller / page shell
 -> Livewire component
 -> domain/integration Service
 -> Eloquent persistence hoặc upstream HTTP
```

Điểm tốt hiện tại:

- upstream HTTP tập trung ở services;
- pagination/cache/sync/wishlist/KQLCNT/HSMT đã tách thành service riêng;
- admin route permission tách search/config;
- token/cookie không nằm trong public UI state;
- persistence có models/migrations rõ;
- kết quả lớn không render toàn bộ cùng lúc;
- selection state xuyên trang được xử lý ở Livewire.

## Current Functional Domains

### 1. Smart Pricing

`TracuuThuoctrungthau` + `MuaSamCongService`.

Có database-first snapshot, force refresh, local filters, local pagination và cross-page selection.

### 2. TBMT Full Pagination

`PricingTbmtPaginationService` giải quyết lỗi lịch sử chỉ tải 20 dòng/page đầu. Với keyword TBMT, full upstream pages được merge trước khi UI paginate local.

### 3. Search Snapshot / Cache

`PricingSearchSnapshotService` persist full result theo normalized keyword. Đây là business cache có timestamp/access audit; không phải transient framework cache.

### 4. Pricing Sync

`PricingResultSyncService` + `PricingResult` persist selected medicine rows và raw upstream payload.

### 5. Synced Management

`SyncedPricingList` cho phép search, pagination, cross-page selection, edit KQLCNT/winner metadata và bulk delete.

### 6. Wishlist

`PricingWishlistService` + `PricingWishlist`, scoped theo user.

### 7. Contractor History

`ContractorHistory` + `ContractorHistoryService` tra lịch sử TBMT nhà thầu đã tham gia và mở KQLCNT/HSMT context.

### 8. KQLCNT

`KqlcntService` normalize contracts/winners và persistence liên quan `KqlcntRecord`.

### 9. HSMT Catalogue

`HsmtDetailService` parse HSMT forms và medicine tables; `HsmtSnapshotService` reuse server snapshot để giảm upstream calls.

### 10. Integration Config

`ConfigManager` + `MuasamcongConfigService` giữ security boundary cho token/cookie/endpoint/SSL.

## Database Analysis

Module hiện có migrations/tables; nhận định lịch sử `stateless/no database` không còn đúng.

Các domain persistence chính:

- `muasamcong_pricing_results`
- `muasamcong_pricing_wishlists`
- `muasamcong_contractor_bids`
- `muasamcong_kqlcnt_records`
- `muasamcong_pricing_search_snapshots`
- các field snapshot bổ sung theo migrations hiện tại

Trước bất kỳ schema change nào phải đọc migrations + models hiện tại và giữ backward compatibility nếu không có lý do nghiệp vụ rõ.

## Performance Analysis

Cải thiện quan trọng:

- keyword cũ đọc DB snapshot, giảm upstream calls;
- TBMT fetch full pages một lần, sau đó local pagination;
- HSMT catalogue ưu tiên server snapshot;
- winner modal giới hạn render và có search/load more;
- UI tables paginate 20 rows/page.

Rủi ro còn lại:

- TBMT rất lớn vẫn fetch upstream synchronously;
- search snapshot payload có thể lớn và tăng storage;
- HSMT raw response/catalogue có thể rất lớn;
- cần cân nhắc retention/queue nếu scale tăng.

## Data Integrity Analysis

Nguyên tắc quan trọng nhất là tách `source data` khỏi `manual administrative enrichment`.

- `raw_payload` giữ source snapshot.
- manual edit winner trên synced list không được giả vờ là dữ liệu upstream.
- không tự join contractor và medicine khi thiếu join key.
- `HTTP 200` không được xem là có dữ liệu nếu body rỗng.

## Winner / Lot Analysis

Đây là open problem chính.

Đã xác minh:

```text
list-contract-for-po
 -> có thể trả danh sách contract/winner của TBMT

lcnt_tbmt_hsmt
 -> trả danh mục HSMT/medicine rows
```

Nhưng chưa có mapping tin cậy:

```text
contractor -> exact PP lotNo / medicine row
```

Case HSMT thực tế có `lotNo`, `medicineCode` nhưng không có contractor fields trên medicine rows. Một số contract có `lotResultDTO` nhưng các table lists đã quan sát rỗng. `get-result-replace` trong case test không cung cấp mapping.

Không được implement heuristic mapping.

## Security Analysis

Các invariant phải giữ:

- HTTPS only;
- exact approved host `muasamcong.mpi.gov.vn`;
- redirects/security policy theo service hiện tại;
- production SSL verify on;
- secret không commit/log/hydrate public;
- config mutation capability-protected;
- upstream payload/response errors được normalize, không leak secret.

## UI/UX Analysis

Các quyết định UI đã chốt:

- main search page nhẹ, các domain lớn tách route riêng;
- bordered search inputs;
- result tables horizontal-scroll khi wide;
- modal KQLCNT/HSMT không tràn header/footer và có internal scroll;
- winner list search + bounded render;
- local pagination 20 rows/page;
- checkbox selection xuyên trang và có selected-state review.

## Test Coverage

Feature suite hiện mở rộng tại:

```text
tests/Feature/Muasamcong/
tests/Feature/MuasamcongModuleTest.php
```

Các nhóm coverage gồm route authorization, config/security, contractor history, KQLCNT, HSMT detail, pricing sync, wishlist, search snapshots và module contracts.

Trước merge cần chạy local Pint + targeted tests + full project regression.

## Technical Debt / Follow-up

P1/P2 tiếp theo:

1. Tìm join key winner ↔ lot/medicine từ Network/API chính thức.
2. Tăng automated coverage cho cross-page selected state và synced bulk management nếu chưa đủ.
3. Xem xét audit fields rõ hơn cho manual winner edits.
4. Xem xét retention strategy cho search snapshots.
5. Xem xét queue/background refresh cho TBMT/HSMT rất lớn.
6. Reconcile/remove unused scaffolds chỉ trong task cleanup riêng, không trộn vào feature work.

## Final Recommendation

Module hiện có kiến trúc hợp lý cho tiếp tục phát triển. Trước merge `main`, ưu tiên quality gate thay vì refactor thêm:

```text
1. docs sync
2. migrations/routes review
3. Pint
4. targeted Muasamcong tests
5. manual smoke critical flows
6. full regression
7. merge main
```

Đọc `AI_HANDOFF.md` trước mọi task mới để không lặp lại các điều tra endpoint và không phá invariants đã chốt.
