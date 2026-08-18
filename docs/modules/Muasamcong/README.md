# Muasamcong Module — Documentation Index

> Cập nhật: 2026-08-18. Tài liệu này là entry point cho AI/lập trình viên tiếp quản module.

## 1. Module làm gì?

`Modules/Muasamcong` là module tích hợp dữ liệu Hệ thống mạng đấu thầu quốc gia (`muasamcong.mpi.gov.vn`) phục vụ tra cứu và lưu trữ nghiệp vụ thuốc/đấu thầu.

Các chức năng hiện tại:

- Tra cứu Smart Pricing theo thuốc, hoạt chất, mã TBMT và dữ liệu winner upstream hỗ trợ.
- Tải đủ các page khi tra cứu TBMT lớn, sau đó phân trang local.
- Lưu snapshot lịch sử tra cứu: từ khóa cũ ưu tiên database; `Tìm kiếm mới` mới force API.
- Filter local theo tên thuốc, hoạt chất, nhóm thuốc, đơn vị trúng thầu.
- Checkbox lựa chọn thuốc xuyên nhiều trang và đồng bộ các thuốc đã chọn vào database.
- Quản lý danh sách thuốc đã đồng bộ: search, pagination, cross-page selection, edit winner/KQLCNT metadata, bulk delete.
- Export Profile cho `/admin/muasamcong/synced`: nhiều cấu hình/user, kéo thứ tự cột, rename header, type/decimal/alignment/width, Header/Footer, logo và chữ ký.
- Wishlist thuốc cần theo dõi theo user.
- Tra cứu lịch sử nhà thầu/doanh nghiệp.
- Xem KQLCNT và danh sách các đơn vị trúng thầu của TBMT.
- Lấy và snapshot HSMT/danh mục hàng hóa-thuốc.
- Tra cứu HSMT và export XLSX.
- Quản trị cấu hình upstream với security boundary riêng.

**Quan trọng:** README cũ từng ghi module không persistence/database và chỉ tải page đầu. Điều đó KHÔNG còn đúng. Module hiện có migrations/models/snapshots/wishlist/synced results và pagination đầy đủ cho luồng TBMT.

## 2. Tài liệu phải đọc

AI/lập trình viên mở chat mới đọc theo thứ tự:

1. `docs/modules/Muasamcong/README.md` — entry point và invariants.
2. `docs/modules/Muasamcong/AI_HANDOFF.md` — tài liệu bàn giao chi tiết nhất, bao gồm endpoint đã điều tra, case thực tế, các kết luận và việc chưa giải quyết.
3. `docs/modules/Muasamcong/SYNCED.md` — mô tả chi tiết `/admin/muasamcong/synced`, Export Profile và Excel/BBG.
4. `docs/modules/Muasamcong/ROUTES.md` — route reference web/API, middleware, handler và domain map.
5. `docs/modules/Muasamcong/INFORMATION.md` — inventory/lịch sử module.
6. `docs/modules/Muasamcong/ANALYSIS.md` — phân tích kiến trúc trước đó; có thể chứa thông tin lịch sử đã cũ.
7. `docs/modules/Muasamcong/ENV_DOCTOR.md` — chẩn đoán cấu hình môi trường.
8. `.codex/skills/configurable-excel-export/SKILL.md` — skill tái sử dụng Export Profile cho module khác.
9. Code + tests hiện tại — source of truth cuối cùng.

Nếu tài liệu lịch sử mâu thuẫn code hiện tại, phải báo rõ và ưu tiên code/tests hiện tại.

## 3. Kiến trúc repository

Dự án dùng module architecture riêng qua:

```text
Modules\ModuleServiceProvider
```

Không giả định `nwidart/laravel-modules`.

Luồng chuẩn:

```text
Route -> Controller/Page Blade -> Livewire -> Service -> Model/DB hoặc upstream HTTP
```

Integration parsing, persistence và UI state phải tiếp tục tách thành services; không gom lại vào Livewire.

## 4. Trang admin

