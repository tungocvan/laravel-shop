# Inventory — Collaboration Handoff

## Current status

- Module: `Modules\Inventory`
- Approved inputs: `IDEA.md`, `REQUIREMENTS.md`, `CREATE_PLAN.md`
- Implementation branch: `feat/inventory-batch-a-core-ledger`
- Branch base: `docs/inventory-module-idea` because the three approved Inventory planning documents are still ahead of `main` on that branch.
- Batch A — Foundation + Persistence + Core Ledger: **IMPLEMENTED / awaiting local PHPUnit + Pint verification**.
- Do not split Batch A into additional MRs unless verification exposes a real blocker.

## Bootstrap contract implemented

- Type: `domain`.
- Default runtime state: disabled.
- Hard dependency: `Shared` only.
- `Invoices`, `Product`, `Partner`, `Pharma`: optional integration boundaries only; none is declared in `depends`.
- No module-specific service provider; root `Modules\ModuleServiceProvider` conventions are used.
- `config/module.php`, migrations, models, services and `routes/web.php` are convention-discoverable.
- `routes/web.php` intentionally exposes no endpoint in Batch A; Admin routes/UI belong to Batch B.

## Batch A persistence

Owned tables:

```text
inventory_warehouses
inventory_items
inventory_item_aliases
inventory_lots
inventory_receipts
inventory_receipt_lines
inventory_issues
inventory_issue_lines
inventory_transfers
inventory_transfer_lines
inventory_stocktakes
inventory_stocktake_lines
inventory_movements
inventory_balances
```

Key persistence decisions:

- quantity: `decimal(20,6)`;
- conversion factor: fixed decimal;
- movement ledger: append-only canonical historical truth;
- balance: projection keyed by deterministic non-null `dimension_key`;
- movement idempotency: deterministic unique `movement_key`;
- lot identity: deterministic item-scoped identity key including expiry dimension when applicable;
- Product/Pharma/Partner references are indexed integration IDs, not cross-module foreign keys;
- no `Product.quantity` dual write;
- no inventory valuation layer/FIFO/weighted-average tables;
- no invoice PDF/file ownership or PDF parsing.

## Core invariants implemented

### Stock ledger and projection

`StockPostingService` is the single mutation path for confirmed stock movements in Batch A.

It:

- creates/reuses balance dimensions through a DB unique key;
- locks balance rows in deterministic `dimension_key` order;
- uses deterministic movement keys;
- recognizes already-posted movement keys as idempotent retries;
- rejects any mutation that would produce negative stock;
- updates balance only after the canonical movement insert succeeds.

`StockMovement` rejects update/delete through the domain model. Corrections use compensating movements through `MovementReversalService` and preserve `reversal_of_movement_id`.

### Confirm transaction boundaries

Receipt, issue, transfer and stocktake confirmation:

- require their dedicated backend permission;
- lock the document row;
- lock document lines;
- run inside one DB transaction with deadlock retry count `3`;
- record confirming actor/time;
- return an already confirmed document as an idempotent retry;
- leave no partial posting when an invariant fails.

Transfer v1 creates source `OUT` and destination `IN` in one posting batch and one transaction.

Stocktake locks all relevant balance dimensions in deterministic order, rejects duplicate stock dimensions inside one stocktake, captures the posting-basis system quantity, and posts only non-zero variance.

### Quantity / UOM

`DecimalQuantity` performs exact fixed-scale decimal arithmetic with strings rather than floats and supports the full `decimal(20,6)` schema precision.

All core document confirms enforce:

- positive receipt/issue/transfer quantity;
- non-negative stocktake count;
- canonical item base UOM;
- `allow_fractional_quantity` policy.

### Lot / HSD

- lot number is required for lot-tracked items;
- expiry is required for expiry-tracked items;
- lot number is not globally unique;
- deterministic lot identity is item-scoped and expiry-aware;
- issue/transfer/stocktake validate that selected lot belongs to the selected item.

### Confirmed document immutability

Confirmed receipt/issue/transfer/stocktake headers reject direct update/delete through their models.

Their line models also reject direct update/delete when the parent is confirmed.

Draft cancellation and stocktake `DRAFT -> COUNTED` are explicit service transitions. Confirmed documents have no direct cancel path.

## Explicitly not implemented in Batch A

- Batch B Admin dashboard/workspaces/CRUD UI;
- Batch C Invoices normalized DTO/inbox/draft-receipt integration;
- invoice PDF parsing or reads from `storage/app/invoices/pdf`;
- Product quantity compatibility projection;
- automatic Product/Partner/Pharma master mutation;
- FEFO UI suggestion;
- Excel audit/export UI/service;
- FIFO or weighted-average valuation/accounting;
- two-step in-transit transfer.

## Tests added

```text
tests/Feature/Inventory/InventoryModuleBootstrapTest.php
tests/Feature/Inventory/InventoryPersistenceContractTest.php
tests/Feature/Inventory/InventoryConcurrencyContractTest.php
tests/Feature/Inventory/InventoryQuantityAndLotContractTest.php
tests/Feature/Inventory/InventoryCorePostingTest.php
```

Coverage includes:

- bootstrap/dependency boundary;
- schema/ledger key contract;
- no optional-module hard dependency/cross-module FK;
- no invoice PDF/Product.quantity ownership leak;
- fixed decimal arithmetic;
- lot/HSD requirements;
- sorted locking/unique-key concurrency contract;
- receipt retry idempotency;
- negative-stock rollback;
- atomic transfer OUT+IN;
- stocktake variance-only posting;
- confirmed document/line immutability;
- immutable movement + idempotent compensating reversal.

## Verification status

The GitHub branch contains the implementation and tests, but the assistant execution environment cannot resolve GitHub from its CLI/container, and this branch currently has no automatic GitHub Actions run/status. Therefore PHPUnit and Pint results must not be represented as passed yet.

Required local gate:

### Test 1 — Inventory

```bash
php artisan test tests/Feature/Inventory
```

If Test 1 fails, stop and send the full output before running Test 2.

### Test 2 — directly impacted module foundation

```bash
php artisan test tests/Feature/System/ModuleCatalogRegistryTest.php tests/Feature/System/ModuleGraphValidatorTest.php tests/Feature/System/ModuleBootstrapRuntimeStateTest.php
```

No full regression is required for Batch A because no shared/root module infrastructure was modified.

### Pint

```bash
./vendor/bin/pint Modules/Inventory tests/Feature/Inventory
```

Pint result is currently **PENDING LOCAL VERIFICATION**.

## Known debt / next approved scopes

- Real multi-process contention should be smoke-tested on the deployment DB after the deterministic locking/idempotency tests pass; SQLite PHPUnit verifies contract and retry behavior but is not a substitute for production-engine contention testing.
- `MovementReversalService` currently provides full compensating reversal per original movement. Rich correction-document UX/reason taxonomy belongs to a later operational surface.
- FEFO suggestion, exports/audit workspace, Admin UI and invoice integration remain in their approved later batches.
- Product/Pharma/Partner mapping adapters remain optional boundaries and must not become hard dependencies.

## Stop gate

After local Inventory + foundation tests and Pint pass, report the output and keep this branch for Batch A review/PR preparation. Do not start Batch B or Batch C automatically without the next explicit user instruction.
