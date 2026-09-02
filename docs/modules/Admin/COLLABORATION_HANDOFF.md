# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Menu Management Workspace — UI/UX Refactor & Menu Taxonomy Cleanup**

Status: **PR READY — UI PASS / IMPORT PASS / FOCUSED GATE PASS**

Branch: `refactor/admin-menu-management-workspace`

This checkpoint improves the canonical Admin-owned `/admin/menus` management surface without moving menu persistence or business-domain ownership out of `Modules/Admin`.

## Scope completed

### Menu management workspace

The menu management page was reorganized into a workspace-oriented Admin UI:

- primary actions emphasize route scanning and menu creation;
- import/export/template/restore/layout-design actions are grouped as secondary tools;
- menu statistics are presented compactly;
- search and status filtering are integrated with the tree workspace;
- expand/collapse controls support large menu trees;
- hierarchical rows expose name, URL, permission and status more clearly;
- section rows have stronger visual hierarchy;
- row secondary actions are consolidated while status remains directly actionable;
- selected-menu bulk actions remain available in a sticky toolbar;
- drag/drop hierarchy and ordering remain supported.

Existing menu-management contracts are preserved, including authorization, route scanning, import/export, restore snapshot, bulk delete/status/permission operations, selection behavior and drag/drop ordering.

Export semantics remain unchanged: when menu rows are selected, export uses the selected set; otherwise export follows the current menu filters.

### Menu form

The create/edit menu form was reorganized for clearer desktop and responsive usage:

- main information and display/settings are separated into clearer regions;
- Link versus Section/Group intent is explicit;
- icon selection provides practical presets while retaining manual icon input;
- icon/sidebar preview is available while editing;
- save/cancel actions are easier to reach.

Business persistence remains delegated through the existing Menu service boundary.

## Menu taxonomy/import acceptance

The exported menu catalog was reviewed for inconsistent titles, generic icons and duplicate top-level module sections.

The optimized import keeps stable menu keys, URLs, permissions and active state while improving display names, supported semantic icons, hierarchy and ordering.

Canonical consolidation targets are:

- `muasamcong.dashboard` under `mua-sam-cong`;
- `admin.invoices.dashboard` under `hoa-don-dien-tu`;
- `admin.system.dashboard` under `cong-cu-he-thong`;
- `admin.pharma.dashboard` under `duoc-pham`.

The redundant generated top-level sections associated with those four domains remain data cleanup candidates only after their dashboards are confirmed under the canonical parents. Spreadsheet `update_or_create` intentionally does not delete omitted records.

The import-ready workbook uses the exact flat import contract:

`key, parent_key, name, url, icon, can, is_active, sort_order`

User-confirmed acceptance:

- `/admin/menus` refactored UI: **PASS**
- menu create/edit UI: **PASS**
- optimized Excel menu import: **PASS**

## Icon compatibility finding

The current Admin `x-icon` component supports a limited explicit icon vocabulary. Menu taxonomy optimization therefore uses icons known to render through the existing component instead of introducing unsupported icon names that would silently fall back to the default icon.

A broader semantic icon library remains a separate enhancement and is not required for this checkpoint.

## Safety / cleanup rule

Do not delete a duplicate module section merely because an optimized spreadsheet omits it when using `update_or_create`.

Before removing any legacy duplicate section, confirm:

1. its canonical dashboard child now points to the intended canonical parent;
2. the legacy section has no remaining required children;
3. sidebar navigation remains reachable and authorized;
4. deletion does not remove a still-required subtree.

Historical duplicate section keys to verify separately:

- `module-muasamcong`
- `module-invoices`
- `module-system`
- `module-pharma`

Their physical data deletion is not required for this UI refactor PR and should not expand this branch beyond proven-safe menu-management changes.

## Verification closeout

### Focused Menu gate — PASS

`php artisan test tests/Feature/Admin/MenuLivewireRefactorContractTest.php`

- **8 passed**
- **56 assertions**

Pint on the changed Menu contract test: **PASS**.

Vite production build: **PASS**.

Working tree after focused verification: **clean**.

### Admin regression

`php artisan test tests/Feature/Admin`

Result:

- **213 passed**
- **3 failed**
- **1868 assertions**

The remaining three failures are pre-existing/out-of-scope ownership-contract drift and do not exercise the Menu workspace refactor:

1. `AdminAffiliateOwnershipContractTest` expects a deprecated Admin affiliate compatibility service to extend `Modules\Website\Services\AdminAffiliateService`, while that canonical Website service is currently absent.
2. `AdminAffiliateOwnershipContractTest` directly reads the same absent `Modules/Website/Services/AdminAffiliateService.php` during commission-list contract verification.
3. `AdminWebsitePresentationOwnershipContractTest` expects the Auth Google controller source to reference `Modules\Auth\Services\AuthService`; the current Auth/Google ownership contract no longer matches that assertion.

These failures belong to Website/Auth ownership cleanup and must not be corrected opportunistically in the Menu UI branch.

No Admin regression failure remains attributable to `MenuLivewireRefactorContractTest` or the changed Menu views.

## Acceptance criteria

- Admin menu UI hierarchy/progressive disclosure: **COMPLETE / UI PASS**
- menu create/edit form UX: **COMPLETE / UI PASS**
- drag/drop and hierarchy behavior preserved: **UI PASS**
- selected-versus-filtered export semantics preserved: **PRESERVED**
- import-ready optimized menu taxonomy: **IMPORT PASS**
- focused Menu contract test: **PASS — 8 tests / 56 assertions**
- Pint focused gate: **PASS**
- Vite build: **PASS**
- Admin regression attributable to Menu scope: **PASS**
- unrelated Website/Auth ownership baseline: **3 known failures / OUT OF SCOPE**
- branch working tree after verification: **CLEAN**

## Next checkpoint

Open one consolidated pull request from `refactor/admin-menu-management-workspace` to `main`. The PR must explicitly disclose the three unrelated Website/Auth ownership baseline failures above. After merge, any physical deletion of the four duplicate menu-section records should be treated as a separate data cleanup only after tree/caller proof.