#!/bin/bash

# load env
source .env.sh

REPO_NAME="$1"

if [ -z "$REPO_NAME" ]; then
  echo "❌ Missing repo name"
  exit 1
fi

read -p "⚠️ Delete repo '$REPO_NAME' ? (y/N): " confirm
[[ "$confirm" != "y" ]] && exit 0

# trim tránh lỗi ký tự ẩn
GITHUB_USERNAME=$(echo "$GITHUB_USERNAME" | tr -d '\r\n ')
REPO_NAME=$(echo "$REPO_NAME" | tr -d '\r\n ')

URL="https://api.github.com/repos/$GITHUB_USERNAME/$REPO_NAME"

echo "DEBUG URL: [$URL]"

STATUS=$(curl -s -o response.txt -w "%{http_code}" -X DELETE \
  -H "Authorization: Bearer $GITHUB_TOKEN" \
  -H "Accept: application/vnd.github+json" \
  "$URL")

if [ "$STATUS" -eq 204 ]; then
  echo "✅ Deleted repo: $REPO_NAME"
else
  echo "❌ Failed (HTTP $STATUS)"
  cat response.txt
fi