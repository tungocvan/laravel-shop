#!/usr/bin/env bash
# Read-only prompt generator: never executes Git, Docker or Artisan.
set -Eeuo pipefail
ROOT="$(cd -- "$(dirname -- "$0")" && pwd)"
usage() { echo 'Usage: bash menu-prompt.sh [--project PROJECT] [--mode quick|debug|audit] [--goal TEXT]'; }
validate_project() {
  [[ "$1" =~ ^[a-zA-Z0-9][a-zA-Z0-9_-]*$ ]] || { echo 'Invalid project name' >&2; exit 2; }
}
validate_mode() {
  case "$1" in quick|debug|audit) ;; *) echo 'Invalid mode' >&2; exit 2 ;; esac
}
print_prompt() {
  local project="$1" mode="$2" goal="$3" label
  validate_project "$project"
  validate_mode "$mode"
  case "$mode" in
    quick) label='PROMPT NHANH' ;;
    debug) label='DEBUG CHUYÊN SÂU' ;;
    audit) label='AUDIT / REFACTOR' ;;
  esac
  printf '\n========================================================\nCOPY PROMPT — %s — %s\n========================================================\n\n' "$label" "$project"
  cat <<EOF
Bạn là AI technical lead hỗ trợ tôi với repository tungocvan/laravel-shop.
Project: $project
Production host path tham chiếu (KHÔNG tự thao tác): /opt/projects/$project
Chế độ: $label

YÊU CẦU CỦA TÔI:
EOF
  if [[ -n "$goal" ]]; then
    printf '%s\n' "$goal"
  else
    echo 'Tôi chưa mô tả mục tiêu. Trước tiên chỉ hỏi tôi một câu: "Bạn muốn tôi xử lý vấn đề hoặc chức năng gì?" Sau khi tôi trả lời, tiếp tục thực hiện theo quy tắc bên dưới.'
  fi
  cat <<EOF

QUY TẮC THỰC HIỆN:
- Chỉ xử lý đúng mục tiêu tôi mô tả. Không tự mở rộng phạm vi.
- Chủ động đọc source, routes, tests và tài liệu hiện hành trên GitHub nếu có công cụ/quyền truy cập. Không giả vờ đã đọc nếu chưa truy cập được. Khi không truy cập được, yêu cầu đúng tài liệu hoặc output thiết yếu.
- Ưu tiên docs/GITHUB_COLLABORATION_WORKFLOW.md. Với Production đọc thêm docs/PRODUCTION_DEBUG_PROMPT.md, docs/projects/$project.md và production guardrails; profile chỉ là snapshot lịch sử.
- Tự tìm module, route, dependency, tests liên quan từ source khi có thể. Không bắt tôi điền biểu mẫu.
- Bắt đầu bằng audit READ-ONLY. Nếu cần tôi chạy lệnh, ghi máy Admin/Production, HOST/CONTAINER, READ-ONLY/MUTATING; một checkpoint cần thiết mỗi lần, chờ output.
- Không sửa trực tiếp Production, không tiết lộ secrets, không xóa dữ liệu/volume, không tự chạy thao tác nguy hiểm; phân biệt host Git, image, container và mounts.
- Nếu cần sửa source: tuân thủ gate phê duyệt hiện hành; chỉ làm trên feature branch sau khi được phép; thêm targeted tests, xác nhận UI/HTTP thực tế, tạo PR và KHÔNG merge nếu chưa được tôi duyệt.
- Nếu yêu cầu không liên quan Production runtime, không tự yêu cầu Docker/log/deploy.
EOF
  case "$mode" in
    quick)
      echo '- PROMPT NHANH: Tập trung trực tiếp yêu cầu, audit tối thiểu phần source/dependency liên quan, đưa checkpoint hoặc kết quả ngắn gọn.' ;;
    debug)
      echo '- DEBUG CHUYÊN SÂU: Xác định symptom và root cause bằng chứng; chỉ kiểm tra log/runtime nếu cần. Không coi snapshot là trạng thái hiện tại, không đoán lỗi, không tự restart/reload/migrate.' ;;
    audit)
      echo '- AUDIT/REFACTOR: Audit architecture, ownership, callers, routes, tests, UI; đưa bằng chứng, plan, phạm vi và test gates trước implementation. Major Module Refactor phải tuân thủ workflow chuyên biệt và chờ duyệt kiến trúc. Admin/PWA UI phải đọc standard tương ứng.' ;;
  esac
  cat <<'EOF'
- Không tự nhận TEST/UI PASS nếu chưa xác minh. Chỉ hỏi thông tin thiết yếu; nếu công cụ không cho phép thực hiện, nói rõ giới hạn.
- Hãy bắt đầu xử lý mục tiêu ngay.

=== END OF PROMPT ===
EOF
  if [[ ! -f "$ROOT/docs/projects/$project.md" ]]; then
    printf 'NOTE (outside prompt): PROFILE_NOT_FOUND docs/projects/%s.md\n' "$project"
  fi
}
project='' mode='' goal=''
if (($#)); then
  while (($#)); do
    case "$1" in
      --help|-h) usage; exit 0 ;;
      --project|--mode|--goal)
        [[ $# -ge 2 ]] || { usage >&2; exit 2; }
        case "$1" in
          --project) project="$2" ;;
          --mode) mode="$2" ;;
          --goal) goal="$2" ;;
        esac
        shift 2 ;;
      *) usage >&2; exit 2 ;;
    esac
  done
  [[ -n "$project" ]] || { usage >&2; exit 2; }
  print_prompt "$project" "${mode:-quick}" "$goal"
  exit 0
fi
if [[ ! -t 0 ]]; then
  echo 'Interactive menu requires a terminal. Use --project tnv [--mode quick|debug|audit].' >&2
  exit 2
fi
while true; do
  printf '\n=== AI PROMPT MENU ===\n1) Tạo prompt theo project\n0) Thoát\nChọn [1/0]: '
  IFS= read -r choice || exit 0
  case "$choice" in
    0) exit 0 ;;
    1)
      printf '\nProject: 1) tnv  2) ntd  3) Khác  0) Quay lại\nChọn project [1/2/3/0]: '
      IFS= read -r selection || exit 0
      case "$selection" in
        1) project=tnv ;;
        2) project=ntd ;;
        3) printf 'Nhập tên project: '; IFS= read -r project || exit 0; validate_project "$project" ;;
        0) continue ;;
        *) echo 'Lựa chọn không hợp lệ.'; continue ;;
      esac
      printf '\nChế độ: 1) Prompt nhanh  2) Debug chuyên sâu  3) Audit / Refactor  0) Quay lại\nChọn [1/2/3/0]: '
      IFS= read -r selection || exit 0
      case "$selection" in
        1) mode=quick ;;
        2) mode=debug ;;
        3) mode=audit ;;
        0) continue ;;
        *) echo 'Lựa chọn không hợp lệ.'; continue ;;
      esac
      printf '\nMô tả yêu cầu (Enter để mô tả sau trong ChatGPT): '
      IFS= read -r goal || exit 0
      print_prompt "$project" "$mode" "$goal"
      ;;
    *) echo 'Lựa chọn không hợp lệ.' ;;
  esac
done
