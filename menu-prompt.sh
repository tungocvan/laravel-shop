#!/usr/bin/env bash
# Production debug prompt selector. Read-only: no Docker, Git, Artisan or file mutation.
set -Eeuo pipefail
ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
MASTER="$ROOT/docs/PRODUCTION_DEBUG_PROMPT.md"
PROJECTS="$ROOT/docs/projects"
usage() {
  printf 'Usage: bash menu-prompt.sh [--help] [--project PROJECT]\n'
  printf 'Interactive mode: select menu 1 and a production project.\n'
}
validate_project() {
  [[ "$1" =~ ^[a-zA-Z0-9][a-zA-Z0-9_-]*$ ]] || {
    printf 'Invalid project name. Use letters, numbers, underscore or hyphen.\n' >&2
    exit 2
  }
}
print_prompt() {
  local project="$1" profile="$PROJECTS/$1.md"
  validate_project "$project"
  [[ -f "$MASTER" ]] || { printf 'Missing master prompt: %s\n' "$MASTER" >&2; exit 1; }
  printf '\n========================================================\n'
  printf 'COPY PROMPT — PRODUCTION DEBUG: %s\n' "$project"
  printf '========================================================\n\n'
  cat <<EOF
Tôi cần DEBUG PRODUCTION cho project: $project
Repository: tungocvan/laravel-shop
Production project directory: /opt/projects/$project
Canonical master prompt: docs/PRODUCTION_DEBUG_PROMPT.md
Project runtime profile: docs/projects/$project.md

YÊU CẦU BẮT BUỘC:
- Đọc toàn bộ master prompt và tuân thủ production guardrails cùng GitHub collaboration workflow.
- Đọc hồ sơ project tương ứng bên dưới. Đây là snapshot lịch sử, phải kiểm tra thời điểm và độ mới so với deploy/runtime hiện tại.
- Nếu không có profile hoặc profile lỗi thời, thông báo rõ và yêu cầu thu thập lại bằng checkpoint READ-ONLY; không tự suy đoán.
- Trước khi đưa lệnh Docker, xác định mục tiêu debug, URL/route, module và triệu chứng; audit source hiện tại trên GitHub khi có quyền truy cập.
- Phân biệt HOST Git, Docker image, container, bind mount và volume; không suy luận phiên bản code chạy thực tế từ host HEAD.
- Mỗi lần chỉ yêu cầu một checkpoint cần thiết; ghi HOST/CONTAINER và READ-ONLY/MUTATING; chờ output trước bước kế tiếp.
- Khi root cause thuộc source, đề xuất fix/refactor có giới hạn trên feature branch, targeted tests, UI/HTTP acceptance và PR; không sửa production trực tiếp hoặc merge khi chưa được tôi duyệt.
- Tài liệu trên repository đang hoạt động được ưu tiên nếu khác với bản prompt được copy.
EOF
  printf '\n========================================================\n'
  printf 'MASTER PROMPT: docs/PRODUCTION_DEBUG_PROMPT.md\n'
  printf '========================================================\n\n'
  cat "$MASTER"
  printf '\n========================================================\n'
  printf 'PROJECT PROFILE: docs/projects/%s.md\n' "$project"
  printf '========================================================\n\n'
  if [[ -f "$profile" ]]; then
    cat "$profile"
  else
    printf 'PROFILE_NOT_FOUND: docs/projects/%s.md\n' "$project"
    printf 'Do not invent runtime facts. Request a read-only snapshot before diagnosing infrastructure.\n'
  fi
  cat <<'EOF'

========================================================
MỤC TIÊU DEBUG HIỆN TẠI — ĐIỀN TRƯỚC KHI GỬI
========================================================

- Project: [đã chọn ở trên]
- Module/chức năng cần debug: [ĐIỀN]
- Route/URL: [ĐIỀN]
- Mục tiêu/hành vi mong muốn: [ĐIỀN]
- Hiện tượng/lỗi thực tế: [ĐIỀN]
- Thời điểm phát sinh: [ĐIỀN NẾU BIẾT]
- Log/stack trace/ảnh chụp: [DÁN SAU KHI ẨN SECRETS]
- Commit/deploy gần nhất: [ĐIỀN NẾU BIẾT]
- Phạm vi được phép sửa: [ĐIỀN]
- Tiêu chí TEST PASS + UI/HTTP PASS: [ĐIỀN]

Hãy bắt đầu bằng audit read-only, chỉ hỏi thêm thông tin thiết yếu.
Không tạo branch, sửa source hoặc thay đổi production trước các gate phê duyệt.
=== END OF PROMPT ===
EOF
}
if (($#)); then
  case "$1" in
    --help|-h) [[ $# -eq 1 ]] || { usage >&2; exit 2; }; usage; exit 0 ;;
    --project) [[ $# -eq 2 ]] || { usage >&2; exit 2; }; print_prompt "$2"; exit 0 ;;
    *) usage >&2; exit 2 ;;
  esac
fi
if [[ ! -t 0 ]]; then
  printf 'Interactive menu requires a terminal. Use --project tnv or --project ntd.\n' >&2
  exit 2
fi
while true; do
  printf '\n=== PRODUCTION DEBUG PROMPT MENU ===\n'
  printf '1) Prompt Debug Production (chọn project)\n'
  printf '0) Thoát\n'
  printf 'Chọn [1/0]: '
  IFS= read -r choice || exit 0
  case "$choice" in
    1)
      printf '\nProject: 1) tnv  2) ntd  3) Khác  0) Quay lại\n'
      printf 'Chọn project [1/2/3/0]: '
      IFS= read -r selection || exit 0
      case "$selection" in
        1) print_prompt tnv ;;
        2) print_prompt ntd ;;
        3)
          printf 'Nhập project (tên thư mục trong docs/projects): '
          IFS= read -r project || exit 0
          print_prompt "$project"
          ;;
        0) ;;
        *) printf 'Lựa chọn không hợp lệ.\n' ;;
      esac
      ;;
    0) exit 0 ;;
    *) printf 'Lựa chọn không hợp lệ.\n' ;;
  esac
done
