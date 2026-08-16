# Muasamcong — AI Handoff / Continuation Guide

> Mục đích: đây là tài liệu bàn giao kỹ thuật để một AI/lập trình viên mở chat mới có thể tiếp tục Module `Muasamcong` mà không phải suy đoán lại lịch sử nghiệp vụ.
>
> Trước khi sửa module, đọc theo thứ tự: `README.md` này trong `docs/modules/Muasamcong`, `AI_HANDOFF.md`, `INFORMATION.md`, sau đó đọc code hiện tại. Code là source of truth nếu tài liệu cũ mâu thuẫn.

## 1. Bối cảnh và mục tiêu nghiệp vụ

`Modules/Muasamcong` tích hợp dữ liệu từ Hệ thống mạng đấu thầu quốc gia (`muasamcong.mpi.gov.vn`) phục vụ các nghiệp vụ chính:

1. Tra cứu thuốc/hoạt chất/mã TBMT và dữ liệu giá trúng thầu (Smart Pricing).
2. Tra cứu HSMT/TBMT và xuất dữ liệu.
3. Tra cứu lịch sử tham dự thầu của một nhà thầu/doanh nghiệp.
4. Xem KQLCNT của TBMT, danh sách đơn vị trúng thầu và danh mục hàng hóa/thuốc HSMT.
5. Lưu snapshot tra cứu để từ khóa cũ đọc từ database thay vì gọi upstream lại.
6. Cho phép người dùng chủ động `Tìm kiếm mới` để bỏ qua snapshot và gọi upstream lại.
7. Chọn nhiều thuốc xuyên nhiều trang và đồng bộ vào database.
8. Quản lý danh sách thuốc đã đồng bộ.
9. Wishlist thuốc cần theo dõi.
10. Quản trị cấu hình kết nối upstream an toàn.

Module đã vượt xa README lịch sử ban đầu: hiện CÓ persistence/database, cache/snapshot, wishlist, KQLCNT/HSMT snapshot và danh sách đồng bộ.

## 2. Kiến trúc dự án bắt buộc phải tôn trọng

Repository dùng module architecture riêng của dự án qua `Modules\ModuleServiceProvider`; KHÔNG giả định `nwidart/laravel-modules`.

Luồng ưu tiên:

```text
Route
 -> Controller/Page Blade
 -> Livewire
 -> Service
 -> Model/database hoặc upstream HTTP
```

Không đưa token/cookie vào public Livewire state, HTML, JS hoặc log. Không hard-code secret. Các endpoint upstream là endpoint nội bộ mà frontend Mua sắm công đang sử dụng, không phải public API contract ổn định.

## 3. Các trang admin hiện tại

Prefix: `/admin/muasamcong`.

- `/admin/muasamcong` — trang tra cứu thuốc/Smart Pricing.
- `/admin/muasamcong/contractors` — lịch sử nhà thầu.
- `/admin/muasamcong/hsmt` — tra cứu HSMT.
- `/admin/muasamcong/synced` — danh sách thuốc đã đồng bộ.
- `/admin/muasamcong/wishlist` — Wishlist thuốc cần theo dõi.
- `/admin/muasamcong/config` — cấu hình tích hợp.

API hiện hữu:

- `GET /api/muasamcong`
- `POST /api/muasamcong/search-pricing`

Route web search dùng `auth:admin` và permission `view_muasamcong`; config dùng permission riêng `muasamcong.config.manage`. Các mutation đồng bộ/xóa/sửa phải tiếp tục kiểm tra permission phù hợp (hiện luồng sync dùng `muasamcong.pricing.sync`).

## 4. Tra cứu thuốc / Smart Pricing

Livewire chính: `Modules/Muasamcong/Livewire/TracuuThuoctrungthau.php`.

Nhiệm vụ:

