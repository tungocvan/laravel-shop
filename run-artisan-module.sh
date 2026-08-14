#!/usr/bin/env bash
set -Eeuo pipefail

# Run every Seeder class found in one Laravel module.
# Usage:
#   ./run-artisan-module.sh Role
#   ./run-artisan-module.sh Modules/Role
#   ./run-artisan-module.sh role

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

if [[ $# -lt 1 || -z "${1// }" ]]; then
    echo "Usage: ./run-artisan-module.sh <module>"
    echo "Example: ./run-artisan-module.sh Role"
    exit 64
fi

INPUT="${1%/}"
INPUT="${INPUT#Modules/}"

if [[ ! -d "Modules" ]]; then
    echo "[ERROR] Modules/ directory not found. Run this script from the project repository."
    exit 2
fi

MODULE_DIR=""
while IFS= read -r dir; do
    name="$(basename "$dir")"
    if [[ "${name,,}" == "${INPUT,,}" ]]; then
        MODULE_DIR="$dir"
        break
    fi
done < <(find Modules -mindepth 1 -maxdepth 1 -type d -print | sort)

if [[ -z "$MODULE_DIR" ]]; then
    echo "[ERROR] Module '$INPUT' not found under Modules/."
    echo "Available modules:"
    find Modules -mindepth 1 -maxdepth 1 -type d -printf '  - %f\n' | sort
    exit 3
fi

MODULE_NAME="$(basename "$MODULE_DIR")"
SEEDER_DIR=""
for candidate in "$MODULE_DIR/database/seeders" "$MODULE_DIR/Database/Seeders"; do
    if [[ -d "$candidate" ]]; then
        SEEDER_DIR="$candidate"
        break
    fi
done

if [[ -z "$SEEDER_DIR" ]]; then
    echo "[INFO] Module: $MODULE_NAME"
    echo "[INFO] No seeder directory found. Nothing to run."
    exit 0
fi

mapfile -t FILES < <(find "$SEEDER_DIR" -maxdepth 1 -type f -name '*.php' -print | sort)

if [[ ${#FILES[@]} -eq 0 ]]; then
    echo "[INFO] Module: $MODULE_NAME"
    echo "[INFO] Seeder directory exists but contains no PHP seeders."
    exit 0
fi

# Prefer leaf seeders. If a DatabaseSeeder exists, run it only when it is the
# sole seeder; otherwise running it as well may execute the same seeders twice.
LEAF_FILES=()
DATABASE_SEEDER=""
for file in "${FILES[@]}"; do
    if [[ "$(basename "$file")" == "DatabaseSeeder.php" ]]; then
        DATABASE_SEEDER="$file"
    else
        LEAF_FILES+=("$file")
    fi
done

if [[ ${#LEAF_FILES[@]} -gt 0 ]]; then
    FILES=("${LEAF_FILES[@]}")
elif [[ -n "$DATABASE_SEEDER" ]]; then
    FILES=("$DATABASE_SEEDER")
fi

extract_fqcn() {
    local file="$1"
    local namespace class

    namespace="$(sed -nE 's/^[[:space:]]*namespace[[:space:]]+([^;]+);.*/\1/p' "$file" | head -n1)"
    class="$(sed -nE 's/^[[:space:]]*(final[[:space:]]+|abstract[[:space:]]+)?class[[:space:]]+([A-Za-z_][A-Za-z0-9_]*).*/\2/p' "$file" | head -n1)"

    if [[ -z "$class" ]]; then
        return 1
    fi

    if [[ -n "$namespace" ]]; then
        printf '%s\\%s' "$namespace" "$class"
    else
        printf '%s' "$class"
    fi
}

echo "============================================================"
echo " Module Seeder Quick Runner"
echo "============================================================"
echo "Module : $MODULE_NAME"
echo "Path   : $MODULE_DIR"
echo "Seeders: ${#FILES[@]}"
echo "------------------------------------------------------------"

PASSED=0
FAILED=0
SKIPPED=0

for file in "${FILES[@]}"; do
    if ! fqcn="$(extract_fqcn "$file")"; then
        echo "[SKIP] $(basename "$file"): cannot detect class."
        ((SKIPPED+=1))
        continue
    fi

    echo
    echo "> php artisan db:seed --class=\"$fqcn\" --force"

    if php artisan db:seed --class="$fqcn" --force; then
        echo "[PASS] $fqcn"
        ((PASSED+=1))
    else
        echo "[FAIL] $fqcn"
        ((FAILED+=1))
    fi
done

echo
echo "============================================================"
echo " Result"
echo "============================================================"
echo "PASS : $PASSED"
echo "FAIL : $FAILED"
echo "SKIP : $SKIPPED"
echo "TOTAL: ${#FILES[@]}"

if [[ $FAILED -gt 0 ]]; then
    exit 1
fi

exit 0
