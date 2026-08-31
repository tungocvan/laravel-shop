# Quy trình Refactor Module sạch

## 1. Mục tiêu

Tài liệu này là quy trình bắt buộc cho Major Refactor của các Module trong repository.

Mục tiêu:

- xác định đúng ownership trước khi sửa source;
- không xóa legacy/compatibility code chỉ dựa trên tên file hoặc implementation tương tự;
- giữ dependency direction rõ ràng;
- phát hiện architecture drift giữa tài liệu và runtime;
- cập nhật architectural contract cùng thay đổi source;
- đóng refactor ở một boundary rõ ràng thay vì mở rộng vô hạn sang Module khác.

Quy trình này bổ sung cho `docs/GITHUB_COLLABORATION_WORKFLOW.md`. Các quy tắc GitHub/branch/PR/test/handoff hiện hành vẫn áp dụng.

## 2. MODULE.md là mandatory architectural contract

Mỗi Module phải có:

`docs/modules/<Module>/MODULE.md`

Đây là architectural source of truth lâu dài của Module.

Major Refactor không được bắt đầu implementation nếu chưa đọc `MODULE.md`.

Nếu Module chưa có `MODULE.md`, bước đầu tiên của refactor là audit runtime và xây dựng contract để người dùng phê duyệt.

Nếu `MODULE.md` và runtime mâu thuẫn, không được mặc định tài liệu hoặc source là đúng. Phải đánh dấu `ARCHITECTURE DRIFT`, audit bằng chứng thực tế, đề xuất target architecture và chờ phê duyệt trước implementation.

## 3. Những thay đổi bắt buộc cập nhật MODULE.md

PR phải cập nhật `MODULE.md` trong cùng architectural slice nếu thay đổi:

- purpose/responsibility;
- canonical ownership;
- explicit non-ownership;
- direct dependencies;
- canonical routes;
- public integration boundaries;
- persistence ownership;
- compatibility/deprecation boundaries;
- quarantine boundaries;
- refactor invariants.

Không merge source và `MODULE.md` khi architectural contract mâu thuẫn nhau.

## 4. Route-first audit bắt buộc

Audit runtime phải bắt đầu từ route và không giới hạn ở route file của Module đang refactor.

Phải trace:

`Route → Controller → View/Livewire → Service → Model/Persistence → Callers → Cross-module dependencies`

Nếu URL nằm trong namespace chung như `/admin/*`, prefix URL không được dùng làm bằng chứng ownership. Phải kiểm tra Module thực sự đăng ký route và runtime phía sau route đó.

GitHub code search zero-result không phải bằng chứng duy nhất để kết luận dead code. Phải dùng thêm route, imports, DI/container bindings, Blade/Livewire references, tests, service providers, manifests, persistence và caller/dependency evidence phù hợp.

## 5. Ownership map

Trước implementation phải lập ownership map tối thiểu gồm:

- Module đang refactor thực sự sở hữu capability nào;
- capability nào thuộc Module chuyên trách khác;
- direct dependencies;
- integration dependencies;
- consumers;
- persistence ownership;
- compatibility boundaries;
- quarantine boundaries.

Ownership được xác định theo business responsibility/runtime contract, không theo folder/class name đơn thuần.

## 6. Classification gate

Mỗi artifact/boundary bị tác động phải được classify:

### KEEP

Canonical runtime hoặc contract vẫn thuộc Module.

### REHOME

Capability còn cần thiết nhưng canonical ownership thuộc Module khác. Rehome phải cập nhật callers/imports/tests và dependency contract.

### DELETE

Chỉ xóa khi đã có caller/dependency proof và canonical replacement hoặc bằng chứng runtime không còn cần artifact.

### QUARANTINE

Artifact nguy hiểm, persistence-sensitive hoặc chưa đủ bằng chứng. Không mở rộng, rehome, beautify hoặc xóa ngoài một phase được phê duyệt riêng.

### DEFER

Debt thuộc boundary/Module khác và không phải blocker cho target architecture hiện tại. Phải ghi rõ owner/target Module và exit condition.

## 7. Approval gate

Sau audit phải trình:

- current architecture;
- architecture drift nếu có;
- target ownership;
- KEEP / REHOME / DELETE / QUARANTINE / DEFER manifest;
- dependency impact;
- regression scope;
- explicit out-of-scope.

Không tạo implementation branch hoặc sửa source cho Major Refactor trước khi target plan được người dùng phê duyệt, trừ khi người dùng đã cho phép rõ trong cùng workflow.

## 8. Implement theo coherent boundary

Không thực hiện một lần cleanup toàn Module nếu không cần thiết.

Chia theo architectural boundary đủ lớn để có ý nghĩa nhưng đủ nhỏ để debug được.

Mỗi slice nên bao gồm cùng lúc:

- source change;
- caller/import updates;
- contract tests;
- MODULE.md nếu contract thay đổi;
- handoff checkpoint phù hợp.

Không yêu cầu người dùng pull/test sau từng file nhỏ. Gom thành batch coherent rồi test.

## 9. Contract-test rule

Khi DELETE hoặc REHOME legacy artifact, phải tìm các test đang bảo vệ path/class/behavior cũ trong cùng slice.

