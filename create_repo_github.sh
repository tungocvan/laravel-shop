#!/bin/bash

source .env.sh

REPO_NAME="$1"

if [ -z "$REPO_NAME" ]; then
  echo "❌ Usage: ./create-repo.sh <repo-name>"
  exit 1
fi

echo "🚀 Creating repo: $REPO_NAME"

# tạo repo trên GitHub
CREATE_STATUS=$(curl -s -o response.json -w "%{http_code}" \
  -X POST https://api.github.com/user/repos \
  -H "Authorization: token $GITHUB_TOKEN" \
  -d "{\"name\":\"$REPO_NAME\",\"private\":false}")

if [ "$CREATE_STATUS" -ne 201 ]; then
  echo "❌ Failed to create repo (HTTP $CREATE_STATUS)"
  cat response.json
  exit 1
fi

echo "✅ Repo created"

# init git nếu chưa có
if [ ! -d ".git" ]; then
  git init
fi

git add .
git commit -m "init commit"

# add remote
git remote remove origin 2>/dev/null
git remote add origin git@github.com:$GITHUB_USERNAME/$REPO_NAME.git

# push
git branch -M main
git push -u origin main

echo "🎉 Done: https://github.com/$GITHUB_USERNAME/$REPO_NAME"