- nhận từ khóa: tên thuốc, hoạt chất, mã TBMT, công ty trúng thầu theo khả năng upstream;
- tìm snapshot database trước;
- nếu có snapshot cùng normalized keyword thì dùng snapshot, không gọi API;
- hiển thị thời gian tra cứu gần nhất và trạng thái nguồn `Từ database`;
- `Tìm kiếm mới` bắt buộc gọi upstream và cập nhật snapshot;
- với tra cứu TBMT, tải đủ các page upstream thay vì chỉ page đầu 20 dòng;
- gộp/chống trùng kết quả;
- phân trang local 20 dòng/trang;
- filter local theo tên thuốc, hoạt chất, nhóm thuốc, đơn vị trúng thầu;
- checkbox lựa chọn phải tồn tại xuyên trang;
- checkbox header chỉ chọn/bỏ chọn trang hiện tại;
- có `Chọn tất cả kết quả`, `Bỏ chọn tất cả`, và modal/danh sách `Đã chọn (N)`;
- đồng bộ toàn bộ selected source IDs, kể cả lựa chọn nằm ở nhiều trang khác nhau.

### Winner trong Smart Pricing

Quy tắc quan trọng:

- Nếu upstream trả `winningName` thì hiển thị winner. Dữ liệu 2026 đã quan sát có trường hợp trả winner và PHẢI giữ.
- Dữ liệu 2025 có nhiều trường hợp `winningName` rỗng. UI phải thể hiện `Nguồn không cung cấp` thay vì suy đoán.
- TUYỆT ĐỐI không lấy danh sách winner toàn TBMT rồi tự gán vào từng thuốc khi chưa có khóa mapping winner ↔ lot/medicine đáng tin cậy.

## 5. Snapshot tra cứu

Service: `PricingSearchSnapshotService`.

Mục tiêu:

- normalized keyword để `IB...`, khác hoa/thường hoặc dư khoảng trắng vẫn nhận ra từ khóa cũ;
- lưu kết quả và thời điểm lấy nguồn;
- lưu `last_accessed_at`, `access_count` để biết lịch sử sử dụng;
- tìm lại từ khóa cũ => database-first;
- chỉ `Tìm kiếm mới` mới force upstream refresh.

UI phải cho người dùng biết dữ liệu đang xem là snapshot cũ hay dữ liệu vừa gọi API, tránh tạo cảm giác dữ liệu cache là realtime.

## 6. Pagination TBMT

Service: `PricingTbmtPaginationService`.

Lý do tồn tại: Smart Pricing mặc định page size 20. Trước đây tìm TBMT có hàng trăm hoạt chất nhưng UI chỉ có 20 vì chỉ gọi page 0.

Hiện tại với truy vấn TBMT, service phải:

1. gọi page đầu;
2. đọc total;
3. tải các page tiếp theo;
4. merge và chống duplicate;
5. trả full set cho Livewire;
6. Livewire phân trang local 20 dòng/trang.

Không regression về lại cơ chế chỉ page đầu.

## 7. Đồng bộ thuốc đã chọn

Service: `PricingResultSyncService`.
Model chính: `PricingResult`.

Các field quan trọng được lưu gồm (không giới hạn):

- `source_id`
- `type`, `tab`
- `don_vi_tinh`
- `ma_tbmt`
- `ten_cdt_bmt`, `ma_cdt`
- `winning_code`, `winning_name`
- `bid_form`, `medicines`
- `ngay_dang_tai_kqlcnt`
- `dia_diem`
- `don_gia`
- `ten_thuoc`, `ten_hoat_chat`, `nong_do`, `duong_dung`, `dang_bao_che`
- `han_dung`
- `ten_co_so_san_xuat`, `nuoc_san_xuat`
- `quy_cach_dong_goi`
- `so_luong`, `nhom_thuoc`
- `so_nha_thau_tham_du`
- `so_quyet_dinh`, `ngay_ban_hanh_quyet_dinh`
- `gdklh_gpnk`
- `raw_payload`
- `synced_by`, `synced_at`

`raw_payload` giữ snapshot nguồn tại thời điểm sync.

### Trang Danh sách đã đồng bộ

Route: `/admin/muasamcong/synced`.
Livewire: `SyncedPricingList`.

Nhiệm vụ hiện tại:

- search/filter danh sách đã lưu;
- pagination;
- checkbox từng dòng;
- checkbox all trang hiện tại;
- lựa chọn tồn tại xuyên trang;
- bulk delete;
- chọn đúng một dòng để edit;
- edit chủ yếu dữ liệu KQLCNT bổ sung: `Đơn vị trúng thầu`, `Mã nhà thầu`, `Số quyết định KQLCNT`, `Ngày ban hành quyết định`;
- hiển thị đầy đủ hơn: thuốc, nhóm, hoạt chất, nồng độ, giá, số lượng, winner, mã winner, chủ đầu tư/BMT, TBMT, quyết định, nhà sản xuất, nước sản xuất, thời gian sync.

