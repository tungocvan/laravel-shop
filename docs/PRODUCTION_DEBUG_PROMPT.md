# Production Debug Prompt — Known-runtime-first (TNV)

Dùng tài liệu này khi debug Production của các project. **Quy tắc dispatch theo project path:** nếu người dùng nhắc tới `/opt/projects/tnv`, `project tnv`, hoặc yêu cầu xử lý Production TNV thì **BẮT BUỘC đọc `docs/production-tnv.md` trước khi debug**, kể cả khi người dùng không nhắc tên file và chưa mô tả lỗi Docker. Không áp dụng hồ sơ TNV cho project khác (`/opt/projects/ntd`, v.v.); project khác phải dùng đúng hồ sơ của chính project đó nếu có. Không giả định mọi project dùng chung runtime. File này là prompt điều phối; thông tin đã xác minh về TNV nằm trong hồ sơ riêng, không cần khám phá lại Docker mỗi lần.

## Prompt cho chat mới

~~~text
Tôi cần debug một project production.

PROJECT DISPATCH (bắt buộc, trước bất kỳ debug command nào):
- Nếu target là /opt/projects/tnv hoặc TNV Production: đọc docs/production-tnv.md
  ĐẦU TIÊN, rồi mới đọc tài liệu module/source, debug và đưa command.
- Nếu target không phải TNV: KHÔNG dùng production-tnv.md làm baseline;
  tìm đúng hồ sơ project tương ứng, nếu chưa có thì áp dụng runbook chung.
- Nếu người dùng chưa nêu project và không thể suy ra an toàn: hỏi project path.
- Không đòi người dùng phải nhắc lại tên production-tnv.md trong mỗi yêu cầu.

KHI TARGET LÀ /opt/projects/tnv, BẮT BUỘC đọc theo thứ tự:
1. docs/production-tnv.md — hồ sơ runtime TNV đã xác minh và checkpoint có ngày
2. docs/PRODUCTION_DEBUG_PROMPT.md — workflow này
3. docs/GITHUB_COLLABORATION_WORKFLOW.md
4. Docs Module và source/tests thực tế liên quan tới route đang lỗi.

Chỉ đọc sâu các runbook khi tình huống tương ứng cần:
- docs/PRODUCTION_OPERATIONS_GUIDE.md
- docs/PRODUCTION_DOCKER_WORKFLOW_GUARDRAILS.md
- docs/PRODUCTION_DOCKER_RUNTIME.md

MỤC TIÊU: DEBUG CODE/ROUTE FIRST, KHÔNG LẶP DOCKER DISCOVERY.

A. Trước tiên phân tích URL, symptom, exception, controller, service, query, model,
   middleware/permission, dữ liệu và test tương ứng từ GitHub/source.
B. Tin dùng các FACT đã ghi trong production-tnv.md theo thời điểm xác minh;
   không yêu cầu tôi chạy lại docker inspect, compose ps, mount, PHP -i, ps, Git
   status, HTTP baseline nếu không có bằng chứng thay đổi hoặc nhu cầu mới.
C. Nếu thiếu bằng chứng để kết luận, yêu cầu đúng MỘT lệnh READ-ONLY có mục đích
   rõ ràng, ưu tiên Laravel exception/log tại thời điểm lỗi hoặc truy vấn dữ liệu
   liên quan. Chờ output rồi mới đưa checkpoint tiếp.
C1. BẮT BUỘC: mọi `php artisan ...` trên Production chạy từ HOST qua
    `./run-docker-artisan.sh "php artisan ..."` trong `/opt/projects/tnv`.
    Không chạy Artisan trực tiếp trên host hay docker exec/compose exec thay helper.
    Chỉ đề xuất lệnh Artisan đúng nhu cầu; lệnh mutate cần approval riêng.
D. Chỉ chuyển sang Docker diagnosis nếu lỗi thuộc hạ tầng, container unhealthy,
   toàn site lỗi, deployment thay đổi image/mount/Compose, hoặc phát hiện source
   và runtime không khớp. Nêu rõ lý do cần kiểm tra lại.
E. Phân biệt snapshot lịch sử với live state. Trước mọi production MUTATION,
   kiểm tra precondition liên quan, ảnh hưởng, rollback, và xin operator approval.
F. Source defect: sửa trên GitHub feature branch; focused tests + impacted
   regression + UI PASS; tạo PR; user review/merge; production chỉ fast-forward
   và deploy/reload đúng service sau approval.
G. Không bao giờ in secrets; không xóa production overlays; không reset --hard,
   clean -fd, down -v, prune volumes, db:wipe, migrate:fresh, chmod 777,
   clear cache hàng loạt hoặc restart/rebuild toàn stack để thử lỗi.
H. Nếu phát hiện thay đổi runtime mới đã xác minh, cập nhật
   docs/production-tnv.md trên branch/PR để lần sau không cần khám phá lại.

FORMAT:
Checkpoint N — [mục tiêu]
FACT: [bằng chứng đã có từ hồ sơ / source / output]
DIAGNOSIS: [chỉ kết luận có chứng cứ]
NEXT ACTION: [chỉ 1 lệnh nếu thực sự cần]
TARGET: HOST hoặc CONTAINER
MODE: READ-ONLY hoặc MUTATING

Không chạy thêm Docker commands chỉ để bổ sung hồ sơ production-tnv.md;
chỉ cập nhật từ output đã xác minh hoặc khi một incident thực sự đòi hỏi kiểm tra mới.

Nếu đã đủ bằng chứng để sửa code trên GitHub thì bắt đầu phân tích/đề xuất fix,
KHÔNG gửi Docker commands chỉ vì đang debug Production.

INCIDENT:
- Route/URL: [điền]
- Symptom/error: [điền]
- Thời điểm: [nếu biết]
- Deploy gần nhất: [nếu biết]
- Output/log đã có: [dán]
~~~

## Khi nào mới cần kiểm tra lại Docker?

| Tình huống | Bước đầu tiên |
| --- | --- |
| Một route lỗi 500, site còn hoạt động | Đọc source/exception và Laravel log đúng thời điểm |
| Logic import/export sai | Đọc service, validation, tests, dữ liệu liên quan |
| Sau pull, PHP behavior vẫn cũ | Đối chiếu source delivery + OPcache trong `production-tnv.md`; chỉ verify runtime có mục tiêu |
| Container unhealthy, site toàn bộ down | Dùng `./production-debug.sh --docker` |
| Lỗi quyền ghi file | Dùng `./production-debug.sh --permissions` khi đã khoanh vùng |
| DB connection lỗi | Dùng `./production-debug.sh --database` |
| Queue không chạy | Log đúng queue; xác minh service nếu có dấu hiệu unhealthy |

## Maintenance

`docs/production-tnv.md` là hồ sơ chuyên biệt; các file `PRODUCTION_*GUIDE/GUARDRAILS/RUNTIME` vẫn là safety contracts. Nếu có drift, xác minh tối thiểu và cập nhật hồ sơ bằng PR. Không biến snapshot thành bảo đảm runtime không đổi.
