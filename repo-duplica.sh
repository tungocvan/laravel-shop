#!/usr/bin/env bash
# V2: Duplicate a GitHub repository with fresh history and authenticated HTTPS push.
# Usage: bash repo-duplica.sh <source-name> <destination-name> [--dry-run] [--public]
set -Eeuo pipefail

usage() {
  printf 'Usage: bash %s <source-name> <destination-name> [--dry-run] [--public]\n' "${0##*/}"
  printf 'Example: bash %s laravel-shop laravel-inafo --dry-run\n' "${0##*/}"
}
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }
info() { printf '[repo-duplica] %s\n' "$*"; }

[[ $# -ge 2 && $# -le 4 ]] || { usage; exit 2; }
source_name=$1
dest_name=$2
shift 2
dry_run=false
visibility=--private
for arg in "$@"; do
  case "$arg" in
    --dry-run) dry_run=true ;;
    --public) visibility=--public ;;
    *) usage; die "Unknown option: $arg" ;;
  esac
done

# Strictly disallow URL/path/option injection; only simple GitHub repo names.
valid_name='^[A-Za-z0-9][A-Za-z0-9._-]{0,99}$'
[[ "$source_name" =~ $valid_name && "$dest_name" =~ $valid_name ]] || die 'Repository names must be simple names, not paths or URLs.'
[[ "$source_name" != "$dest_name" ]] || die 'Source and destination must differ.'
for tool in git gh mktemp tar grep find; do command -v "$tool" >/dev/null 2>&1 || die "Missing dependency: $tool"; done
gh auth status >/dev/null 2>&1 || die 'Run gh auth login first.'
owner=$(gh api user --jq .login) || die 'Cannot identify authenticated GitHub user.'
[[ -n "$owner" ]] || die 'GitHub account name is empty.'
src="$owner/$source_name"
dst="$owner/$dest_name"

gh repo view "$src" --json defaultBranchRef >/dev/null || die "Cannot access source: $src"
branch=$(gh repo view "$src" --json defaultBranchRef --jq '.defaultBranchRef.name') || die 'Cannot read source default branch.'
[[ "$branch" == main ]] || die "Expected source default branch 'main'; found '$branch'."

# A failed 'gh repo view' might be a permission/network error, not a missing repo.
# Use the REST response status to distinguish 404 from other failures.
errfile=$(mktemp)
trap 'rm -f -- "$errfile"' EXIT
if gh api "repos/$dst" >/dev/null 2>"$errfile"; then
  die "Destination already exists: $dst (will not overwrite)"
elif ! grep -Eq 'HTTP 404|404: Not Found|Not Found \(HTTP 404\)' "$errfile"; then
  cat "$errfile" >&2
  die 'Cannot establish destination absence; aborting safely.'
fi

tmpdir=$(mktemp -d)
cleanup() { rm -f -- "$errfile"; rm -rf -- "$tmpdir"; }
trap cleanup EXIT
info "Source: $src ($branch); destination: $dst; visibility: ${visibility#--}; dry-run: $dry_run"
git -c credential.helper= -c credential.helper="!gh auth git-credential" clone --quiet --depth 1 --single-branch --branch "$branch" "https://github.com/$src.git" "$tmpdir/source"
source_sha=$(git -C "$tmpdir/source" rev-parse HEAD)
mkdir "$tmpdir/new"
git -C "$tmpdir/source" archive HEAD | tar -xf - -C "$tmpdir/new"

# Reject tracked credentials by filename, but preserve conventional Laravel templates.
# Templates are still reviewed as code and are NOT proof that contents are secret-free.
sensitive=$(find "$tmpdir/new" -type f \( -name '.env' -o -name '.env.*' -o -name '*.pem' -o -name '*.key' -o -name 'auth.json' -o -name 'id_rsa*' -o -name 'id_ed25519*' -o -name '*.p12' -o -name '*.pfx' \) \
  ! -name '.env.example' ! -name '.env.*.example' -print)
if [[ -n "$sensitive" ]]; then
  printf 'Potentially sensitive tracked files found; no destination created:\\n%s\\n' "$sensitive" >&2
  die 'Review source and exclusions before retrying.'
fi

git -C "$tmpdir/new" init --quiet -b main
git -C "$tmpdir/new" add -A
git -C "$tmpdir/new" diff --cached --quiet && die 'Source snapshot contains no files.'
git -C "$tmpdir/new" -c user.name="Repository Duplicator" -c user.email="repo-duplicator@users.noreply.github.com" commit --quiet -m "Initial commit"
commit_sha=$(git -C "$tmpdir/new" rev-parse HEAD)
count=$(git -C "$tmpdir/new" rev-list --count HEAD)
[[ "$count" == 1 ]] || die 'Unexpected local commit history.'
info "Source snapshot: $source_sha"
info "Fresh initial commit: $commit_sha (commit count: $count)"
info 'Only Git-tracked source files are copied. No local .env, backups or untracked XML.'

if [[ "$dry_run" == true ]]; then
  info 'DRY RUN PASS: no GitHub repository was created or pushed.'
  exit 0
fi

printf 'Type the destination name (%s) to create and push: ' "$dst"
IFS= read -r confirmation || die 'Confirmation cancelled.'
[[ "$confirmation" == "$dst" ]] || die 'Confirmation did not match. Nothing created.'

# Create first, then push over HTTPS using the active gh account (never SSH).
# No global git config or SSH keys are modified.
# A failure leaves the destination intact for explicit, reviewed recovery.
info "Creating $dst..."
gh repo create "$dst" "$visibility" || die "Repository creation failed. Check $dst before retrying."
git -C "$tmpdir/new" remote add origin "https://github.com/$dst.git"
info "Pushing initial commit via authenticated HTTPS..."
git -C "$tmpdir/new" -c credential.helper= -c credential.helper="!gh auth git-credential" push -u origin main ||
  die "Push failed; $dst may be empty. Do not rerun or delete it. Inspect GitHub and recover with a reviewed procedure."

remote_sha=$(git -C "$tmpdir/new" -c credential.helper= -c credential.helper="!gh auth git-credential" ls-remote origin refs/heads/main | cut -f1)
[[ "$remote_sha" == "$commit_sha" ]] || die 'Remote main SHA mismatch. Inspect destination manually.'
remote_count=$(gh api "repos/$dst/commits?per_page=2" --jq 'length') || die 'Cannot verify destination commit count.'
[[ "$remote_count" == 1 ]] || die "Destination history check failed: expected 1 commit, found $remote_count."
info "SUCCESS: https://github.com/$dst (main, one fresh commit)."