Chỉnh sửa thủ công winner chỉ cập nhật record đã sync; không được sửa ngược snapshot tìm kiếm gốc hoặc giả vờ dữ liệu đó đến từ upstream.

## 8. Wishlist thuốc cần theo dõi

Model: `PricingWishlist`.
Service: `PricingWishlistService`.
Route: `/admin/muasamcong/wishlist`.

Wishlist là một DANH SÁCH persistence, không phải card tạm thời trong session.

Mỗi user/admin có danh sách riêng theo `user_id`; unique theo `user_id + source_id`. Snapshot thuốc được lưu để danh sách vẫn có thông tin ngay cả khi upstream thay đổi.

Trang wishlist cần hỗ trợ search, pagination, thông tin thuốc/hoạt chất/nồng độ/nhóm/TBMT/thời gian theo dõi và điều hướng mở lại tra cứu.

## 9. Lịch sử nhà thầu

Trang: `/admin/muasamcong/contractors`.
Livewire: `ContractorHistory`.
Service: `ContractorHistoryService`.

Nghiệp vụ:

- tìm doanh nghiệp/nhà thầu;
- tra lịch sử các TBMT/gói thầu đã tham gia;
- hỗ trợ khoảng ngày;
- xem KQLCNT của từng TBMT;
- dữ liệu danh sách nhà thầu trúng thầu có thể rất lớn nên modal winner phải có search và giới hạn render ban đầu (đã triển khai pattern `<x-search>` + load thêm).

UI `Lịch sử nhà thầu` được tách khỏi trang tra cứu thuốc để tránh trang chính quá nặng.

## 10. KQLCNT và dữ liệu winner

Service: `KqlcntService`.

Các endpoint upstream đã điều tra trong quá trình phát triển gồm:

### Danh sách contract theo TBMT

```text
POST /o/egp-portal-contractor-selection-v2/services/econsign/contract-info/list-contract-for-po?token=...
Payload: {"notifyNo":"IB..."}
```

Response có thể chứa:

- `contractorCode`, `contractorName` trực tiếp;
- hoặc `contractorPassList` là JSON string;
- `contractNo`, `resultId`, `investorName`, `notifyNo`, ...

Ví dụ đã xác minh `IB2500539527` có thể trả nhiều contract và lấy được winner.

Nhưng `IB2600099293` đã được test trả HTTP 200 với `[]` từ endpoint này. Vì vậy `Đồng bộ lại` không thể tạo winner nếu upstream source đang trả rỗng. Không được coi HTTP 200 là có dữ liệu.

### KQLCNT TTC/LDT

Endpoint `lcnt_tbmt_ttc_ldt` nhận token và payload `{"id":"<notify-id>"}` trong các case đã điều tra.

### get-result-replace

```text
POST .../input-result-replace/get-result-replace
Payload: {"id":"<resultId>"}
```

Đã test một result của Nam Sơn và response chỉ có `replaceResultsList`, không cung cấp mapping winner → lot cần thiết trong case đó.

## 11. HSMT / danh mục hàng hóa thuốc

Service: `HsmtDetailService`, `HsmtSnapshotService`.

Endpoint đã xác minh:

```text
POST /o/egp-portal-contractor-selection-v2/services/lcnt_tbmt_hsmt?token=...
Payload: {"id":"<notifyId>","processApply":"LDT"}
```

Response top-level đã quan sát:

```text
bidoInvFileDTO
dtoList
bidoInvBiddingDTO
bidaInvChapterConfList
bidInvContractorOfflineDTO
```

Các form quan trọng đã gặp:

- `BD.DT.02.1854`
- `BD_DATA_TABLE`
- `BD.MT.02.1220`

Trong `BD.DT.02.1854.formValue` và `BD.MT.02.1220.formValue`, JSON có `Table` chứa danh mục thuốc.

Case thực tế `IB2600008930`, notifyId `894fb581-2622-421e-aada-320c53332745`:

- `Table` có 285 rows;
- row có `lotNo` dạng `PP2500656487`;
- `medicineCode` ví dụ `PL001`;
- có `lotName`, `quantity`, `pricePlan`, `groupMedicine`, `nongDo`, `duongDung`, `dangBaoChe`, `uom`, ...;
- không có `contractorCode`, `contractorName`, `winningCode`, `resultId` trong row.

