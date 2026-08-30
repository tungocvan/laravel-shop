# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Admin Layout Hub Consolidation**

Status: **IMPLEMENTED — AWAITING LOCAL VERIFICATION / UI SMOKE**

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

`tests/Feature/Admin/AdminOwnershipBoundaryContractTest.php` now asserts:

- canonical Admin controller imports remain limited to the shell controllers;
- `/admin/layout/header` remains the Header entry;
- `/admin/admin-header`, `adminHeader()` and the obsolete wrapper stay absent;
- `/admin/menus*` remains canonical and permission-protected;
- the Sidebar layout workspace links to `admin.menus.index`;
- Admin API remains closed by default.

## Schema and data decision

No schema, migration, foreign-key or production-data change is authorized or included.

## Verification required

Run focused Admin ownership tests and route checks, then manually verify:

- `/admin/layout/header` loads and saves as before;
- `/admin/layout/sidebar` loads and shows **Quản lý menu Sidebar**;
- the button opens `/admin/menus`;
- `/admin/menus` management remains operational;
- `/admin/admin-header` is no longer an active Admin route.

## Remaining compatibility debt

The preserved `Admin\Livewire\Header` components and their Blade partials require a later caller-proof decision. Public Website Header/Footer legacy runtime cleanup, Flash Sale, Coupon, Affiliate/Order residue, environment/System adapters and Database quarantine remain separate scopes.

## Next phase

Do not start another compatibility-debt family until this branch passes focused verification/UI smoke and is merged. After merge, resume route -> controller -> view -> Livewire -> service/model caller proof for exactly one remaining legacy family.
