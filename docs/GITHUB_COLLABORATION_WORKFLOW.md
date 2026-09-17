# Quy trình làm việc ChatGPT ↔ GitHub ↔ Local

## Mục tiêu

Tài liệu này chuẩn hóa cách làm việc giữa người dùng, ChatGPT và repository GitHub của dự án.

Repository chính:

`git@github.com:tungocvan/laravel-shop.git`

Mục tiêu là để ChatGPT trực tiếp phân tích/sửa code trên GitHub, còn người dùng chủ yếu `git pull`, chạy CLI/test/manual UI và gửi output để debug theo từng bước.

### Major/Clean Module Refactor — entry point bắt buộc

Khi người dùng gửi một yêu cầu tương đương:

```text
Áp dụng refactor Module
Module: <Module>
```

hoặc `Refactor Module: <Module>` / `Major Refactor Module <Module>`, phải kích hoạt **Clean Module Refactor workflow**.

Trước khi đề xuất implementation, bắt buộc đọc và áp dụng đồng thời:

```text
docs/GITHUB_COLLABORATION_WORKFLOW.md
docs/GITHUB_COLLABORATION_WORKFLOW_REFACTOR_MODULE.md
docs/MODULE_REFACTOR_WORKFLOW.md
docs/modules/<Module>/MODULE.md
```

và `docs/modules/<Module>/COLLABORATION_HANDOFF.md` khi tồn tại, sau đó đối chiếu manifest, routes, source, persistence, tests và cross-module callers/dependencies thực tế.

Trigger Refactor Module chỉ cấp quyền **bootstrap + audit chỉ đọc**. Nó không tự cấp quyền tạo branch, sửa source, xóa/rehome artifact, migration, thao tác destructive, tạo PR hoặc merge. Target architecture/manifest refactor phải được trình và người dùng phê duyệt trước implementation, trừ khi người dùng đã cấp quyền implementation rõ ràng.

Nếu `docs/modules/<Module>/MODULE.md` chưa tồn tại, đây là **Missing MODULE.md Gate**: không bắt đầu implementation; phải audit runtime/ownership, dùng `docs/modules/MODULE_TEMPLATE.md` để đề xuất Module Contract, chờ người dùng phê duyệt và tạo/cập nhật contract trước khi triển khai Major Refactor.

Nếu `MODULE.md` mâu thuẫn source/schema/config/routes/tests, phải đánh dấu **ARCHITECTURE DRIFT**, chứng minh runtime/callers, đề xuất target architecture và chờ phê duyệt; không mặc định tài liệu hoặc runtime là target đúng.

Mọi architectural refactor PR làm thay đổi responsibility, ownership/non-ownership, dependency, canonical routes, integration boundary, persistence, compatibility/deprecation, quarantine hoặc refactor invariants phải cập nhật `docs/modules/<Module>/MODULE.md` trong cùng PR. `COLLABORATION_HANDOFF.md` vẫn bắt buộc theo các gate của tài liệu này.

Chi tiết route-first audit, `KEEP / REHOME / DELETE / QUARANTINE / DEFER`, contract-test gate, regression, debt handoff và closeout nằm trong hai tài liệu Refactor Module nêu trên và là quy tắc bắt buộc khi trigger này được kích hoạt.

## 1. Vai trò

ChatGPT chịu trách nhiệm:

- đọc code thực tế trên GitHub trước khi đề xuất
- phân tích kiến trúc, dependency và tests liên quan
- tạo feature branch khi task đủ lớn
- sửa/tạo file trực tiếp trên branch GitHub
- chia công việc thành MR/Phase nhỏ, coherent
- thêm/cập nhật automated tests
- hướng dẫn người dùng pull/test
- phân tích output rồi mới quyết định bước tiếp theo

Người dùng chịu trách nhiệm:

- giữ local repository đồng bộ đúng branch
- chạy các lệnh CLI được yêu cầu
- chạy manual UI smoke khi cần
- gửi output nguyên văn để debug
- phê duyệt plan/architecture trước thay đổi lớn

