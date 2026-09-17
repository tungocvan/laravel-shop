# Module Snapshot Schema Doctor

## Mục tiêu

Schema Doctor giải thích vì sao Module Snapshot bị `SCHEMA KHÔNG TƯƠNG THÍCH` thay vì chỉ hiển thị `BLOCKED` chung chung. Doctor là read-only: không chạy DDL và không có force restore.

## Verdict

- `SAFE`: fingerprint hiện tại tương thích; Restore Module có thể mở theo validation hiện hữu.
- `REVIEW`: có bằng chứng khác biệt nhưng cần đối chiếu migration/version trước khi sửa.
- `BLOCKED`: có khác biệt có nguy cơ mất dữ liệu; không auto repair.

Snapshot 1.0 cũ chỉ có fingerprint tổng được đánh dấu `REVIEW` khi không tương thích vì không đủ metadata để chỉ ra chính xác column/index khác nhau.

## Detailed schema manifest

`ModuleSchemaDoctorService::currentSchema()` tạo representation deterministic gồm columns, indexes và foreign keys. `ModuleSnapshotManifestService` và `ModuleSnapshotSchemaManifest` là boundary additive để snapshot thế hệ kế tiếp có thể ghi `schema_manifest` mà vẫn giữ `format_version=1.0` và restore semantics hiện tại.

Lưu ý ở checkpoint này: `ModuleSnapshotService::create()` chưa được đổi để ghi field mới. Vì vậy snapshot hiện hữu và snapshot tạo trước integration tiếp theo vẫn được Doctor xử lý như legacy fingerprint-only. Đây là chủ ý để không thay đổi đường backup/restore production trước khi focused tests xác nhận boundary mới.

## Safety rules

Doctor không được tự chạy destructive DDL, không bỏ qua checksum/ownership/schema validation và không tự mở Restore khi verdict khác `SAFE`. Việc repair phải đi qua migration/version-control và được đánh giá riêng; Safety Snapshot vẫn là gate bắt buộc của restore hiện hữu.
