#!/bin/bash

# =========================
# Validate input
# =========================
if [ -z "$1" ]; then
  echo "❌ Usage: ./export_struct.sh Modules/User"
  exit 1
fi

TARGET_DIR="$1"

if [ ! -d "$TARGET_DIR" ]; then
  echo "❌ Directory not found: $TARGET_DIR"
  exit 1
fi

# =========================
# Config
# =========================
OUTPUT_FILE="$TARGET_DIR/structure.md"

IGNORE_DIRS=("node_modules" ".git" "vendor" "storage")

# =========================
# Helpers
# =========================
should_ignore() {
  local name="$1"
  for ignore in "${IGNORE_DIRS[@]}"; do
    if [[ "$name" == "$ignore" ]]; then
      return 0
    fi
  done
  return 1
}

get_size() {
  local path="$1"
  du -sh "$path" 2>/dev/null | cut -f1
}

get_lines() {
  local file="$1"

  # chỉ đếm file text (tránh binary)
  if file "$file" | grep -q text; then
    wc -l < "$file" 2>/dev/null
  else
    echo "-"
  fi
}

# =========================
# Tree generator
# =========================
generate_tree() {
  local dir="$1"
  local prefix="$2"

  local items=()

  while IFS= read -r -d '' item; do
    items+=("$(basename "$item")")
  done < <(find "$dir" -mindepth 1 -maxdepth 1 -print0 2>/dev/null | sort -z)

  local total=${#items[@]}
  local index=0

  for item in "${items[@]}"; do
    if should_ignore "$item"; then
      continue
    fi

    index=$((index+1))
    local path="$dir/$item"

    # prefix
    if [ $index -eq $total ]; then
      connector="└──"
      new_prefix="${prefix}    "
    else
      connector="├──"
      new_prefix="${prefix}│   "
    fi

    if [ -d "$path" ]; then
      size=$(get_size "$path")
      echo "${prefix}${connector} 📁 $item ($size)" >> "$OUTPUT_FILE"

      generate_tree "$path" "$new_prefix"
    else
      size=$(get_size "$path")
      lines=$(get_lines "$path")

      printf "%s%s 📄 %s (%s, %s lines)\n" \
        "$prefix" "$connector" "$item" "$size" "$lines" >> "$OUTPUT_FILE"
    fi
  done
}

# =========================
# Summary
# =========================
generate_summary() {
  echo "" >> "$OUTPUT_FILE"
  echo "## 📊 Summary" >> "$OUTPUT_FILE"

  total_files=$(find "$TARGET_DIR" -type f \
    ! -path "*/node_modules/*" \
    ! -path "*/.git/*" \
    ! -path "*/vendor/*" \
    ! -path "*/storage/*" | wc -l)

  total_dirs=$(find "$TARGET_DIR" -type d \
    ! -path "*/node_modules/*" \
    ! -path "*/.git/*" \
    ! -path "*/vendor/*" \
    ! -path "*/storage/*" | wc -l)

  total_lines=$(find "$TARGET_DIR" -type f \
    ! -path "*/node_modules/*" \
    ! -path "*/.git/*" \
    ! -path "*/vendor/*" \
    ! -path "*/storage/*" \
    -exec cat {} + 2>/dev/null | wc -l)

  total_size=$(du -sh "$TARGET_DIR" | cut -f1)

  echo "- 📁 Directories: $total_dirs" >> "$OUTPUT_FILE"
  echo "- 📄 Files: $total_files" >> "$OUTPUT_FILE"
  echo "- 🧾 Total lines: $total_lines" >> "$OUTPUT_FILE"
  echo "- 💾 Total size: $total_size" >> "$OUTPUT_FILE"
}

# =========================
# Run
# =========================
echo "# 📦 Structure: $TARGET_DIR" > "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

echo "\`\`\`" >> "$OUTPUT_FILE"
echo "$(basename "$TARGET_DIR")/" >> "$OUTPUT_FILE"

generate_tree "$TARGET_DIR" ""

echo "\`\`\`" >> "$OUTPUT_FILE"

generate_summary

# =========================
# Done
# =========================
echo "✅ Exported audit to: $OUTPUT_FILE"