## 2. Quy tắc trước khi sửa code

Trước khi code, ChatGPT phải:

1. kiểm tra branch/base branch
2. đọc code hiện tại trên GitHub
3. đọc tests liên quan
4. xác định dependency và backward compatibility
5. xác định nguyên nhân hoặc phạm vi thay đổi
6. đề xuất plan/MR nếu task lớn

Không sửa trực tiếp `main` nếu chưa thống nhất.

Không force-push, rewrite history hoặc thay đổi source ngoài phạm vi khi không cần thiết.

## 3. Workflow chuẩn

Luồng mặc định:

```text
GitHub inspection
    ↓
Architecture / root-cause analysis
    ↓
Plan / MR checklist
    ↓
User approval nếu cần
    ↓
Feature branch
    ↓
Batch implementation
    ↓
User git pull
    ↓
Focused tests
    ↓
Debug nếu fail
    ↓
Module regression
    ↓
Impacted / cross-module regression nếu có dependency liên quan
    ↓
Manual UI smoke
    ↓
Git-clean verification
    ↓
Cập nhật COLLABORATION_HANDOFF.md
    ↓
Gate trước khi tạo PR
    ↓
Tạo/review PR
    ↓
Refresh COLLABORATION_HANDOFF.md trước merge
    ↓
Merge main
    ↓
Push main
    ↓
Delete feature branch
```

**Full project regression không phải gate mặc định cho từng MR/Module.** Chỉ chạy khi phạm vi thay đổi có mức ảnh hưởng toàn hệ thống, thay đổi shared/core infrastructure rộng, release/checkpoint yêu cầu, hoặc người dùng yêu cầu rõ.

## 4. Làm việc theo batch

Không yêu cầu `git pull` sau từng file nhỏ.

Một MR nên được hoàn thành theo batch hợp lý gồm:

- code
- tests
- docs liên quan

Sau đó mới yêu cầu người dùng `git pull` và test một lần.

## 5. Quy tắc yêu cầu CLI

Nếu cần 1 lệnh, chỉ đưa đúng 1 lệnh.

Nếu cần 2 lệnh, nói rõ `Chạy 2 lệnh` và đưa đúng 2 lệnh.

Nếu cần 3 lệnh, nói rõ `Chạy 3 lệnh` và đưa đúng 3 lệnh.

Sau đó dừng và chờ output.

Không đưa trước một chuỗi dài lệnh cho nhiều bước chưa tới.

### 5.1 Pull và kiểm thử theo hai tầng

Khi ChatGPT yêu cầu cập nhật một batch mới, hướng dẫn phải cung cấp trong cùng một lần:

1. lệnh `git pull --ff-only`
2. **Test 1**: kiểm thử tập trung cho phần vừa thay đổi
3. **Test 2**: Module/system regression phù hợp với phạm vi thay đổi

Người dùng thực hiện theo điều kiện:

- nếu Test 1 **FAIL**, dừng, không chạy Test 2 và gửi nguyên output lỗi
- nếu Test 1 **PASS**, chạy ngay Test 2 rồi gửi kết quả của cả hai tầng

Không tách ba bước trên thành ba lượt trao đổi nếu các lệnh kiểm thử đã xác định được tại thời điểm yêu cầu pull.

## 6. Khi GitHub write bị chặn

Nếu thao tác write GitHub thất bại:

1. nói rõ thao tác nào bị lỗi
2. không chuyển ngay sang bắt người dùng tự viết code
3. yêu cầu CLI kiểm tra tối thiểu
4. chờ output
5. xác định nguyên nhân
6. tiếp tục write qua GitHub khi có thể

## 7. Không có background work

Nếu ChatGPT nói sẽ tiếp tục xử lý thì phải thực hiện ngay trong cùng turn khi tool cho phép.

Không để người dùng chờ một tiến trình nền không tồn tại.

Nếu cần user kích hoạt bước tiếp theo, nói rõ ví dụ:

