#!/usr/bin/env bash
# Interactive GitHub repository manager. Invoked by repo-duplica.sh without arguments.
set -Eeuo pipefail

ROOT_DIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)
say() { printf '\n[repo-duplica V3] %s\n' "$*"; }
pause_menu() { printf '\nPress Enter to return to menu... '; IFS= read -r _ || true; }
valid_repo() { [[ "$1" =~ ^[A-Za-z0-9][A-Za-z0-9._-]{0,99}$ ]]; }
valid_owner() { [[ "$1" =~ ^[A-Za-z0-9][A-Za-z0-9-]{0,38}$ ]]; }
valid_full_repo() {
  [[ "$1" == */* ]] && [[ "${1#*/}" != */* ]] || return 1
  local o="${1%%/*}" n="${1#*/}"
  valid_owner "$o" && valid_repo "$n"
}
clean_repo_input() {
  local value="$1"
  value="${value%
require_auth() {
  command -v gh >/dev/null 2>&1 || { say 'Missing GitHub CLI (gh).'; return 1; }
  gh auth status >/dev/null 2>&1 || { say 'Not authenticated. Choose menu 4 to log in.'; return 1; }
}
normalize_repo() {
  local input="$1" owner
  if [[ "$input" == */* ]]; then
    valid_full_repo "$input" || return 1
    printf '%s\n' "$input"
  else
    valid_repo "$input" || return 1
    owner=$(current_owner) || return 1
    valid_owner "$owner" || return 1
    printf '%s/%s\n' "$owner" "$input"
  fi
}
check_connection() {
  require_auth || return 0
  say 'GitHub authentication status:'
  gh auth status || true
  say 'Active account:'
  gh api user --jq '{login, id}' || true
  say 'Git protocol configured in gh:'
  gh config get git_protocol --host github.com || true
  say 'This menu uses authenticated HTTPS for repository duplication.'
}
duplicate_repo() {
  require_auth || return 0
  local src dst src_owner dst_owner src_name dst_name dry visibility answer
  printf 'Source repository (name or owner/name): '
  IFS= read -r src || return 0
  src=$(normalize_repo "$(clean_repo_input "$src")") || { say 'Invalid source repository.'; return 0; }
  printf 'Destination repository (name or owner/name): '
  IFS= read -r dst || return 0
  dst=$(normalize_repo "$(clean_repo_input "$dst")") || { say 'Invalid destination repository.'; return 0; }
  src_owner="${src%%/*}"; dst_owner="${dst%%/*}"
  src_name="${src#*/}"; dst_name="${dst#*/}"
  if [[ "$src_owner" != "$dst_owner" ]]; then
    say 'V3 duplication currently supports source and destination in the same authenticated account only.'
    return 0
  fi
  if [[ "$src_owner" != "$(current_owner)" ]]; then
    say 'Source and destination must belong to the active GitHub account.'
    return 0
  fi
  printf 'Mode [1=Dry Run (default), 2=Create repository]: '
  IFS= read -r answer || return 0
  case "$answer" in
    ''|1) dry=true ;;
    2) dry=false ;;
    *) say 'Invalid mode.'; return 0 ;;
  esac
  printf 'Visibility [1=Private (default), 2=Public]: '
  IFS= read -r visibility || return 0
  local args=("$src_name" "$dst_name")
  case "$visibility" in
    ''|1) ;;
    2) args+=(--public) ;;
    *) say 'Invalid visibility.'; return 0 ;;
  esac
  [[ "$dry" == true ]] && args+=(--dry-run)
  bash "$ROOT_DIR/repo-duplica.sh" "${args[@]}" || say 'Duplication failed. Inspect output; do not retry blindly.'
}
delete_repo() {
  require_auth || return 0
  local target details permission confirm final
  say 'DANGER: This permanently deletes a GitHub repository, including its code and history.'
  printf 'Repository to DELETE (exact owner/name): '
  IFS= read -r target || return 0
  target=$(clean_repo_input "$target")
  valid_full_repo "$target" || { say 'Invalid repository name; owner/name required.'; return 0; }
  # Require explicit target owner; never delete based on a guessed account.
  details=$(gh api "repos/$target" --jq '{full_name,private,default_branch,archived}' 2>/dev/null) ||
    { say 'Cannot inspect repository; no deletion performed.'; return 0; }
  say "Repository details: $details"
  permission=$(gh api "repos/$target" --jq '.permissions.admin // false' 2>/dev/null) || {
    say 'Cannot verify admin permission; no deletion performed.'; return 0;
  }
  [[ "$permission" == true ]] || { say 'Admin permission is required; no deletion performed.'; return 0; }
  printf 'Type the EXACT repository name (%s) to continue: ' "$target"
  IFS= read -r confirm || return 0
  [[ "$confirm" == "$target" ]] || { say 'Confirmation mismatch; cancelled.'; return 0; }
  printf 'Type DELETE PERMANENTLY to execute irreversible GitHub deletion: '
  IFS= read -r final || return 0
  [[ "$final" == 'DELETE PERMANENTLY' ]] || { say 'Final confirmation mismatch; cancelled.'; return 0; }
  # No -F, no --silent, no automatic scope escalation. API returns an error if
  # the active token lacks delete_repo scope or GitHub policy forbids deletion.
  if gh api -X DELETE "repos/$target" >/dev/null; then
    say "GitHub deletion request succeeded for $target."
  else
    say 'GitHub deletion failed. Check delete_repo permission and repository policies.'
  fi
}
configure_account() {
  command -v gh >/dev/null 2>&1 || { say 'Install GitHub CLI (gh) first.'; return 0; }
  printf '\n1) View accounts\n2) Login (interactive)\n3) Switch account\n4) View git protocol\n0) Back\nSelect: '
  local option
  IFS= read -r option || return 0
  case "$option" in
    1) gh auth status || true ;;
    2) gh auth login --hostname github.com ;;
    3) gh auth switch --hostname github.com ;;
    4) gh config get git_protocol --host github.com ;;
    0) ;;
    *) say 'Invalid option.' ;;
  esac
}
main() {
  while true; do
    printf '\n========== REPO DUPLICA V3 ==========\n'
    printf '1) Check GitHub connection\n2) Duplicate repository\n3) DELETE GitHub repository\n4) Configure GitHub account\n0) Exit\nSelect: '
    local choice
    IFS= read -r choice || return 0
    case "$choice" in
      1) check_connection; pause_menu ;;
      2) duplicate_repo; pause_menu ;;
      3) delete_repo; pause_menu ;;
      4) configure_account; pause_menu ;;
      0) return 0 ;;
      *) say 'Invalid option.' ;;
    esac
  done
}
main "$@"
\r'}"
  value="${value#"${value%%[![:space:]]*}"}"
  value="${value%"${value##*[![:space:]]}"}"
  printf '%s' "$value"
}
current_owner() { gh api user --jq .login; }
require_auth() {
  command -v gh >/dev/null 2>&1 || { say 'Missing GitHub CLI (gh).'; return 1; }
  gh auth status >/dev/null 2>&1 || { say 'Not authenticated. Choose menu 4 to log in.'; return 1; }
}
normalize_repo() {
  local input="$1" owner
  if [[ "$input" == */* ]]; then
    valid_full_repo "$input" || return 1
    printf '%s\n' "$input"
  else
    valid_repo "$input" || return 1
    owner=$(current_owner) || return 1
    valid_owner "$owner" || return 1
    printf '%s/%s\n' "$owner" "$input"
  fi
}
check_connection() {
  require_auth || return 0
  say 'GitHub authentication status:'
  gh auth status || true
  say 'Active account:'
  gh api user --jq '{login, id}' || true
  say 'Git protocol configured in gh:'
  gh config get git_protocol --host github.com || true
  say 'This menu uses authenticated HTTPS for repository duplication.'
}
duplicate_repo() {
  require_auth || return 0
  local src dst src_owner dst_owner src_name dst_name dry visibility answer
  printf 'Source repository (name or owner/name): '
  IFS= read -r src || return 0
  src=$(normalize_repo "$src") || { say 'Invalid source repository.'; return 0; }
  printf 'Destination repository (name or owner/name): '
  IFS= read -r dst || return 0
  dst=$(normalize_repo "$dst") || { say 'Invalid destination repository.'; return 0; }
  src_owner="${src%%/*}"; dst_owner="${dst%%/*}"
  src_name="${src#*/}"; dst_name="${dst#*/}"
  if [[ "$src_owner" != "$dst_owner" ]]; then
    say 'V3 duplication currently supports source and destination in the same authenticated account only.'
    return 0
  fi
  if [[ "$src_owner" != "$(current_owner)" ]]; then
    say 'Source and destination must belong to the active GitHub account.'
    return 0
  fi
  printf 'Mode [1=Dry Run (default), 2=Create repository]: '
  IFS= read -r answer || return 0
  case "$answer" in
    ''|1) dry=true ;;
    2) dry=false ;;
    *) say 'Invalid mode.'; return 0 ;;
  esac
  printf 'Visibility [1=Private (default), 2=Public]: '
  IFS= read -r visibility || return 0
  local args=("$src_name" "$dst_name")
  case "$visibility" in
    ''|1) ;;
    2) args+=(--public) ;;
    *) say 'Invalid visibility.'; return 0 ;;
  esac
  [[ "$dry" == true ]] && args+=(--dry-run)
  bash "$ROOT_DIR/repo-duplica.sh" "${args[@]}" || say 'Duplication failed. Inspect output; do not retry blindly.'
}
delete_repo() {
  require_auth || return 0
  local target details permission confirm final
  say 'DANGER: This permanently deletes a GitHub repository, including its code and history.'
  printf 'Repository to DELETE (exact owner/name): '
  IFS= read -r target || return 0
  valid_full_repo "$target" || { say 'Invalid repository name; owner/name required.'; return 0; }
  # Require explicit target owner; never delete based on a guessed account.
  details=$(gh api "repos/$target" --jq '{full_name,private,default_branch,archived}' 2>/dev/null) ||
    { say 'Cannot inspect repository; no deletion performed.'; return 0; }
  say "Repository details: $details"
  permission=$(gh api "repos/$target" --jq '.permissions.admin // false' 2>/dev/null) || {
    say 'Cannot verify admin permission; no deletion performed.'; return 0;
  }
  [[ "$permission" == true ]] || { say 'Admin permission is required; no deletion performed.'; return 0; }
  printf 'Type the EXACT repository name (%s) to continue: ' "$target"
  IFS= read -r confirm || return 0
  [[ "$confirm" == "$target" ]] || { say 'Confirmation mismatch; cancelled.'; return 0; }
  printf 'Type DELETE PERMANENTLY to execute irreversible GitHub deletion: '
  IFS= read -r final || return 0
  [[ "$final" == 'DELETE PERMANENTLY' ]] || { say 'Final confirmation mismatch; cancelled.'; return 0; }
  # No -F, no --silent, no automatic scope escalation. API returns an error if
  # the active token lacks delete_repo scope or GitHub policy forbids deletion.
  if gh api -X DELETE "repos/$target" >/dev/null; then
    say "GitHub deletion request succeeded for $target."
  else
    say 'GitHub deletion failed. Check delete_repo permission and repository policies.'
  fi
}
configure_account() {
  command -v gh >/dev/null 2>&1 || { say 'Install GitHub CLI (gh) first.'; return 0; }
  printf '\n1) View accounts\n2) Login (interactive)\n3) Switch account\n4) View git protocol\n0) Back\nSelect: '
  local option
  IFS= read -r option || return 0
  case "$option" in
    1) gh auth status || true ;;
    2) gh auth login --hostname github.com ;;
    3) gh auth switch --hostname github.com ;;
    4) gh config get git_protocol --host github.com ;;
    0) ;;
    *) say 'Invalid option.' ;;
  esac
}
main() {
  while true; do
    printf '\n========== REPO DUPLICA V3 ==========\n'
    printf '1) Check GitHub connection\n2) Duplicate repository\n3) DELETE GitHub repository\n4) Configure GitHub account\n0) Exit\nSelect: '
    local choice
    IFS= read -r choice || return 0
    case "$choice" in
      1) check_connection; pause_menu ;;
      2) duplicate_repo; pause_menu ;;
      3) delete_repo; pause_menu ;;
      4) configure_account; pause_menu ;;
      0) return 0 ;;
      *) say 'Invalid option.' ;;
    esac
  done
}
main "$@"