Kết luận đã xác minh: `lcnt_tbmt_hsmt` cung cấp danh mục HSMT/toàn bộ thuốc của TBMT, KHÔNG tự nó cung cấp mapping winner → từng lot/medicine.

## 12. Vấn đề chưa giải quyết: winner ↔ lot/medicine

Đây là điểm quan trọng nhất để AI sau không lặp lại hàng giờ điều tra cũ.

Ta hiện có hai nguồn độc lập:

```text
list-contract-for-po
 -> có thể biết các công ty trúng thầu của toàn TBMT

lcnt_tbmt_hsmt
 -> biết toàn bộ lô/thuốc của TBMT
```

Nhưng chưa tìm được khóa đáng tin cậy để xác định chính xác:

```text
Công ty A trúng PPxxxx nào / thuốc nào
```

`lotResultDTO` trong contract đã được kiểm tra ở một số case và các list table price/scope đều rỗng. `get-result-replace` cũng không giải quyết case đã thử. HSMT response không chứa contractor code.

DO NOT:

- join theo thứ tự array;
- gán tất cả winner cho tất cả thuốc;
- đoán theo tên thuốc;
- dùng `contractorPassList` của toàn contract để kết luận từng medicine;
- ghi mapping suy đoán vào database.

Nếu tiếp tục nghiên cứu, ưu tiên Network trên trang KQLCNT chính thức và tìm request phát sinh khi UI hiển thị/expand danh sách hàng hóa hoặc chi tiết một nhà thầu/lô.

## 13. HSMT snapshot/file cố định

Quan điểm nghiệp vụ đã chốt: danh mục mời thầu sau khi gói đã trúng thầu về bản chất là dữ liệu lịch sử cố định. Khi đã tải/snapshot thành công, nên ưu tiên đọc dữ liệu server/database/file đã lưu; chỉ refresh khi người dùng chủ động `Đồng bộ lại`.

Không biến việc mở modal thành một loạt API call upstream không cần thiết.

## 14. UI/UX đã chốt

- Admin UI dùng layout chuẩn dự án và Tailwind.
- Input search phải có border rõ tương tự ô `Tên thuốc, hoạt chất hoặc mã TBMT`.
- Modal KQLCNT phải nằm trong viewport, body scroll được, footer luôn truy cập được; bảng rộng phải horizontal-scroll.
- Winner list trong modal không render vài trăm row một lần: có `<x-search>`, giới hạn ban đầu và `Xem thêm`.
- Các trang lớn tách riêng: tra cứu, lịch sử nhà thầu, synced, wishlist, HSMT/config.
- Pagination kết quả thuốc local 20 rows/page sau khi đã tải full TBMT.
- Cross-page checkbox là yêu cầu nghiệp vụ, không reset selection khi chuyển page.

## 15. Security / integration rules

- Chỉ HTTPS tới host allowlist `muasamcong.mpi.gov.vn`.
- Production SSL verify phải bật.
- Không log token/cookie/Authorization hoặc raw secret.
- Không commit token/cookie.
- Upstream token/cookie có thể hết hạn.
- Endpoint/schema upstream có thể đổi không báo trước.
- Khi response bất thường: dump metadata/keys/count trước, không dump secret.
- HTTP 200 không đồng nghĩa có dữ liệu; luôn kiểm tra body/count/schema.

## 16. Services và trách nhiệm

- `MuaSamCongService`: HTTP integration nền tảng, Smart Pricing/HSMT search và chuẩn hóa request/response chung.
- `PricingTbmtPaginationService`: tải đủ các page Smart Pricing cho truy vấn TBMT.
- `PricingSearchSnapshotService`: cache/snapshot theo từ khóa và lịch sử truy cập.
- `PricingResultSyncService`: map và persist các thuốc người dùng chọn.
- `PricingWishlistService`: persist wishlist theo user.
- `ContractorHistoryService`: lịch sử tham dự thầu theo doanh nghiệp.
- `KqlcntService`: lấy/chuẩn hóa KQLCNT, contract và winner-level metadata.
- `HsmtDetailService`: lấy/parse chi tiết HSMT và danh mục hàng hóa/thuốc.
- `HsmtSnapshotService`: lưu/tái sử dụng snapshot HSMT.
- `MuasamcongConfigService`: quản trị cấu hình `.env`/integration.

