# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Slice 6: Order Legacy Ownership Cleanup**

Status: **IMPLEMENTED — AWAITING LOCAL VERIFICATION**

Branch/checkpoint: `refactor/admin-order-legacy-ownership-cleanup`

Slice 6 was explicitly approved after Slice 5 Product cleanup was merged as PR #100.

## Ownership decision

`Modules/Order` is the canonical owner of Order management behavior.

Preserved contracts:

- `/admin/orders`, `/admin/orders/{id}`, `/admin/orders/{id}/print`, and `/admin/orders/{id}/pdf` remain unchanged;
- `admin.orders.index`, `admin.orders.show`, `admin.orders.print`, and `admin.orders.pdf` remain Order-owned;
- the active controller remains `Modules\Order\Http\Controllers\OrderController`;
- Order pages mount canonical `order.orders.*` Livewire components;
- `auth:admin` remains required on the Order route group;
- browser print and PDF invoice behavior remain Order-owned;
- Order schema, migrations and table names are unchanged.

## Removed legacy Admin Order management runtime

The following proven duplicate files were removed:

- `Modules/Admin/Livewire/Orders/OrderTable.php`
- `Modules/Admin/Livewire/Orders/OrderDetail.php`
- `Modules/Admin/resources/views/livewire/orders/order-table.blade.php`
- `Modules/Admin/resources/views/livewire/orders/order-detail.blade.php`
- `Modules/Admin/resources/views/pages/orders/index.blade.php`
- `Modules/Admin/resources/views/pages/orders/show.blade.php`
- `Modules/Admin/resources/views/pages/orders/invoice.blade.php`

Canonical equivalents remain in `Modules/Order`.

## Order status-history correction

The canonical `Modules/Order/Livewire/Orders/OrderDetail.php` previously assigned the new status before capturing `$oldStatus`, so history descriptions could record the new status as both the old and new values.

Slice 6 now captures the previous status first, then mutates the model. This is a localized correctness fix and does not change the Order state model or schema.

## Affiliate cross-domain boundary deliberately retained

The similarly named `OrderDetailModal` is not part of the canonical Order management page flow. The active Admin Affiliate commission workspace already contains its own inline reconciliation/detail modal and still depends on `Modules\Admin\Services\AdminAffiliateService`.

Therefore Slice 6 deliberately preserves:

- `Modules/Admin/Livewire/Orders/OrderDetailModal.php`
- `Modules/Admin/resources/views/livewire/orders/order-detail-modal.blade.php`
- `Modules/Admin/Services/AdminAffiliateService.php`

These are classified as `DEPRECATE/MOVE candidate` for a future dedicated Affiliate ownership analysis rather than being deleted mechanically as Order duplicates.

Affiliate rank/scheme ownership also remains outside Slice 6.

## Authorization assessment

The canonical Order routes currently retain their existing `web` + `auth:admin` middleware contract. Slice 6 does not invent new Order permission names or silently alter authorization semantics.

Capability-specific Order authorization remains a follow-up security/ownership question if a canonical permission contract is later established. No authorization weakening was introduced by this cleanup.

## Guardrails

`tests/Feature/Admin/AdminOrderOwnershipCleanupContractTest.php` protects:

- canonical Order route/controller ownership;
- existing route names and URLs;
- `auth:admin` route protection;
- canonical Order Livewire page aliases;
- absence of the seven removed Admin Order management files;
- presence of canonical Order runtime and print/PDF view;
- correct old-status capture ordering before status mutation;
- preservation of Affiliate compatibility surfaces pending separate ownership proof;
- continued P0 `DatabaseService.php` quarantine.

`docs/modules/Admin/OWNERSHIP_BASELINE.md` now classifies Order management legacy ownership as `CLEANED`, while Affiliate cross-domain compatibility remains separately unresolved.

## Runtime / schema impact

Route URL/name change: **NONE**

Authentication guard change: **NONE**

Order authorization weakening: **NONE**

Order schema/table/migration change: **NONE**

Order state-machine redesign: **NONE**

Print/PDF compatibility change: **NONE**

Affiliate refactor: **NONE — FOLLOW-UP OWNERSHIP DEBT RECORDED**

P0 database administration quarantine: **UNCHANGED**

## Verification required before PR readiness

Run focused verification only; do not run full project regression.

Recommended commands:

```bash
vendor/bin/pint --test Modules/Order/Livewire/Orders/OrderDetail.php tests/Feature/Admin/AdminOrderOwnershipCleanupContractTest.php
php artisan test tests/Feature/Admin/AdminOrderOwnershipCleanupContractTest.php
php artisan test tests/Feature/Order
php artisan route:list --name=admin.orders
```

If `tests/Feature/Order` is not a valid path in this repository, run the existing Order-focused test classes instead.

If existing Admin regression tests are normally used for ownership cleanup, run only the Admin-focused suite and directly impacted Affiliate tests; do not run the full project suite.

## Manual UI smoke required

- `/admin/orders` renders successfully after legacy Admin removal;
- search and status filter work;
- pagination works;
- order detail opens;
- status update succeeds and history shows the actual old -> new transition;
- permitted Pending/Cancelled delete behavior still works;
- non-deletable status remains blocked;
- browser print works;
- PDF download works;
- unauthenticated/non-admin access remains blocked by the existing guard;
- Affiliate commission reconciliation remains functional because its retained Admin compatibility path was not changed;
- desktop/tablet layout has no serious overflow regression.

## Acceptance criteria pending verification

- canonical Order management ownership confirmed: **IMPLEMENTED**;
- seven proven Admin Order management duplicates absent: **IMPLEMENTED**;
- route names/URLs preserved: **IMPLEMENTED — VERIFY ROUTE LIST**;
- auth guard preserved: **IMPLEMENTED — VERIFY TEST/ROUTE LIST**;
- order history old/new status correction: **IMPLEMENTED — VERIFY TEST/UI**;
- print/PDF preserved: **IMPLEMENTED — VERIFY UI**;
- Affiliate compatibility preserved without redesign: **IMPLEMENTED — VERIFY FOCUSED SMOKE IF NEEDED**;
- schema/migration changes: **NONE**;
- P0 database quarantine: **UNCHANGED**;
- PR readiness: **PENDING LOCAL TEST + UI PASS**.

## Material risks still open

### P0

`Modules/Admin/Services/DatabaseService.php` remains quarantined and must stay unreachable.

### Affiliate ownership debt

Admin and Order still contain overlapping Affiliate-named models/services/runtime. That family requires its own caller, permission, schema and compatibility analysis before cleanup. Slice 6 does not authorize it.

### Remaining Admin legacy families

Post/content, customer/address, roles/staff, marketing/public-site and system/environment remain separate ownership/reachability candidates.

Production migration-ledger/table ownership remains unresolved and out of scope.

## Next phase

Do not select or implement the next Admin legacy family until Slice 6 focused automated verification and manual UI smoke are complete, the handoff is closed out, and the user explicitly authorizes the next scope.