`Tiếp tục MR-2`

## 8. Test strategy

Test theo tầng, ưu tiên đúng phạm vi thay đổi:

1. Syntax / lint cần thiết
2. Focused tests cho phần vừa sửa
3. Module regression cho Module đang triển khai
4. Impacted/cross-module regression cho các Module hoặc shared boundary thực sự liên quan
5. Full project regression chỉ khi có lý do toàn hệ thống rõ ràng

Quy tắc mặc định:

- không chạy `php artisan test` toàn project chỉ vì một MR sắp merge;
- với project nhiều Module, regression phải tập trung vào Module đang làm và các Module/shared infrastructure có dependency hoặc contract bị tác động;
- trước khi chọn impacted regression, phải đọc dependency/source/tests để giải thích vì sao Module hoặc test suite đó liên quan;
- không kéo các Module độc lập vào regression nếu source/contract hiện tại không cho thấy tác động;
- full project regression chỉ applicable khi thay đổi chạm shared/core infrastructure rộng, bootstrap/autoload/module framework, security/auth boundary toàn hệ thống, migration/schema dùng chung, release/checkpoint diện rộng, hoặc khi người dùng yêu cầu rõ;
- nếu full project regression không applicable, handoff/PR gate ghi `NOT APPLICABLE — module-scoped regression strategy`, không coi đó là gate thiếu.

Không chạy full regression sau mọi chỉnh sửa nhỏ.

## 9. Debug khi test fail

Không sửa code theo phỏng đoán.

Phân loại nguyên nhân trước:

- code mới
- test
- environment
- permission/ownership
- database
- runtime state
- dependency
- config/cache
- module khác

Chỉ refactor module khác khi đã chứng minh nó là nguyên nhân.

## 10. Manual UI smoke

Nếu task có UI, kiểm tra tối thiểu:

- trang load bình thường
- action thành công
- refresh giữ đúng state
- validation đúng
- permission đúng
- không 404/500
- không lỗi Livewire quan trọng
- browser console không có lỗi quan trọng

### 10.1 Admin UI/UX standard bắt buộc

Mọi task có liên quan tới giao diện Admin — gồm tạo mới, sửa, refactor, review hoặc acceptance UI — **bắt buộc phải đọc và tuân thủ**:

```text
.codex/standards/ADMIN_UI_STANDARD.md
```

Đây là canonical Admin UI/UX standard của repository. AI không được tự tạo một design system riêng cho từng Module khi repository đã có chuẩn hoặc shared component tương ứng.

Trước khi sửa Admin UI, AI phải:

1. đọc `.codex/standards/ADMIN_UI_STANDARD.md`
2. đọc layout/shell và shared components thực tế đang được dùng
3. kiểm tra UI hiện tại của Module và các Module tương đồng khi cần
4. xác định component/pattern nào phải reuse trước khi tạo mới
5. giữ đúng ownership giữa Admin shell và feature view

Các nguyên tắc bắt buộc gồm:

- dùng canonical admin layout hiện hành, ví dụ `Admin::layouts.master` khi applicable
- ưu tiên workspace-first cho màn hình nhiều chức năng; không dàn tất cả chức năng thành chuỗi card dài nếu không cần
- page Blade chỉ là shell; interactive feature UI thuộc Livewire Blade
- ưu tiên class-based Livewire và không đặt business logic/query DB trong Blade
- reuse shared inputs, searchable select, modal, pagination, status badge, upload, import/export và các component chuẩn khi đã tồn tại
- form control phải có boundary/focus/error/disabled/read-only state rõ ràng; không dùng borderless input làm mặc định
- dataset lớn phải pagination có giới hạn; không dùng `All` không giới hạn
- destructive action phải permission-aware và confirmation rõ ràng
- loading/disabled state phải ngăn double-submit với mutation có thể mất thời gian
- responsive và accessibility là acceptance criteria, không phải phần tùy chọn
- không hardcode width/spacing của Sidebar/Header/Footer trong feature view; Admin shell sở hữu layout tổng thể
- không thực hiện global frontend migration ngoài phạm vi chỉ để hoàn thành một Module