Contract tests phải bảo vệ target architecture, không bảo vệ stale legacy paths.

Không được sửa test chỉ để làm xanh nếu runtime target architecture chưa được chứng minh.

## 10. Dependency rule

`Modules/<Module>/config/module.php` và `docs/modules/<Module>/MODULE.md` phải thống nhất về direct dependencies.

Phải phân biệt:

- direct dependency: Module cần dependency để boot/operate theo contract;
- integration dependency: sử dụng capability của Module khác qua boundary nhưng không chuyển ownership;
- consumer: Module khác sử dụng capability của Module hiện tại.

Không thêm hard dependency âm thầm.

## 11. Persistence rule

Model/migration/table/file-state không được xóa/rehome chỉ vì không tìm thấy route hoặc caller trực tiếp.

Persistence change cần:

- ownership proof;
- migration/data compatibility plan;
- rollback/recovery consideration khi applicable;
- focused regression;
- explicit approval nếu có rủi ro dữ liệu.

## 12. Regression gate

Regression theo phạm vi thực sự bị ảnh hưởng:

1. syntax/lint cần thiết;
2. focused tests cho slice;
3. Module regression;
4. dependency/consumer regression nếu boundary bị ảnh hưởng;
5. route verification;
6. Pint changed-files;
7. frontend build nếu UI/assets thay đổi;
8. manual UI smoke cho canonical surfaces.

Không mặc định chạy full project regression. Full regression chỉ khi shared/core infrastructure hoặc release scope thực sự yêu cầu.

## 13. UI gate

Nếu refactor ảnh hưởng Admin UI phải tuân thủ `.codex/standards/ADMIN_UI_STANDARD.md`.

Nếu ảnh hưởng PWA file handoff phải tuân thủ `docs/PWA_EXTERNAL_FILE_HANDOFF.md`.

UI acceptance phải kiểm tra canonical surfaces và các specialized surfaces bị dependency change tác động.

## 14. Debt handoff rule

Không kéo cleanup của Module kế tiếp vào refactor hiện tại chỉ để đạt cảm giác “sạch tuyệt đối”.

Debt được DEFER phải ghi:

- debt là gì;
- canonical owner/target Module;
- vì sao không xử lý trong scope hiện tại;
- exit condition;
- regression/caller proof cần có khi xử lý sau.

Khi refactor target Module bắt đầu, deferred debt phải được đưa vào audit đầu kỳ.

## 15. Closeout gate

Một Major Refactor chỉ được đánh dấu COMPLETE khi:

- target ownership đã đạt;
- canonical routes đúng;
- direct dependencies đúng;
- contract tests phản ánh architecture mới;
- required regression PASS;
- build/Pint/UI gate applicable đã PASS;
- quarantine/deferred debt được document;
- `MODULE.md` đồng bộ runtime;
- `COLLABORATION_HANDOFF.md` được cập nhật;
- PR sẵn sàng merge.

Sau merge phải sync `main` và xác nhận working tree clean.

## 16. Pipeline chuẩn

```text
READ MODULE.md
      ↓
ROUTE-FIRST AUDIT
      ↓
RUNTIME / CALLER TRACE
      ↓
ARCHITECTURE DRIFT CHECK
      ↓
OWNERSHIP + DEPENDENCY MAP
      ↓
KEEP / REHOME / DELETE / QUARANTINE / DEFER
      ↓
USER APPROVAL
      ↓
IMPLEMENT BY COHERENT BOUNDARY
      ↓
UPDATE CONTRACT TESTS + MODULE.md
      ↓
FOCUSED / MODULE / IMPACTED REGRESSION
      ↓
ROUTES + PINT + BUILD + UI
      ↓
DEBT HANDOFF
      ↓
HANDOFF CLOSEOUT
      ↓
PR / MERGE
      ↓
SYNC MAIN
      ↓
MODULE REFACTOR COMPLETE
```

## 17. Quan hệ giữa các tài liệu Module

### MODULE.md

Architectural contract lâu dài: Module là gì, sở hữu gì, không sở hữu gì, phụ thuộc ai, persistence/integration/quarantine/debt nào phải bảo vệ.

### COLLABORATION_HANDOFF.md

Checkpoint công việc: phase/MR hiện tại, trạng thái implementation/test/UI/PR và bước tiếp theo.

### ANALYSIS.md

Bằng chứng/phân tích chi tiết của một đợt audit hoặc refactor. Không thay thế contract.

### README.md

Developer onboarding và cách tiếp cận/sử dụng Module. Không phải architectural source of truth.

## 18. Rule ưu tiên khi tài liệu xung đột

Không tự động chọn một tài liệu làm đúng khi có xung đột với runtime.

Thứ tự xử lý là:

1. phát hiện và ghi nhận drift;
2. kiểm tra route/runtime/callers/persistence/dependencies;
3. đối chiếu `MODULE.md`, handoff, standards và historical decisions;
4. đề xuất target architecture;
5. người dùng phê duyệt;
6. sửa source và contract cùng nhau.

Mục tiêu không phải làm source giống tài liệu cũ, mà làm runtime và architectural contract cùng phản ánh kiến trúc đã được phê duyệt.