Giữ separation này; không gom HTTP parsing/database/UI trở lại một Livewire khổng lồ.

## 17. Models/persistence cần biết

Ít nhất các domain persistence hiện tại gồm:

- `PricingResult` — thuốc đã đồng bộ.
- `PricingSearchSnapshot` — cache/lịch sử kết quả tìm kiếm.
- `PricingWishlist` — wishlist theo user.
- KQLCNT/HSMT snapshot models/tables tương ứng trong code hiện tại.

AI tiếp quản phải đọc toàn bộ `Modules/Muasamcong/Models` và migrations trước khi thay schema. Không được dựa vào README cũ nói module không có database.

## 18. Test và pre-merge checklist

Tối thiểu trước merge main:

```bash
php artisan optimize:clear
php artisan migrate:status
php artisan route:list --path=muasamcong
vendor/bin/pint --test Modules/Muasamcong tests/Feature/Muasamcong
php artisan test tests/Feature/Muasamcong
```

Nếu module suite PASS, sau đó nên chạy full regression theo quy trình repository trước merge main.

Manual smoke quan trọng:

1. Search một keyword mới -> API -> badge dữ liệu mới.
2. Search lại cùng keyword -> database snapshot, không gọi API.
3. `Tìm kiếm mới` -> gọi API lại và cập nhật timestamp.
4. Search TBMT có >20 rows -> tải đủ và pagination local.
5. Filter tên thuốc/hoạt chất/nhóm/winner.
6. Trang 1 chọn 2 thuốc, trang 5 chọn 5 -> tổng vẫn 7; sync đủ 7.
7. Winner 2026 có `winningName` vẫn hiển thị.
8. Winner thiếu ở nguồn 2025 hiện `Nguồn không cung cấp`, không suy đoán.
9. Synced list: cross-page checkbox, edit một record, bulk delete.
10. Wishlist: persistence/search/pagination.
11. Contractor history: tìm công ty, mở KQLCNT, modal scroll đúng.
12. HSMT catalogue: load snapshot, horizontal/vertical scroll đúng.

## 19. Những việc nên làm tiếp

Ưu tiên sau merge nếu tiếp tục phát triển:

1. Tìm endpoint/mapping chính xác winner ↔ lot/medicine.
2. Bổ sung automated tests cho cross-page selection và synced management nếu coverage chưa đủ.
3. Bổ sung audit metadata cho manual winner edits nếu nghiệp vụ cần phân biệt dữ liệu upstream và dữ liệu người quản trị bổ sung ở mức field.
4. Đánh giá retention/cleanup cho search snapshots nếu database tăng lớn.
5. Đánh giá queue/background refresh cho TBMT rất lớn nếu synchronous fetch gây chậm.

## 20. Prompt mở chat mới

Có thể dùng nguyên văn:

```text
Hãy tiếp tục phát triển Modules/Muasamcong.

Trước khi làm bất kỳ thay đổi nào:
1. Đọc docs/modules/Muasamcong/README.md.
2. Đọc docs/modules/Muasamcong/AI_HANDOFF.md toàn bộ.
3. Đọc docs/modules/Muasamcong/INFORMATION.md và ANALYSIS.md để có lịch sử kiến trúc.
4. Đọc code hiện tại trong Modules/Muasamcong; nếu docs cũ mâu thuẫn code thì báo rõ và dùng code hiện tại làm source of truth.
5. Đọc tests/Feature/Muasamcong trước khi sửa route/permission.

Các invariant quan trọng:
- Không hard-code/log token/cookie.
- Search cũ ưu tiên snapshot DB; chỉ `Tìm kiếm mới` force API.
- TBMT phải tải đủ page rồi phân trang local.
- Checkbox phải giữ selection xuyên trang.
- `winningName` upstream có thì giữ; thiếu thì không suy đoán.
- Chưa có mapping đáng tin cậy winner ↔ lot/medicine; không tự gán.
- Danh mục HSMT đã snapshot ưu tiên đọc local, refresh chỉ khi người dùng yêu cầu.
- Giữ service boundaries hiện tại và permission/auth admin.

Sau khi đọc xong, hãy tóm tắt trạng thái module, điểm chưa giải quyết và kế hoạch thay đổi trước khi implement.
```