Khi UI có thay đổi đáng kể, trước khi đánh dấu hoàn tất phải kiểm tra actual rendered UI ở representative desktop và mobile widths. Phải đánh giá tối thiểu: visual hierarchy, spacing, content width, form/table usability, sidebar/shell balance, responsive behavior, overflow, loading/error states và action visibility.

Nếu tài liệu UI cũ của Module mâu thuẫn với `.codex/standards/ADMIN_UI_STANDARD.md`, phải đối chiếu source/shared components hiện tại; canonical standard và repository reality được ưu tiên hơn generic hoặc historical UI guidance.

### 10.2 PWA download/open file gate bắt buộc

Khi một Module hoặc Client application thêm/sửa hành vi tải hoặc mở file trên bề mặt có thể chạy dưới installed PWA, phải đọc và áp dụng:

```text
docs/PWA_EXTERNAL_FILE_HANDOFF.md
```

Quy tắc chung:

- không để top-level navigation của installed PWA bị thay thế trực tiếp bởi response Excel/PDF/CSV/attachment nếu việc đó làm mất workspace hoặc navigation stack hiện tại;
- với file authenticated cùng origin, ưu tiên kích hoạt request từ chính authenticated PWA context trong khi giữ nguyên top-level application page;
- không mặc định ép protected download sang browser ngoài vì session/cookie của browser và installed PWA có thể khác nhau theo platform;
- không tạo public URL chỉ để né vấn đề PWA download;
- không cache rộng private binary response trong service worker;
- desktop/browser thông thường nên giữ native download behavior nếu không có bằng chứng cần override;
- tránh user-agent sniffing nếu có thể; ưu tiên phát hiện standalone/display-mode;
- nếu native viewer của OS không cung cấp nút quay lại PWA, acceptance tập trung vào việc PWA workspace vẫn còn sống và người dùng quay lại app vẫn ở đúng context.

Manual acceptance phải kiểm tra iOS installed PWA và Android installed PWA khi phạm vi có file handoff trên mobile, cùng với desktop/browser regression. Nếu chưa có thiết bị/platform để xác nhận thì gate phải ghi `NOT VERIFIED`, không tự suy luận PASS.

## 11. Git working tree

Sau runtime operation có khả năng ghi file, phải kiểm tra:

```bash
git status
```

Mục tiêu:

`nothing to commit, working tree clean`

Runtime state/cache/user settings trên production không được làm tracked source dirty nếu kiến trúc không yêu cầu.

### 11.1 Cơ chế bật/tắt và autoload Module toàn project

Đây là quy tắc chung cho **toàn bộ hệ thống Module**, không dành riêng cho bất kỳ Module cụ thể nào.

Các source phải được đọc/đối chiếu khi xử lý bật/tắt hoặc autoload Module:

```text
Modules/ModuleServiceProvider.php
app/Modules/ModuleStateResolver.php
app/Modules/ModuleStateRepository.php
app/Modules/FileModuleStateRepository.php
app/Providers/AppServiceProvider.php
Modules/<Module>/config/module.php hoặc Modules/<Module>/Config/module.php
```

#### Runtime state

Trạng thái bật/tắt thực tế của các Module được lưu mặc định tại:

```text
storage/app/system/module-state.json
```

Ví dụ cấu trúc dữ liệu tổng quát:

```json
{
  "version": 1,
  "modules": {
    "Example": true
  }
}
```

#### Resolve trạng thái Module

Luồng resolve chuẩn:

```text
Đọc manifest Module
    ↓
Xác định type / required / depends
    ↓
Gọi ModuleStateResolver để resolve enabled thực tế
    ↓
Sắp xếp boot order: shell → support → domain
    ↓
Validate dependency graph
    ↓
Chỉ giữ các Module enabled
    ↓
Register ServiceProvider + config + routes + resources + helpers
+ migrations + Livewire + Blade components + console commands
```

