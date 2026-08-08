# Administrative

Tài liệu kỹ thuật cho `Modules/Administrative`.

## Tài liệu

- `ANALYSIS.md`: kiến trúc, luồng xử lý, điểm tốt và rủi ro.
- `INFORMATION.md`: nghiệp vụ, trạng thái, dữ liệu và quy tắc vận hành.

## Kiến trúc

```text
Route -> Controller -> Blade/Livewire -> Service -> Model -> Database
```

## Workflow

```text
Pending
├── Approved
├── Rejected
└── Need Supplement -> Resubmit -> Pending
```

## Định hướng

Giữ kiến trúc hiện tại. Các bước tiếp theo nên tập trung vào automated test, kiểm tra security, performance khi dữ liệu lớn và chuẩn hóa documentation.
