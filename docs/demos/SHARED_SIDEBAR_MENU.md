# Shared Demo Sidebar Menu — Handoff (2026-10-28)

Branch: `feat/shared-demo-sidebar-menu-20261028`, based on `main`.

## Scope / status

Phase 1 implemented on GitHub (unverified locally):
- Reusable Blade mount and responsive JS/CSS editor for Sales Employees and Warehouse Inventory demos.
- Read-only GET plus operator-authorized POST/DELETE menu and JSON presets APIs, separated per demo.
- Existing Debt Management Menu Builder intentionally remains untouched until a compatibility adapter is extracted; **three-demo consolidation is not complete**.
- No database, Module, .env, production, or business dataset changes.

## Checkpoint

Run:
```bash
git fetch origin
git switch feat/shared-demo-sidebar-menu-20261028
git pull --ff-only
php artisan route:list --path=demo/doanh-so-nhan-vien/menu
php artisan route:list --path=demo/quan-ly-xuat-nhap-ton/menu
php artisan test tests/Feature/SharedDemoSidebarMenuTest.php
git diff --check
git status --short
```

Stop on any failure; do not mark TEST PASS without output. After focused PASS, run impacted existing demo route/dataset/menu tests. Manual UI must verify navigation, badges, settings editor, import/export, server authorization, localStorage reload, responsive mobile, and that original business CRUD/Excel/print remain intact. UI PASS must be user-confirmed.

## Remaining implementation

1. Review and fix focused test/runtime failures.
2. Extract Debt Management adapter onto the shared component while preserving existing menu storage, schema and server preset API, and preserving the current menu-builder features.
3. Run all impacted regression and UI gate for three demos.
4. Only then create/review PR; never merge/deploy without explicit approval.

Rollback: switch back to `main` (no migration or data changes). Existing server JSON files are not touched by branch creation.
