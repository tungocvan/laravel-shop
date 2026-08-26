# Quy trình làm việc ChatGPT ↔ GitHub ↔ Local

## Mục tiêu

Tài liệu này chuẩn hóa cách làm việc giữa người dùng, ChatGPT và repository GitHub của dự án.

Repository chính:

`git@github.com:tungocvan/laravel-shop.git`

Mục tiêu là để ChatGPT trực tiếp phân tích/sửa code trên GitHub, còn người dùng chủ yếu `git pull`, chạy CLI/test/manual UI và gửi output để debug theo từng bước.

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
Module/System regression
    ↓
Full regression
    ↓
Manual UI smoke
    ↓
Git-clean verification
    ↓
Merge main
    ↓
Push main
    ↓
Delete feature branch
```

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

Test theo tầng:

1. Syntax / lint cần thiết
2. Focused tests cho phần vừa sửa
3. Module regression
4. System/shared-infrastructure regression khi liên quan
5. Full project regression trước merge

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
    "ModuleA": true,
    "ModuleB": false
  }
}
```

Quy tắc:

- Đây là runtime state, không phải tracked source và không được commit vào Git.
- Không sửa `module-state.json` thủ công. Mọi thao tác bật/tắt/reset phải đi qua cơ chế quản trị hoặc `ModuleStateRepository` của hệ thống.
- Repository runtime state dùng file lock độc quyền và ghi file tạm rồi thay thế nguyên tử; không được thay thế cơ chế này bằng thao tác ghi JSON trực tiếp.
- Không sửa `Modules/<Module>/config/module.php` chỉ để bật/tắt Module trong runtime.

#### Cách xác định trạng thái Module

`ModuleStateResolver` xác định trạng thái theo thứ tự:

1. Shell Module bắt buộc (`required`) luôn bật.
2. Nếu runtime state có giá trị cho Module thì dùng đúng `true`/`false` từ runtime state.
3. Nếu chưa có runtime state thì dùng `default_enabled` trong manifest.
4. Để tương thích manifest cũ, nếu không có `default_enabled` thì dùng `enabled`; nếu cả hai không tồn tại, fallback chung hiện tại là `true`.

Tóm tắt tổng quát:

```text
runtime <Module>=true
    → Module ENABLED

runtime <Module>=false
    → Module DISABLED

không có runtime override
    → dùng default_enabled
    → nếu thiếu default_enabled thì dùng enabled
    → nếu cả hai đều thiếu thì fallback chung = true
```

Shell Module là ngoại lệ bắt buộc: `required=true` thì luôn enabled và không được tắt bằng runtime state.

#### Cách autoload Module

`Modules/ModuleServiceProvider.php` là entry point autoload chung của toàn project. Luồng xử lý phải được hiểu như sau:

```text
Discover toàn bộ thư mục con trong Modules/
    ↓
Đọc manifest config/module.php hoặc Config/module.php nếu có
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

Nếu feature tạo runtime file/directory:

- kiểm tra `Dockerfile`
- kiểm tra `docker/entrypoint.sh`
- kiểm tra volume persistence
- kiểm tra ownership của `www-data`
- phân biệt CLI chạy bằng `root` với PHP-FPM chạy bằng `www-data`
- không dùng `chmod 777`

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
- full regression
- manual UI smoke
- Admin UI standard acceptance nếu task có Admin UI
- Git clean
- Docker/production check nếu liên quan
- docs cập nhật
- không còn debug/temp files

## 15. Quy trình merge chuẩn

```bash
git switch main
git pull --ff-only origin main
git merge --no-ff <feature-branch> -m "merge: <description>"
```

Sau merge:

- `git status`
- chạy regression cần thiết
- kiểm tra log/commit

Chỉ khi PASS mới:

```bash
git push origin main
```

Sau push xác nhận:

- `main == origin/main`
- working tree clean

Sau đó mới xóa branch:

```bash
git branch -d <feature-branch>
git push origin --delete <feature-branch>
```

## 16. Khởi động và bàn giao công việc theo Module

### 16.1 Phản hồi đầu tiên và giới hạn hành động

Khi người dùng yêu cầu áp dụng workflow này kèm tên Module, phản hồi đầu tiên phải xác nhận rằng ChatGPT **chưa sửa code hoặc thay đổi GitHub**. Trước tiên ChatGPT thực hiện kiểm tra chỉ đọc về quyền repository, branch/PR/checkpoint, Module source, tài liệu và handoff; sau đó báo dữ liệu đã xác minh trước khi tiếp tục.

Việc yêu cầu “áp dụng workflow” không tự cấp quyền sửa code, merge, xóa dữ liệu hoặc thực hiện thao tác phá hủy. Phạm vi phải được phân loại rõ là: phân tích, diagnose, review, implementation, kiểm thử/acceptance hay chuẩn bị merge.

### 16.2 Kiểm tra quyền truy cập GitHub trước tiên

Trước khi đọc handoff hoặc phân tích Module, phải xác nhận bằng thao tác chỉ đọc rằng repository được chỉ định là chính xác và quyền truy cập hiện tại đủ cho phạm vi công việc yêu cầu. Không tạo commit, branch hoặc file thử chỉ để kiểm tra quyền ghi.

Nếu quyền đã đầy đủ, chỉ cần báo ngắn gọn đúng một câu:

`Tôi đã có toàn quyền thực hiện trên kho mà bạn chỉ định trong tài liệu.`

Không cần liệt kê tài khoản, mức quyền, branch, API permission hoặc chi tiết kỹ thuật khác khi mọi thứ hợp lệ.

- Không truy cập được hoặc quyền không đủ: dừng và chỉ báo ngắn gọn vấn đề cần người dùng xử lý.
- Repository không khớp yêu cầu: dừng và yêu cầu xác nhận.

### 16.3 Xác nhận dữ liệu bootstrap

Sau kiểm tra quyền và trước khi thực hiện công việc, xác minh Module source, Module docs, handoff, branch/PR/checkpoint và working scope. Chỉ báo những dữ liệu cần thiết để người dùng xác nhận; không liệt kê chi tiết quyền truy cập đã được xác nhận ở mục 16.2.

Nếu dữ liệu không nhất quán, dừng để người dùng xác nhận thay vì tự phỏng đoán.

Nếu working scope có Admin UI, bootstrap phải ghi nhận `.codex/standards/ADMIN_UI_STANDARD.md` là tài liệu bắt buộc và phải đọc trước khi đưa ra plan hoặc sửa UI.

### 16.4 Xác minh Module và cây fallback tài liệu

Phải xác minh chính xác `Modules/<Module>` trước. Nếu không tồn tại, không tự chọn Module có tên gần giống; phải kiểm tra sai tên, chữ hoa/thường, branch thiếu source, tài liệu orphan hoặc yêu cầu tạo Module mới.

#### A. Có handoff

Nếu có `docs/modules/<Module>/COLLABORATION_HANDOFF.md`:

1. đọc toàn bộ handoff
2. đối chiếu checkpoint/branch/PR với GitHub
3. kiểm tra các file source và tests liên quan
4. tiếp tục từ “Remaining work / Next step”, không phân tích lại từ đầu

Handoff không được ghi đè bằng chứng hiện tại. Khi khác source, source/schema/config là nguồn sự thật và phải báo documentation drift.

#### B. Có thư mục docs nhưng không có handoff

Không đọc toàn bộ `.md` một cách máy móc. Trước tiên lập inventory và ưu tiên:

1. `README.md`, `REQUIREMENTS.md`
2. `ANALYSIS.md`, `INFORMATION.md` khi có
3. master spec, domain invariants và architecture contract
4. implementation completion/summary/addendum
5. acceptance, release evidence và release notes
6. runbook liên quan trực tiếp
7. phase/kế hoạch lịch sử chỉ khi nhiệm vụ hoặc source dẫn tới

Tài liệu phải được kiểm chứng với source/tests hiện tại. Đề xuất tạo handoff khi branch hoàn thành.

#### C. Có Module source nhưng chưa có thư mục docs

Đề xuất áp dụng `.codex/tasks/analyze-module.md`. Task `/analyze` chỉ phân tích, không sửa application code và chỉ được tạo/cập nhật:

```text
docs/modules/<Module>/ANALYSIS.md
docs/modules/<Module>/INFORMATION.md
docs/modules/<Module>/README.md
```

Tạo `COLLABORATION_HANDOFF.md` là batch riêng sau baseline analysis, không trộn vào contract `/analyze`.

#### D. Có docs nhưng không có Module source

Báo tài liệu có thể orphan/stale và dừng để xác nhận.

#### E. Không có cả source lẫn docs

Dừng và yêu cầu xác nhận tên Module, branch hoặc đây có phải Module mới không.

### 16.5 Thứ tự nguồn sự thật

1. Source code, schema và configuration hiện tại; với cơ chế Module phải đối chiếu thêm `Modules/ModuleServiceProvider.php`, `ModuleStateResolver` và runtime state repository.
2. Branch, PR và checkpoint thực tế trên GitHub.
3. `.codex/bootstrap/*`, `.codex/standards/*` và `ROADMAP.md`; riêng Admin UI, `.codex/standards/ADMIN_UI_STANDARD.md` là canonical UI/UX standard và phải được áp dụng cùng repository reality/shared components hiện tại.
4. Handoff đã được kiểm chứng.
5. Requirements/analysis/tài liệu Module.
6. Tài liệu lịch sử hoặc kế hoạch cũ.

### 16.6 File handoff bắt buộc khi kết thúc branch

Mỗi Module dùng một file duy nhất:

```text
docs/modules/<Module>/COLLABORATION_HANDOFF.md
```

Trước khi kết thúc feature/fix branch, cập nhật trong chính branch đó: repository/base/branch/PR/checkpoint, phạm vi hoàn thành, batch quan trọng, root cause và cách sửa, quyết định kiến trúc/phân quyền/ranh giới an toàn, migration/seeder/storage/lệnh vận hành, focused test, module regression, UI smoke, Git clean, blocker, việc còn lại và bước tiếp theo được phép.

Nếu branch có Admin UI, handoff phải ghi rõ mức tuân thủ `.codex/standards/ADMIN_UI_STANDARD.md`, shared components/patterns đã reuse và kết quả kiểm tra UI desktop/mobile applicable.

Chỉ ghi thông tin hữu ích để tiếp tục; không sao chép log dài. Handoff không thay thế requirements, runbook hoặc acceptance document.

Chỉ ghi branch `COMPLETED` khi mọi gate applicable đã PASS. Nếu chưa hoàn tất phải ghi `IN PROGRESS`. Không tự cập nhật handoff ngoài phạm vi đã được người dùng phê duyệt.
