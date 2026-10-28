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

## Batch 2 — three-route editor consolidation (pending local verification)

- Shared tabbed dialog now mounts on Debt Management through a compatibility bridge in the existing debt menu script; the legacy debt menu endpoint and browser key remain unchanged.
- Sales and Warehouse use the same Menu / Header / Footer / JSON Library tabs and per-demo server paths.
- Footer UI exposes only company credit and URL. Warehouse/Sales render a clickable footer link when configured; Debt retains its existing credit click handler and data contract, including the legacy `showSave` property for compatibility.
- Debt bridge skips its former editor and sidebar initialization path while the shared editor is mounted, preventing two competing editors. Shared script loads after Debt's app initializer so its defaults use initialized `App.cfg`.
- No production, database, or .env changes. UI verification for all three routes remains mandatory.

### Focused checkpoint

```bash
node --check public/demo/shared/sidebar-menu/menu-builder.js
node --check public/demo-assets/quan-ly-cong-no/js/menu-builder.js
php artisan test tests/Feature/SharedDemoSidebarMenuTest.php
```

After focused PASS, run debt menu focused tests and relevant sales/warehouse route regressions. Test the debt settings entry, preserved legacy server presets, footer links, navigation, and mobile layouts manually. Do not mark UI PASS for Debt until user confirms.
