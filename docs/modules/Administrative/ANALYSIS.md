# Administrative Module Analysis

## Tổng quan

`Administrative` là domain module tiếp nhận và xử lý hồ sơ hành chính công khai, không yêu cầu người nộp đăng nhập.

## Luồng chính

```text
Route -> Controller -> Blade -> Livewire -> Service -> Model -> Migration -> Database
```

Nghiệp vụ chính:

```text
Thủ tục -> Nộp hồ sơ -> Pending -> Approved / Rejected / Need Supplement
                                      ^                     |
                                      |------ Resubmit ------|
```

## Thành phần

- Controllers: quản lý trang thủ tục, nộp hồ sơ, tra cứu và admin submission.
- Livewire: ProcedureForm/Table, SubmissionForm, SupplementForm, LookupForm, SubmissionTable/Detail.
- Services: ProcedureService, SubmissionService, AdministrativeFileService, LookupService, ReceiptService, PublicBrandingService.
- Models: AdministrativeProcedure, AdministrativeSubmission, AdministrativeFile, AdministrativeStatusHistory.
- Database: 7 migrations cho procedure, submission, file, history, email preference, supplement workflow và soft delete.

## Điểm tốt

- Authorization ở route và Livewire action.
- File lưu private, không expose URL trực tiếp.
- Lookup token chỉ lưu hash.
- Có rate limit, transaction, `lockForUpdate()` và version chống xử lý đồng thời.
- Có audit history cho thay đổi trạng thái.
- Hồ sơ dùng soft delete.

## Rủi ro / cải tiến

- `listForAdmin(..., 'All')` có thể load toàn bộ dữ liệu; nên giới hạn khi dataset lớn.
- Search `%keyword%` trên nhiều cột có thể chậm khi số hồ sơ tăng mạnh.
- Cần tài liệu hóa dependency `Administrative -> Account\Models\User`.
- Có thể chuẩn hóa rejection reason và relationship bằng Enum/config khi nghiệp vụ ổn định.
- Chưa xác minh automated test riêng cho module.

## Kết luận

Không cần rebuild. Giữ kiến trúc hiện tại, ưu tiên documentation, test, performance và security verification.
