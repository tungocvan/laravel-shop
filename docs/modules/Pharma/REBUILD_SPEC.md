# Đặc tả xây dựng lại module Pharma

## 1. Goal

Giữ Pharma là canonical owner của Medicine, DrugBidAward, SupplierTracking và PriceList; xây luồng an toàn theo findings `PH-P0-01..04`, correctness/performance trong `ANALYSIS.md` và thứ tự `REFACTOR_PLAN.md`.

## 2. Target Architecture

```text
Authorized Route -> Thin Controller -> Page -> Authorized Livewire/Form
 -> Pharma Application Service -> Model/DB
 -> Import/Export contract -> Shared private storage
 -> PriceList service -> server-side analysis snapshot -> builder -> private download/audit
```

Shared panel nhận registry key server-owned, không nhận class tin cậy từ client. Major decision này dựa trên `PH-P0-03`.

## 3. Database Design

Giữ ba table hiện tại. Thêm forward unique Supplier sau khi chốt key/nullability (`PH-P1-COR-02`). Nếu quotation là chứng từ, thêm quotation header/version/items/file metadata/source hash/status/creator timestamps thay vì chỉ file tạm (Open Questions 2-5). Không lưu public path; lưu private storage key. Thêm audit actor và constraint/status theo quyết định nghiệp vụ.

## 4. Model Design

Giữ ba model, thêm inverse relations và enum/status cast. Derived Supplier fields chỉ được service/domain calculator quản lý. Nếu có Quote model, quan hệ items dùng snapshot giá/nội dung để lịch sử không đổi khi workbook cập nhật. Cơ sở: Model/Database Analysis.

## 5. Service Design

CRUD/query/calculation thuộc service. Import/export mỗi aggregate triển khai interface shared. PriceList tách: source repository/versioning, analyzer, selection validator, builder, private artifact service, audit service. Web service không nhận arbitrary output path (`PH-P1-COR-03`); CLI override là trusted contract riêng.

## 6. Livewire Design

Authorize trong mount/render và từng public mutation. Dùng typed form/state. PriceList public state chỉ có metadata/projection; full workbook ở server snapshot keyed theo user/source hash. Validate/cap search, rows, columns, text. Không trả exception thô. Cơ sở: `PH-P0-01/02`, `PH-P1-SEC-05`.

## 7. Blade/UI Design

Button theo `@can`, nhưng server vẫn enforce. Hiển thị nguồn/version/time, selected count, progress, safe errors và audit ID. External links dùng noopener. Menu PriceList chỉ hiện với capability tương ứng.

## 8. Import Design

XLSX/CSV private temp; fixed mode per aggregate; verified header mapping; normalization/validation/relationship resolution; preload lookup; explicit partial hoặc atomic strategy; dry-run side-effect free; bounded chunk/queue; row/sheet/column report; cleanup. Dựa trên Import Analysis và roadmap P1-05/06/09.

## 9. Export Design

Filter server-side, capped selected IDs, chunk/lazy, queue threshold, private artifact, authorized short-lived download, expiry/cleanup. PriceList source columns phải có allowlist/classification; user text explicit string. Dựa trên `PH-P0-04`, `PH-P1-PERF-01/02`.

## 10. Permissions And Authorization

Capabilities tối thiểu: `view_pharma`, create/update/delete theo aggregate hoặc chung; `import_pharma`, `export_pharma`, `generate_price_list`, và nếu cần `approve/publish_price_list`. Route + policy/Livewire action + download đều kiểm tra. Record ownership/tenant scope phải được xác nhận. Dựa trên `PH-P0-01`.

## 11. Transactions And Data Integrity

CRUD/bulk/import obey explicit transaction contract. DB unique là hàng rào concurrency. Derived values tính server-side. File artifact chỉ được đánh dấu ready sau khi save thành công; DB record và file dùng compensation/outbox nếu cần. Không cascade-delete lịch sử thương mại nếu chưa được nghiệp vụ duyệt.

## 12. Performance Strategy

Cache analysis theo source hash/mtime và không chứa dữ liệu vượt quyền; load workbook tối đa cần thiết; set row/column/file thresholds; queue large generation; no All; chunk import/export; preload medicine keys. Dựa trên `PH-P1-PERF-01/02`.

## 13. Shared Foundation Integration

Shared interface + registry định nghĩa service/modes/capabilities/storage/retention. Pharma cung cấp mapper/rules/query. Không copy normalization/report/storage. Shared changes bắt buộc có regression test cho mọi consumer. Dựa trên `PH-P0-03/04`.

## 14. Event And Listener Design

Events sau commit: Medicine/Award/Supplier changed; import completed; export requested/ready/expired; PriceList generated/approved/downloaded. Payload chỉ chứa IDs/metrics, không workbook rows hoặc sensitive values.

## 15. Queue Design

Queue import/export/generate khi vượt threshold; job mang actor/tenant/capability snapshot có thời hạn và re-authorize khi download; idempotency key theo request/source hash/options; progress, retry, final failure, cleanup. Hiện module chưa có jobs—đây là target theo performance findings.

## 16. Cache Design

Cache workbook analysis projection/server snapshot theo file hash + sheet + access scope; invalidate khi mtime/hash đổi. Không cache authorization lâu hơn role changes. Cache failure không được bỏ qua validation nguồn.

## 17. Logging Strategy

Structured log: correlation, actor, feature, source hash/version, row/column counts, duration, artifact ID, result. Không log full rows, contract URLs, recipient data hoặc raw exceptions cho UI. Redact paths khi không cần.

## 18. Monitoring Strategy

Metrics/alerts: import/export/generate duration/failure, workbook load count/memory, queue age, public/private artifact count, cleanup backlog, denied/tampered actions, duplicate Supplier attempts.

## 19. Rollback Strategy

Additive migrations; backup/dedupe Supplier trước unique. Version quote builder/source. Pause queue during incompatible deploy. Private artifact migration có grace-period. Quarantine cleanup. Không rollback P0 auth/private storage containment.

## 20. Test Strategy

Unit service/calculator/parser/builder; feature route/permission/Livewire; security tampering/data projection/formula/path; import/export fixtures/dry-run/partial/atomic; storage download/expiry; DB migration/concurrency; performance load/memory; production-like MySQL smoke.

## 21. Deployment Checklist

- Chốt open questions và data classification.
- Seed/assign permissions trước middleware.
- Khai báo direct PhpSpreadsheet dependency.
- Backup DB/workbook/public exports; hash/version workbook.
- Migrate private artifacts và Supplier invariant.
- Run full tests/Pint, MySQL migration, frontend build.
- Configure private disk, queue, cleanup, logs/alerts.
- Generate/review sample quote and denied-access tests.

## 22. Implementation Checklist

- [ ] Route/action/download authorization
- [ ] PriceList client projection only
- [ ] Shared service registry/interface
- [ ] Private exports + retention
- [ ] Broken routes resolved
- [ ] Safe strings/errors/output path
- [ ] Supplier key/nullability constraint
- [ ] Bounded/cached/queued processing
- [ ] Quote audit/versioning decision implemented
- [ ] Tests/monitoring/docs complete

## 23. Needs Confirmation Before Coding

1. Permission matrix và tenant/ownership.
2. Quote temporary hay persisted/approved/versioned.
3. Allowed/sensitive source columns.
4. Workbook owner, update and approval process.
5. CLI arbitrary output path policy.
6. Supplier key + required working date.
7. Fixed import mode và partial/atomic policy mỗi aggregate.
8. API requirement.
9. File retention/download expiry.
10. Large-file queue thresholds và production DB/queue.
