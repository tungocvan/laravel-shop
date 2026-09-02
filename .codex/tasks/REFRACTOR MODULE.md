# Task: /refactor-module <ModuleName>
# REFRACTOR MODULE

Áp dụng đầy đủ:

- `docs/GITHUB_COLLABORATION_WORKFLOW.md`
- các workflow/standard liên quan đến **Refactor Module** nếu repository có quy định;
- `.codex/standards/ADMIN_UI_STANDARD.md` đối với phần Admin UI.

## Chế độ

**Refactor Module**

## 1. Nguyên tắc làm việc

### Phân tích trước, triển khai sau

Trước khi sửa code:

1. Đọc tài liệu workflow, handoff, module contract và các tài liệu liên quan hiện có.
2. Phân tích **toàn diện Module trong một lần**, không chia nhỏ thành nhiều vòng hỏi đáp nếu không cần thiết.
3. Kiểm tra:
   - architecture / ownership;
   - routes;
   - controllers;
   - Livewire/components;
   - models;
   - services/actions;
   - policies/permissions/authorization;
   - migrations/schema ownership;
   - imports/exports;
   - seeders;
   - tests;
   - views/UI/UX;
   - pagination;
   - duplicate/dead/legacy code;
   - dependency và ảnh hưởng tới module khác.
4. Phân loại những phần quan trọng theo:
   - `KEEP`
   - `REFACTOR`
   - `REHOME`
   - `DEPRECATE`
   - `DELETE`
   - `QUARANTINE`
   - `DEFER`
5. Đề xuất **một kế hoạch triển khai tổng thể** để tôi APPROVE.

Không tạo branch hoặc sửa code trước khi tôi APPROVE kế hoạch.

---

## 2. Quy tắc APPROVE

Khi bạn yêu cầu tôi APPROVE kế hoạch và tôi trả lời:

> **oki tôi đồng ý với đề xuất của bạn !**

thì xem đó là **ủy quyền triển khai toàn bộ kế hoạch đã trình bày**.

Sau đó:

- không hỏi lại các xác nhận kỹ thuật thông thường;
- tự đưa ra quyết định kỹ thuật trong phạm vi kế hoạch đã duyệt;
- tự sửa lỗi phát sinh trong phạm vi Module;
- tự cập nhật tests/docs/handoff cần thiết;
- tiếp tục cho đến checkpoint cần tôi test UI hoặc review PR.

Chỉ dừng và hỏi lại khi phát hiện vấn đề **ngoài phạm vi đã APPROVE** hoặc có rủi ro lớn, gồm:

- thay đổi schema/database/migration có ảnh hưởng dữ liệu;
- thay đổi authorization/permission contract;
- xóa hoặc làm mất dữ liệu;
- xóa chức năng đang được sử dụng;
- thay đổi public contract/API quan trọng;
- thay đổi ownership giữa các Module;
- ảnh hưởng đáng kể đến Module khác;
- phát hiện yêu cầu mới làm thay đổi đáng kể kế hoạch đã APPROVE.

Nếu phải hỏi tôi, hãy:

1. giải thích ngắn gọn vấn đề;
2. đưa ra phương án bạn đề xuất;
3. nêu rủi ro;
4. cung cấp **một câu trả lời/prompt ngắn có thể copy-paste** để tôi APPROVE nhanh.

---

## 3. Branch / PR / MR

Ưu tiên **một consolidated branch + một PR/MR** cho toàn bộ đợt Refactor Module.

Không chia thành nhiều PR/MR nhỏ nếu không có lý do kỹ thuật hoặc rủi ro rõ ràng.

Mục tiêu là tránh việc tôi phải:

- pull nhiều lần;
- chạy test nhiều lần;
- test UI nhiều lần;
- merge nhiều PR cho cùng một đợt refactor.

Nếu trong quá trình thực hiện có nhiều workstream nội bộ, hãy xử lý chúng trên cùng branch và gom lại thành một delivery cuối.

Trước PR/merge phải cập nhật:

`docs/modules/<Module>/COLLABORATION_HANDOFF.md`

---

## 4. Architecture / cleanup

Không chỉ sửa lỗi hiện tại. Hãy đánh giá Module như một đợt **Refactor Module hoàn chỉnh**.

Đặc biệt tìm:

- duplicate components;
- duplicate services/actions;
- duplicate queries;
- dead code;
- legacy code;
- unreachable code;
- controller/service quá lớn;
- sai ownership;
- coupling không cần thiết;
- hard-coded configuration;
- pagination/filter/export logic bị lặp;
- UI component trùng chức năng;
- code có thể đơn giản hóa mà không làm thay đổi contract.

Không tự ý xóa phần chưa chứng minh được là an toàn.

Nếu chưa đủ caller/reachability proof thì đánh dấu `QUARANTINE` hoặc `DEFER`, không DELETE.

---

## 5. UI / UX

Rà soát toàn bộ UI thuộc Module và áp dụng Admin UI Standard hiện hành.

Đặc biệt kiểm tra:

