# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Admin Layout Hub Consolidation**

Status: **IMPLEMENTED — FOCUSED VERIFICATION PASS / UI PASS / PR READY**

Branch/checkpoint: `refactor/admin-layout-hub-consolidation`

This approved slice consolidates the Admin shell presentation entry points around `/admin/layout/*` and makes the existing Admin menu manager discoverable from the Sidebar layout workspace without merging the two subsystems.

## Ownership decision

- `Modules/Admin` remains the authenticated shell.
- `/admin/layout/header` is the canonical Admin shell Header configuration entry.
- `/admin/layout/sidebar` remains the canonical Sidebar appearance/behavior configuration entry.
- `/admin/menus*` remains a separate canonical Admin navigation/menu-management subsystem with its own controller and permissions.
- Website public Header/Menu presentation remains separate and is not redesigned in this slice.

## Runtime changes

The obsolete `/admin/admin-header` route was removed together with `AdminController::adminHeader()` and its wrapper view `Modules/Admin/resources/views/pages/admin/header/index.blade.php`.

The historical `Admin\Livewire\Header` components were deliberately preserved. Their individual caller/compatibility lifecycle still requires dedicated proof; removing the legacy route is not by itself proof that every component has no dynamic/external caller.

`/admin/layout/sidebar` now exposes a **Quản lý menu Sidebar** action linking to the existing `admin.menus.index` route. The menu controller, route family, permissions and business behavior are unchanged.

## Guardrails

`tests/Feature/Admin/AdminOwnershipBoundaryContractTest.php` asserts:

- canonical Admin controller imports remain limited to the shell controllers;
- `/admin/layout/header` remains the Header entry;
- `/admin/admin-header`, `adminHeader()` and the obsolete wrapper stay absent;
- `/admin/menus*` remains canonical and permission-protected;
- the Sidebar layout workspace links to `admin.menus.index`;
- Admin API remains closed by default.

`tests/Feature/Admin/AdminWebsitePresentationOwnershipContractTest.php` now also protects the distinction between canonical Admin shell Header configuration and Website-owned public Header management without depending on the deleted Admin wrapper.

## Schema and data decision

No schema, migration, foreign-key or production-data change is authorized or included.

## Verification

Local focused verification reported PASS:

```text
AdminOwnershipBoundaryContractTest: 5 passed, 31 assertions
AdminWebsitePresentationOwnershipContractTest: 6 passed, 64 assertions
Total: 11 passed, 95 assertions
```

Route verification reported:

```text
/admin/layout*: 7 canonical Admin layout routes
/admin/menus*: 3 canonical MenuController routes
/admin/admin-header: no matching route
```

Manual UI smoke: **PASS** for `/admin/layout/header`, `/admin/layout/sidebar`, the **Quản lý menu Sidebar** navigation to `/admin/menus`, and the existing menu-management workspace.

Working tree after verification: **clean** at implementation checkpoint `651ca5e9` before this documentation closeout.

## Acceptance criteria

- canonical Admin Header entry `/admin/layout/header`: **PRESERVED**;
- legacy `/admin/admin-header` entry/method/wrapper: **REMOVED**;
- canonical Sidebar configuration `/admin/layout/sidebar`: **PRESERVED**;
- Sidebar shortcut to `admin.menus.index`: **ADDED**;
- `/admin/menus*` subsystem/controller/permissions: **PRESERVED AND SEPARATE**;
- historical `Admin\Livewire\Header` components: **PRESERVED pending caller proof**;
- Website public Header management: **UNCHANGED**;
- schema/migration/data changes: **NONE**;
- focused regression: **PASS — 11 tests / 95 assertions**;
- route verification: **PASS**;
- manual UI smoke: **PASS**.

## Remaining compatibility debt

The preserved `Admin\Livewire\Header` components and their Blade partials require a later caller-proof decision. Public Website Header/Footer legacy runtime cleanup, Flash Sale, Coupon, Affiliate/Order residue, environment/System adapters and Database quarantine remain separate scopes.

## Next phase

Open and merge this Admin Layout Hub consolidation as a focused PR. Do not start another compatibility-debt family until this branch is merged.

After merge, resume route -> controller -> view -> Livewire -> service/model caller proof for exactly one remaining legacy family before proposing another implementation scope.
