#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

EXCLUDE_DIRS='vendor|storage/uploads|\.git'
PHP_GLOB='*.php'

echo "== Refactor Requirements Audit =="
echo "Root: $ROOT_DIR"
echo

if command -v rg >/dev/null 2>&1; then
    SEARCH_TOOL="rg"
else
    SEARCH_TOOL="grep"
fi

echo "1) PHP files with inline <style> or <script>"
if [[ "$SEARCH_TOOL" == "rg" ]]; then
    rg -n --glob "$PHP_GLOB" --glob '!vendor/**' --glob '!storage/uploads/**' "<style|<script" . || true
else
    grep -RniE "<style|<script" . --include="$PHP_GLOB" --exclude-dir=vendor --exclude-dir=storage --exclude-dir=.git || true
fi
echo

echo "2) PHP files with inline style= attributes"
if [[ "$SEARCH_TOOL" == "rg" ]]; then
    rg -n --glob "$PHP_GLOB" --glob '!vendor/**' --glob '!storage/uploads/**' "style=\"|style='" . || true
else
    grep -RniE "style=\"|style='" . --include="$PHP_GLOB" --exclude-dir=vendor --exclude-dir=storage --exclude-dir=.git || true
fi
echo

echo "3) PHP files with top-level helper naming pattern (admin/home/calendar)"
if [[ "$SEARCH_TOOL" == "rg" ]]; then
    rg -n --glob "$PHP_GLOB" --glob '!vendor/**' --glob '!storage/uploads/**' "^\s*function\s+(admin|home|calendar)[A-Za-z0-9_]*\s*\(" . || true
else
    grep -RniE "^[[:space:]]*function[[:space:]]+(admin|home|calendar)[A-Za-z0-9_]*[[:space:]]*\(" . --include="$PHP_GLOB" --exclude-dir=vendor --exclude-dir=storage --exclude-dir=.git || true
fi
echo

echo "4) PHP functions missing adjacent comments (simple heuristic)"
# Prints function signatures where the previous non-empty line is not a comment.
awk '
    FNR==1 { prev="" }
    {
        line=$0
        trimmed=line
        gsub(/^[ \t]+|[ \t]+$/, "", trimmed)
        if (trimmed ~ /^function[ \t]+[A-Za-z0-9_]+\(/ || trimmed ~ /^public[ \t]+static[ \t]+function[ \t]+[A-Za-z0-9_]+\(/) {
            if (prev !~ /^\/\// && prev !~ /^\/\*/ && prev !~ /^\*/) {
                print FILENAME ":" FNR ":" trimmed
            }
        }
        if (trimmed != "") prev=trimmed
    }
' $(find . -type f -name "*.php" -not -path './vendor/*' -not -path './storage/uploads/*') || true

echo
echo "Audit complete."