- Input;
- Select;
- textarea;
- date/time controls;
- checkbox;
- focus state;
- validation/error state;
- loading/disabled state;
- button/action consistency;
- responsive mobile/tablet/desktop;
- empty state;
- table/list/card;
- filter;
- pagination.

### Pagination

Không hard-code page size nếu Module đã có configuration phù hợp.

Nếu phù hợp với Module, ưu tiên:

`10 / 25 / 50 / 100`

Việc đổi page size phải reset pagination và giữ filter/query phù hợp.

Chỉ hiển thị pagination khi thực sự cần.

---

## 6. Export

Chuẩn hóa hành vi export:

### Có checkbox được chọn

Export **chỉ các record đã chọn**, nhưng vẫn phải áp dụng authorization scope phía server.

Client gửi ID không được phép bypass authorization.

### Không có checkbox được chọn

Export **toàn bộ record thuộc filter/scope hiện tại mà user được phép truy cập**, không chỉ dữ liệu của trang pagination đang hiển thị.

Phải giữ nguyên các security boundary hiện có như:

- authorization;
- permission;
- ownership;
- private storage;
- expiry;
- download re-check;
- max-row limits;
- audit/idempotency nếu Module đang sử dụng.

Áp dụng thống nhất cho CSV/XLSX và các format tương ứng nếu Module hỗ trợ.

---

## 7. Local demo / Seeder

Đây là **môi trường Local Development**.

Hãy bảo đảm Module có dữ liệu demo đủ để kiểm thử thực tế.

Seeder demo nên:

- chỉ chạy an toàn trong `local/testing` nếu phù hợp;
- deterministic;
- idempotent hoặc có khả năng chạy lại an toàn;
- không ảnh hưởng production;
- không yêu cầu migration chỉ để tạo demo data.

Dữ liệu phải đủ để kiểm tra:

- nhiều trạng thái;
- nhiều loại record;
- filter;
- search;
- pagination;
- permission/authorization nếu phù hợp;
- export;
- empty/non-empty states;
- các workflow chính của Module.

Số lượng record nên **lớn hơn ít nhất một trang pagination mặc định** để có thể test pagination thật.

---

## 8. Testing

Không chạy full-project regression mặc định.

Ưu tiên:

1. focused tests cho phần vừa thay đổi;
2. toàn bộ test của Module;
3. test Module liên quan trực tiếp nếu thay đổi có tác động;
4. routes;
5. Pint/code style;
6. `git diff --check`;
7. frontend build nếu có thay đổi UI/assets.

Nếu test fail trong phạm vi kế hoạch đã APPROVE:

**tự phân tích → sửa → chạy lại**, không hỏi tôi từng lỗi thông thường.

Chỉ báo cho tôi khi:

- cần manual UI test;
- có blocker/risk ngoài scope;
- hoặc đã tới PR checkpoint.

---

## 9. Manual UI checkpoint

Khi automated tests đã PASS, đưa cho tôi **một checklist UI ngắn gọn**, tập trung vào những màn hình thực sự cần kiểm tra.

Không yêu cầu tôi test lại những phần không bị ảnh hưởng nếu không cần thiết.

Tôi có thể trả lời đơn giản:

> **UI PASS**

Sau khi nhận UI PASS, tiếp tục hoàn thiện handoff/PR mà không hỏi thêm xác nhận kỹ thuật thông thường.

---

## 10. PR checkpoint

Trước khi yêu cầu tôi review/merge, phải bảo đảm:

- implementation hoàn tất;
- focused tests PASS;
- Module regression PASS;
- Pint/style PASS;
- routes PASS nếu liên quan;
- build PASS nếu liên quan;
- diff check PASS;
- manual UI PASS nếu có UI;
- `COLLABORATION_HANDOFF.md` đã cập nhật;
- không còn working change ngoài dự kiến.

Sau đó cung cấp **một PR duy nhất** để tôi review và merge thủ công.

Không tự merge PR.

---

## 11. Sau khi merge

Khi tôi trả lời:

> **đã merge**

hãy:

1. xác nhận PR thực sự đã merge vào `main`;
2. thực hiện closeout theo `docs/GITHUB_COLLABORATION_WORKFLOW.md`;
3. cập nhật handoff nếu workflow yêu cầu;
4. nếu cần docs-only closeout PR thì tạo một PR closeout duy nhất;
5. sau closeout, xác nhận Module ở trạng thái:

**MERGED / CLOSED OUT**

và đưa lệnh ngắn để tôi đồng bộ local `main`.

---

## Mục tiêu cuối cùng

Thực hiện Refactor Module theo hướng:

**phân tích toàn diện một lần → tôi APPROVE một lần → bạn chủ động triển khai → automated tests → tôi UI test một lần → một PR implementation → closeout.**

Ưu tiên giảm tối đa các vòng:

**hỏi lại → xác nhận → pull → test → merge**

không cần thiết, nhưng vẫn phải dừng lại đối với các thay đổi có rủi ro lớn hoặc vượt phạm vi đã APPROVE.