```text
/admin/muasamcong              Tra cứu thuốc / Smart Pricing
/admin/muasamcong/contractors  Lịch sử nhà thầu
/admin/muasamcong/hsmt         Tra cứu HSMT
/admin/muasamcong/synced       Danh sách thuốc đã đồng bộ + Export Profile
/admin/muasamcong/wishlist     Wishlist thuốc cần theo dõi
/admin/muasamcong/config       Cấu hình tích hợp
```

API:

```text
GET  /api/muasamcong
POST /api/muasamcong/search-pricing
POST /api/muasamcong/update-cookie
```

Các route search/admin dùng `auth:admin` + permission `view_muasamcong`; config có permission riêng `muasamcong.config.manage`. Mutation sync sử dụng permission chuyên biệt theo code hiện tại.

Xem chi tiết toàn bộ route tại `docs/modules/Muasamcong/ROUTES.md`.

## 5. Chức năng và service chịu trách nhiệm

| Chức năng | Thành phần chính | Trách nhiệm |
|---|---|---|
| Smart Pricing | `TracuuThuoctrungthau`, `MuaSamCongService` | Tra cứu thuốc/TBMT và normalize kết quả |
| Full TBMT pagination | `PricingTbmtPaginationService` | Tải đủ page upstream, merge/chống trùng |
| Search snapshot | `PricingSearchSnapshotService` | Database-first cho từ khóa cũ, timestamp/access count, force refresh |
| Đồng bộ thuốc | `PricingResultSyncService` | Map selected source rows vào `PricingResult` |
| Synced list | `SyncedPricingList` | Search, pagination, checkbox xuyên trang, edit/bulk delete |
| Configurable Excel | `SyncedPricingExportPreferenceService`, `SyncedExportProfile`, `SyncedPricingExportController` | Multi-profile export, typed Excel, Header/Footer, logo/chữ ký |
| Wishlist | `PricingWishlistService`, `PricingWishlist` | Danh sách thuốc theo dõi theo user |
| Lịch sử nhà thầu | `ContractorHistory`, `ContractorHistoryService` | Tìm doanh nghiệp và các TBMT đã tham gia |
| KQLCNT | `KqlcntService` | Contract/winner metadata của TBMT |
| HSMT detail | `HsmtDetailService` | Parse form HSMT/danh mục thuốc |
| HSMT snapshot | `HsmtSnapshotService` | Lưu và tái sử dụng dữ liệu HSMT |
| Config | `MuasamcongConfigService` | Quản lý integration config an toàn |

## 6. Invariants nghiệp vụ không được phá

### Search/cache

- Từ khóa đã tra cứu: ưu tiên database snapshot, không gọi API lại.
- UI phải hiển thị thời gian dữ liệu được tra cứu/lấy nguồn gần nhất.
- `Tìm kiếm mới` là hành động rõ ràng để gọi upstream lại và refresh snapshot.

### Pagination

- TBMT có >20 rows phải tải đủ upstream pages.
- Sau khi tải full set, UI phân trang local 20 rows/page.
- Không regression về chỉ page 0.

### Checkbox

- Selection phải giữ xuyên trang.
- Header checkbox thao tác trang hiện tại.
- Có thao tác chọn tất cả kết quả/bỏ tất cả/xem danh sách đã chọn.
- Sync phải nhận toàn bộ selected IDs ở nhiều trang.

### Winner

- Upstream có `winningName` (đã thấy ở dữ liệu 2026) => hiển thị bình thường.
- Upstream không có `winningName` (thường gặp 2025) => hiển thị `Nguồn không cung cấp`.
- Không suy đoán/gán winner toàn TBMT vào từng medicine.
- Chưa có mapping đáng tin cậy winner ↔ lot/medicine.

### HSMT

- HSMT catalogue có thể rất lớn; modal phải scroll đúng cả ngang/dọc.
- Dữ liệu đã snapshot ưu tiên local; refresh chỉ khi người dùng chủ động đồng bộ lại.

### Synced export

- Save cấu hình không tự export.
- Export chỉ nhận identifier từ browser và query DB lại.
- Export profile luôn scope theo user.
- Cột export luôn đi qua server-side whitelist.
- `GĐKLH/GPNK` phải giữ dạng text khi xuất.
- Number phải là numeric cell, decimal/format do profile quyết định.
- Header/Footer chỉ thay presentation, không mutate domain data.