Khi một Module đang `disabled`, `ModuleServiceProvider` không chạy `registerModule()` cho Module đó, vì vậy các provider/routes/resources/helpers/migrations/components/commands của Module không được autoload bởi cơ chế Module chung.

Dependency phải được kiểm tra trước khi autoload: Module enabled không được phụ thuộc vào Module bị thiếu, bị disabled, tự phụ thuộc hoặc tạo circular dependency.

#### Thao tác chuẩn

```text
BẬT Module
→ ghi runtime override <Module>=true qua cơ chế quản trị/runtime repository

TẮT Module
→ ghi runtime override <Module>=false qua cơ chế quản trị/runtime repository

RESET Module về mặc định
→ xóa riêng runtime override của <Module>
→ ModuleStateResolver quay về manifest default
```

Trước và sau thao tác bật/tắt phải kiểm tra dependency, trạng thái resolve thực tế và tác động autoload. Sau runtime operation phải kiểm tra `git status`; runtime state không được làm working tree tracked source bị dirty.

## 12. Docker / production

Khi người dùng nói `production`, `Docker production`, `debug production`, `test production`, `lỗi chỉ xảy ra production` hoặc tham chiếu `/opt/projects/<project>`, bắt buộc đọc và áp dụng:

```text
docs/PRODUCTION_DOCKER_RUNTIME.md
```

Production diagnosis mặc định là **READ-ONLY**. Ưu tiên bootstrap bằng:

```bash
./production-debug.sh
```

Sau khi người vận hành thay đổi production `.env`, helper chuẩn để apply runtime environment là:

```bash
./run-updated-env.sh
```

Production `.env` canonical nằm tại `/opt/projects/<project>/.env`; không coi `.env` nằm trong Docker image là source of truth và không dump secret trong diagnostic output.

Nếu feature tạo runtime file/directory:

- kiểm tra `Dockerfile`
- kiểm tra `.dockerignore`
- kiểm tra `compose.yaml` / Compose overlays applicable
- kiểm tra `docker/entrypoint.sh`
- kiểm tra volume persistence
- kiểm tra ownership của `www-data`
- phân biệt CLI/container user với PHP-FPM `www-data`
- không dùng `chmod 777`

Không thực hiện destructive/mutating production operation khi chưa xác định root cause và chưa được người dùng phê duyệt rõ ràng. Đặc biệt không dùng `migrate:fresh`, `db:wipe`, rollback, `git reset --hard`, `git clean -fd`, xóa volume hoặc sửa `.env` như bước diagnosis mặc định.

## 13. Working tree đang dirty

Nếu local có thay đổi chưa commit:

- dừng trước khi switch/merge/pull có rủi ro
- xem `git diff`
- xác định thay đổi cần giữ hay bỏ
- không dùng `reset --hard`, `clean -fd`, `restore` bừa bãi

## 14. Trước khi merge

Chỉ merge khi các gate applicable đã PASS:

- focused tests
- module regression
- impacted/cross-module regression nếu dependency hoặc shared contract liên quan
- full project regression **chỉ khi applicable theo mục 8**, không phải gate mặc định
- manual UI smoke
- Admin UI standard acceptance nếu task có Admin UI
- PWA file handoff acceptance theo `docs/PWA_EXTERNAL_FILE_HANDOFF.md` nếu task có download/open file trên PWA-capable surface
- Git clean
- `COLLABORATION_HANDOFF.md` cập nhật

Không merge nếu còn lỗi chưa giải thích.

## 15. Handoff

Trước khi tạo PR và trước merge phải cập nhật:

```text
docs/modules/<Module>/COLLABORATION_HANDOFF.md
```

Handoff phải phản ánh:

- branch
- scope
- implementation
- tests
- UI smoke
- known issues
- next step

Nếu task không thuộc một Module cụ thể và không có handoff tương ứng, ghi rõ trong PR/handoff context thay vì tạo tài liệu Module giả.
