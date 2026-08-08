# Administrative Module Information

## Mục đích

Quản lý thủ tục hành chính, tiếp nhận hồ sơ từ phụ huynh/người dân và hỗ trợ quản trị viên xử lý hồ sơ.

## Public

- Xem danh sách và chi tiết thủ tục.
- Tải biểu mẫu.
- Nộp hồ sơ và nhiều file đính kèm.
- Nhận mã hồ sơ và mã tra cứu.
- Tra cứu trạng thái hồ sơ.
- Bổ sung hồ sơ khi được yêu cầu.
- Nhận biên nhận PDF/email.

## Admin

- Quản lý thủ tục hành chính.
- Xem, tìm kiếm và lọc hồ sơ.
- Xem chi tiết và tải file hồ sơ.
- Phê duyệt hồ sơ.
- Từ chối kèm nhóm lý do và nội dung chi tiết.
- Yêu cầu người nộp bổ sung hồ sơ.
- Theo dõi lịch sử xử lý.

## Trạng thái

- `pending`: chờ xử lý.
- `need_supplement`: cần bổ sung.
- `approved`: đã phê duyệt.
- `rejected`: đã từ chối.

## Dữ liệu chính

- `administrative_procedures`
- `administrative_submissions`
- `administrative_files`
- `administrative_status_histories`

## Quy tắc quan trọng

- Chỉ hồ sơ `pending` được xử lý.
- Hồ sơ đã nộp không xóa vật lý.
- File hồ sơ phải nằm trong private storage.
- Từ chối phải có lý do.
- Mọi thay đổi trạng thái phải được ghi lịch sử.