## 7. Kết luận điều tra winner ↔ thuốc

Đã xác minh hai nguồn độc lập:

```text
list-contract-for-po
 -> danh sách contract / các công ty trúng thầu của TBMT

lcnt_tbmt_hsmt
 -> toàn bộ danh mục lô/thuốc HSMT của TBMT
```

Chưa tìm được khóa chính xác nối contractor với từng `lotNo`/medicine.

Case `IB2600008930`, notifyId `894fb581-2622-421e-aada-320c53332745`: HSMT trả 285 rows và có `lotNo`, `medicineCode`, nhưng không có `contractorCode`, `contractorName`, `winningCode`, `resultId` trên medicine rows.

Case `IB2600099293`: `list-contract-for-po` đã test HTTP 200 nhưng response `[]`; bấm đồng bộ lại không thể tạo winner từ một source rỗng.

Chi tiết endpoint, payload và các thử nghiệm thất bại/thành công nằm trong `AI_HANDOFF.md`.

## 8. Security

- Không hard-code token/cookie.
- Không commit secret.
- Không đưa secret vào Livewire public state/HTML/JS/log.
- Chỉ HTTPS và host allowlist `muasamcong.mpi.gov.vn`.
- SSL verification production phải bật.
- Endpoint/schema/token/cookie upstream có thể thay đổi.
- HTTP 200 phải kiểm tra body/count/schema, không mặc định là success có dữ liệu.
- Export/download phải validate selected IDs và query DB server-side.
- Export profile không được cross-user.

## 9. Pre-merge verification

```bash
php artisan optimize:clear
php artisan migrate:status
php artisan route:list --path=muasamcong
vendor/bin/pint --test Modules/Muasamcong tests/Feature/Muasamcong
php artisan test tests/Feature/Muasamcong
```

Sau module regression nên chạy full project regression trước khi merge `main`.

Manual smoke tối thiểu:

1. Keyword mới -> API.
2. Keyword cũ -> DB snapshot.
3. `Tìm kiếm mới` -> API refresh.
4. TBMT >20 rows -> đủ dữ liệu + local pagination.
5. Filter thuốc/hoạt chất/nhóm/winner.
6. Chọn checkbox ở nhiều trang -> selection giữ nguyên -> sync đủ.
7. Winner 2026 vẫn hiện khi source có `winningName`.
8. Source thiếu winner không bị suy đoán.
9. Synced list edit/bulk delete/cross-page checkbox.
10. Synced Export Profile: save, reload, duplicate, chọn profile, export đúng format.
11. Wishlist persistence.
12. Contractor history + KQLCNT modal.
13. HSMT snapshot/catalogue scroll.

## 10. Khi mở chat mới

Dùng prompt:

```text
Hãy tiếp tục Modules/Muasamcong. Trước tiên đọc toàn bộ:
- docs/modules/Muasamcong/README.md
- docs/modules/Muasamcong/AI_HANDOFF.md
- docs/modules/Muasamcong/SYNCED.md
- docs/modules/Muasamcong/ROUTES.md
- docs/modules/Muasamcong/INFORMATION.md
- docs/modules/Muasamcong/ANALYSIS.md
- code/tests hiện tại của Modules/Muasamcong

Không implement ngay. Hãy tóm tắt kiến trúc hiện tại, chức năng đã hoàn thành, invariants, các endpoint/source dữ liệu, vấn đề winner ↔ lot/medicine chưa giải quyết và đề xuất kế hoạch thay đổi. Nếu docs lịch sử mâu thuẫn code/tests, dùng code/tests làm source of truth và nêu rõ mâu thuẫn.
```

Để áp dụng Export Profile cho module khác, dùng:

```text
Hãy sử dụng `.codex/skills/configurable-excel-export/SKILL.md` để phân tích và triển khai configurable Excel export cho Modules/<Module>.
```

Xem `AI_HANDOFF.md` để có tài liệu bàn giao đầy đủ nhất.
