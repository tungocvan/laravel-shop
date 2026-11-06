# GDT POS — Debug runbook và lịch sử khắc phục (06/11/2026)

> Phạm vi: Modules/Invoices, nguồn hóa đơn máy tính tiền (POS) của GDT, môi trường DELL development/test. Tài liệu này lưu lại **bằng chứng thực tế**, các lỗi từng gặp và thứ tự chẩn đoán để tránh lặp lại nhiều ngày debug. Không phải quy trình thao tác production. Với production, đọc `docs/PRODUCTION_DEBUG_PROMPT.md` phiên bản main mới nhất trước.

## 1. Kết quả đã xác nhận

- Ngày 06/11/2026, POS purchase preview 01/10–31/10 trả 6 hóa đơn; một hóa đơn có ngày lập 30/09.
- Đồng bộ canonical thực tế trên DELL cho 01/10–30/10 lúc 07:56: API trả đủ 6/6; sau dedupe 6; trong khoảng ngày lập 5, ngoài khoảng 1; tạo mới 2, cập nhật 0, không đổi 4; RAW header 6; detail tái sử dụng 4, tải mới 2, lỗi 0; xuất Excel thành công.
- Read-only MySQL sau sync: tháng 09/2026 **111 canonical / 111 RAW GDT** (không đổi); tháng 10/2026 tăng **117 → 119 canonical / 119 RAW GDT**. Hai hóa đơn `1/C26MDC/898` và `1/C26MSS/88182` đều có đúng một match theo symbol+number trong dữ liệu purchase.
- Đồng bộ lặp cùng khoảng 01/10–30/10 lúc 08:05: **created=0, updated=0, unchanged=6**, RAW header 6, detail reused 6, fetched 0, failed 0. Idempotency PASS theo log UI.
- Người dùng báo focused test và Invoices module regression **PASS hai tầng** sau commit `dd9b9452b`. Không suy rộng thành toàn bộ hệ thống hoặc production PASS.
- POS preview **không ghi canonical**. POS canonical **chạy trực tiếp, không queue**; mỗi lần preview/sync cần CAPTCHA mới. Không lưu câu trả lời CAPTCHA vào cache.

## 2. Dấu hiệu lỗi gốc và bằng chứng phân biệt

**Triệu chứng ban đầu:** GDT POS purchase trả `HTTP 200` nhưng body là HTML (trang có title phân loại ERROR), không phải JSON hóa đơn. HTTP 200 **không đồng nghĩa** dữ liệu hợp lệ. UI cần fail-closed: không ghi canonical khi phản hồi HTML, pagination thiếu hoặc dữ liệu không đủ.

**Điều tra đã làm:**
1. Phân biệt source `regular` và `pos`; không dùng kết quả regular để kết luận POS thành công.
2. Kiểm tra phiên cookie: ghi nhận khôi phục 4/4 cookie ở một lần thử, nhưng vẫn nhận HTML ERROR. Do đó **không thể khẳng định cookie là nguyên nhân duy nhất**.
3. Thêm fingerprint HTML an toàn (nhãn cố định về HTML/title/server/error category, kích thước body, meta refresh), không log HTML raw, cookie, token, CAPTCHA, header value. Phân loại `UNCLASSIFIED`/`NO_RECOGNIZED_GATEWAY_HEADER` chỉ là heuristic, **không chứng minh nguyên nhân upstream**.
4. So sánh request browser trả JSON với Laravel HTTP fake contract. Phát hiện **mismatch cụ thể**: POS `End-Point` bị header generic ghi thành `/`, thay vì đường dẫn trang POS `/tra-cuu/tra-cuu-hoa-don`. Đây là lỗi request contract có thể kiểm chứng; không tự khẳng định nó là nguyên nhân duy nhất của HTML upstream.
5. Thử `PendingRequest::withoutHeader()` dẫn đến **BadMethodCallException** vì Laravel HTTP PendingRequest không hỗ trợ phương thức này; focused tests 16 fail/9 pass. Sửa bằng **POS-only PendingRequest factory (`clientForPos`)** không mang các header generic `Referer`, `Action`, `End-Point`; áp dụng header POS đúng ở request. Regular client giữ nguyên.
6. Duy trì cookie/session GDT qua CAPTCHA, login và POS search; không bypass CAPTCHA. Browser-vs-Laravel HTTP/2, cURL/Guzzle, cookie eligibility được audit; HTTP fake không chứng minh actual on-wire delivery.

