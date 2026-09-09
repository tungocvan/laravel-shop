# Inventory — Collaboration Handoff

## Current status

- Module: `Modules\Inventory`.
- Approved inputs: `IDEA.md`, `REQUIREMENTS.md`, `CREATE_PLAN.md`.
- `main` base for Batch B: `71ef46e9` (`feat(inventory): add Batch A core ledger foundation`).
- Batch A — Foundation + Persistence + Core Ledger: **MERGED / VERIFIED / PASS**.
- Batch B branch: `feat/inventory-batch-b-admin-operations`.
- Batch B — Admin Dashboard + UI/UX: **IMPLEMENTED / LOCAL VERIFICATION PENDING**.
- Keep Batch B in one MR unless review exposes a real blocker.

## Batch A contract carried forward unchanged

Batch B does not redesign or duplicate Batch A ledger behavior.

- Inventory remains a `domain` module, disabled by default, with hard dependency `Shared` only.
- `Invoices`, `Product`, `Partner`, `Pharma` remain optional integration boundaries.
- `StockPostingService` remains the canonical mutation path for confirmed stock movements.
- Receipt/issue/transfer/stocktake confirmation continues through the dedicated Batch A posting services with transaction, row-lock, idempotency and negative-stock protection.
- Confirmed documents/lines and stock movements remain immutable by their existing model/service contracts.
- No `Product.quantity` dual write was introduced.
- No invoice PDF parsing/storage access was introduced.

## Batch B implementation

### Admin route family

All Inventory Admin routes are under:

```text
/admin/inventory
```

Route names:

```text
admin.inventory.dashboard
admin.inventory.warehouses
admin.inventory.items
admin.inventory.receipts
admin.inventory.issues
admin.inventory.transfers
admin.inventory.stocktakes
admin.inventory.stock
admin.inventory.lots
admin.inventory.movements
```

Routes use `web`, `auth:admin` and the capability-specific permissions already declared by the Inventory manifest.

### Inventory Dashboard

`InventoryDashboardService` provides bounded/index-friendly operational aggregates for:

- active warehouses;
- active inventory items;
- draft receipts;
- low-stock dimensions against `reorder_level`;
- lots expiring within 90 days;
- draft/counted stocktakes;
- movements today;
- recent movements;
- actionable low-stock / expiry warnings.

Dashboard cards deep-link to the owning Inventory workspace.

Batch C invoice matching/inbox KPIs are intentionally absent until the approved Batch C integration contract exists.

### Admin operational workspaces

A class-based Livewire `Inventory.AdminWorkspace` owns UI state for:

- warehouse management;
- InventoryItem management;
- receipt drafts + confirmation;
- issue drafts + confirmation;
- transfer drafts + confirmation;
- stocktake drafts + confirmation;
- current stock browser;
- lot/HSD browser;
- immutable movement browser.

The Livewire component does not implement stock posting logic. High-risk confirmation delegates to:

```text
ReceiptPostingService
IssuePostingService
TransferPostingService
StocktakePostingService
```

### Search / filter / pagination

Production workspaces use bounded pagination only:

```text
10 / 25 / 50 / 100
```

There is no `All` page size.

Search/filter changes reset pagination. Filters are scoped by workspace, including status, warehouse, stock state, expiry state and movement type where relevant.

Inventory owns an explicit Livewire pagination view with:

- white inactive controls;
- indigo active page;
- clear disabled states;
- previous/next/goto Livewire actions preserved.

### UI/UX contract

Batch B follows `.codex/standards/ADMIN_UI_STANDARD.md`:

- canonical `Admin::layouts.master` shell;
- page Blade remains a shell for Livewire workspaces;
- visible bordered form/search controls;
- responsive tables with horizontal overflow;
- centered modal for create/edit and high-risk confirm;
- loading/disabled state during save/confirm;
- explicit empty/error states;
- direct return path to Inventory Dashboard;
- no bulk posting/confirm action.

### Admin menu integration

Inventory owns an idempotent menu-registration migration.

It inserts/restores one permission-aware `Quản lý kho` group and child links for the Batch B workspaces, then clears the existing `admin.menus` cache.

This is used instead of relying only on `AdminMenuSeeder`, because the repository seeder intentionally skips when `admin_menus` already contains data.

Rollback removes only Inventory-owned menu slugs.

## Batch B tests added

```text
tests/Feature/Inventory/InventoryAdminBatchBContractTest.php
```

Contract coverage includes:

- approved Admin route family and `auth:admin` boundary;
- no invoice PDF ownership leak;
- bounded page-size contract;
- visible input/pagination visual contract;
- confirmation delegation to Batch A posting services;
- loading/double-submit guard presence;
- permission-aware Inventory admin-menu registration.

Existing Batch A Inventory tests remain part of the focused Batch B verification pack.

## Verification gate — pending local execution

Run only the approved module/direct-impact scope.

### Test 1 — Inventory focused + module regression

```bash
php artisan test tests/Feature/Inventory
```

Expected gate: **PASS**.

### Test 2 — directly impacted Admin shell/menu contract

```bash
php artisan test tests/Feature/Admin/AdminGeneralLayoutContractTest.php
```

Expected gate: **PASS**.

### Pint

```bash
./vendor/bin/pint Modules/Inventory tests/Feature/Inventory
```

Expected gate: **PASS**.

No full-project regression is required for Batch B. No Shared, Invoices, Product, Partner, Pharma or root module infrastructure application code was changed.

## Manual UI acceptance — pending

Verify representative desktop/tablet/mobile widths for:

- `/admin/inventory` dashboard hierarchy and deep links;
- warehouse/item forms and visible borders/focus/error states;
- receipt/issue/transfer/stocktake draft workflow;
- confirmation modal and loading/disabled state;
- stock/lots/movements responsive tables;
- search/filter reset behavior;
- white inactive + indigo active pagination;
- empty/error states;
- Inventory menu/group visibility according to permissions;
- no 404/500 or important console error.

Report as `UI PASS` only after these checks.

## Explicitly deferred to approved later batches

### Batch C

- Invoices normalized Inventory V1 producer/adapter;
- integration inbox;
- invoice -> draft receipt proposal;
- product matching / alias review;
- unresolved Partner snapshots;
- optional Product/Pharma candidate references.

### Batch D

- Excel audit/export;
- selected-vs-all-filtered export semantics;
- import/template scope where approved;
- final query/index/runtime hardening and closeout.

## Stop gate

Batch B implementation is on the feature branch and must not be merged until:

1. Inventory test pack passes;
2. directly impacted Admin test passes;
3. Pint passes;
4. manual UI smoke is reported `UI PASS`;
5. final diff/status review is clean.

Do not start Batch C automatically before Batch B is accepted/merged.