**Kết luận được phép rút ra:** Có mismatch header POS thực sự, đã sửa bằng tách client POS; sau các bản sửa, user xác nhận API POS JSON 6/6, canonical và idempotency thành công trên DELL. **Không có bằng chứng độc lập xác nhận lý do nội bộ phía GDT đã trả HTML ERROR trước đó.**

## 3. Luồng đúng để debug / kiểm chứng

1. Xác nhận branch, HEAD, main, tracked/untracked; đọc workflow và handoff. Audit read-only, không đụng `.env`, dữ liệu production hoặc untracked.
2. Kiểm tra GDT auth/cookie session và CAPTCHA: POS CAPTCHA phải được cấp mới; kiểm tra cookie restore count **không log giá trị**.
3. Xác minh request POS: endpoint, query `sort`, `size`, `search`, và POS-specific `Referer`/`Action`/`End-Point`. Không dùng generic client header override; không gọi phương thức `withoutHeader()` không tồn tại.
4. Phân loại response: HTTP status, Content-Type, JSON shape, pagination/total count; nếu HTML 200 hoặc dữ liệu thiếu, **fail-closed trước DB**. Không coi HTTP 200 là success về nghiệp vụ.
5. Preview trước, xác nhận số hóa đơn, ngày lập và source; preview không ghi DB.
6. Canonical sync trực tiếp bằng CAPTCHA mới; xác nhận log `created/updated/unchanged`, RAW header/detail và Excel. GDT có thể trả hóa đơn **ngày lập ngoài khoảng truy vấn**; lưu theo `issued_date` thật, không ép sang tháng truy vấn.
7. Kiểm tra DB read-only theo `issued_date` và `provider=gdt`, đối chiếu từng identity đầy đủ (invoice_type, symbol, number, issued_date, tax_code), tránh suy đoán từ symbol+number đơn lẻ.
8. Sync lại cùng khoảng với CAPTCHA mới; yêu cầu created=0, updated=0, unchanged=N và detail reused=N. Chỉ xác nhận UI PASS theo kết quả thực tế người dùng.
9. Chạy focused → Invoices regression → impacted regression khi applicable; stop ngay khi FAIL. Kiểm tra git clean, handoff, PR review; merge/deploy chỉ sau phê duyệt.

## 4. Regression tests và các bẫy đã gặp

- `tests/Feature/Invoices/GdtPosRequestTransportContractTest.php`: POS request headers/query, cookie eligibility, HTML fail-closed, preview read-only, incomplete pagination, canonical replay/RAW identity, cross-month issued-date.
- Các test liên quan trong `tests/Feature/Invoices/GdtPos*Test.php`, `GdtCanonicalPosSyncContractTest.php`, `GdtSource*Test.php`.
- Test cross-month ban đầu FAIL tại assertion tháng 09 vì SQLite `issued_date` được cast thành date có phần thời gian; so sánh `whereBetween('issued_date', ['2026-09-01','2026-09-30'])` có thể bỏ sót `2026-09-30 00:00:00`. Commit `dd9b9452b` chuyển sang `whereDate()`; user xác nhận focused + module regression PASS.
- SQLite in-memory test chỉ chứng minh fixture/path trong test, không thay thế kiểm chứng MySQL và UI; test SKIPPED không phải PASS.
- Một invoice POS có ngày lập 30/09 trong kết quả query 01/10–30/10 là dữ liệu API thực tế, không tự động coi là lỗi date parsing.

## 5. Mốc tham chiếu và lưu ý bảo mật

- Feature branch: `feat/invoices-gdt-independent-sources-20261105`.
- Checkpoint test: `dd9b9452bc1c228e92f4fe82b53debaa5e800888`.
- Handoff: `docs/modules/Invoices/COLLABORATION_HANDOFF.md`.
- Không ghi log hoặc commit: token, cookie, CAPTCHA answer, `.env`, raw HTML phản hồi nhạy cảm, thông tin người mua/bán từ hóa đơn.
- Khi lỗi tái phát, **bắt đầu từ contract request/response và session**, không đoán gateway, không sửa hàng loạt header, không disable SSL, không bỏ CAPTCHA, không queue POS để né lỗi.
- Các gate còn lại trước merge: review toàn bộ diff PR, GitHub checks, impacted regression nếu có và manual UI acceptance tương ứng; không có production PASS.
