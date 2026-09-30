## Current delivery — Pharma PWA Inventory Read Workspace

- Last updated: 2026-09-30
- Active branch: `feat/clientportal-pharma-inventory`
- Base branch: `main`
- Base checkpoint: `df7c67942c1164a0ab85ed21b68a9ee2274c870c`
- Status: **IMPLEMENTED — FOCUSED TEST PASS — CLIENTAPPS REGRESSION PASS — DESKTOP/TABLET/MOBILE UI PASS — READY FOR PR GATE**

### Objective

Expose Pharma inventory as a read-only ClientPortal/PWA workspace while keeping inventory schema, valuation rules and domain ownership in `Modules/Pharma`. Do not reuse Admin controllers, Blade, Livewire or `auth:admin`.

### Delivered capability

- `GET /apps/pharma/inventory` with `client.pharma.inventory.view`;
- canonical read service `Modules\Pharma\Services\UserInventoryWorkspace`;
- search by medicine name / active ingredients;
- lot, expiry, current quantity, active supplier-average cost and inventory value;
- expiry and cost filters plus value sorting when cost visibility is authorized;
- responsive Mobile/Tablet cards and Desktop table;
- no visible paginator: backend remains chunked and the PWA appends the next page through `IntersectionObserver`, with `Xem thêm` as fallback;
- presentation title/description continues through the ClientPortal manifest/settings pipeline.

### Sensitive cost permission

Inventory financial data has a separate ClientPortal permission:

`client.pharma.inventory.costs`

Without it, the request does not receive cost/value presentation, cost filters or value sorting, and the Pharma workspace does not join/calculate supplier-cost values for that browse request. Forged `cost_status` or value-sort query parameters are rejected.

With the permission, the PWA exposes the Admin-aligned inventory summary:

- Giá trị tồn theo giá vốn, including a separate still-valid value/count;
- Lô chưa định giá;
- Giá trị hàng cận hạn ≤ 6 tháng, excluding already-expired lots;
- Hàng hết hạn còn tồn.

### Ownership and mutation boundary

`Modules/Pharma` remains canonical owner of inventory balances, supplier-cost rules, warehouses and inventory mutations. ClientPortal only orchestrates authorized read presentation.

This delivery intentionally has no ClientPortal receipt/issue create, edit, post, revert or delete route. Existing `client.pharma.inventory.receipts` and `client.pharma.inventory.issues` remain future mutation capabilities and require a separate mutation/security audit before implementation.

### Validation evidence

User reported:

```text
Focused PharmaInventoryCapabilityTest: PASS
Manual Desktop/Tablet/Mobile UI acceptance: PASS

ClientApps Pharma regression:
Tests: 31 passed (968 assertions)
Duration: 2.47s
```

UI acceptance includes aligned filter controls, live medicine/active-ingredient search, compact reset-filter action, Admin-aligned cost KPIs for authorized users, protected cost/value columns, responsive cards/table and native-like continuous loading.

### Final PR gate

Final comparison before this handoff update: branch is ahead of `main` with no behind commits and changes are limited to the Inventory capability/controller/manifest/routes/view/service/test plus this handoff document.

Review the post-handoff diff, then open a PR into `main`. Do not merge automatically. After merge, run the agreed main-branch validation before starting the next capability.

### Deferred next scope

Recommended sequence remains:

1. inventory detail by medicine / lot / expiry;
2. receipt mutation only after permission, row/object-scope and stale/forged-request audit;
3. issue mutation under the same mutation gate;
4. commissions after Inventory is stable.

---

## Checkpoint — Selective manager adjustment workspace — 2026-09-29

- Multi-manager summary cards now expose `Điều chỉnh phân công` per User. Single-manager replacement remains in the existing single-mode flow.
- Added a dedicated mobile-first adjustment workspace grouped by Hospital -> assigned products for the selected User.
- Selection supports one product, all assignments within one hospital, or all assignments owned by the User. A sticky action panel shows the live selected count.
- `Thay User mục đã chọn` transfers only selected active assignments to another active User. `Gỡ mục đã chọn` uses a confirmation modal and removes only selected assignments; allocation quantities, hospital commercial-policy overrides, product policies and distribution scope are untouched.
- Pharma owns both mutations. `DrugBidAwardCommercialPolicyService` rechecks result-group scope, current owner, active status and assignment IDs under `lockForUpdate()`; stale/forged IDs are rejected instead of partially mutating another User's work.
- Transfer rejects the same User and inactive/missing target Users. After transfer/removal the main assignment screen recomputes User/assignment/hospital/product counters from canonical rows. Removed pairs become eligible again in the existing User -> Hospital -> unassigned products workflow.
- Added GET/PUT/DELETE ClientPortal routes for the per-User adjustment workspace and focused contract coverage for ownership guards, UI selection levels, transfer/remove actions and preservation copy.
- Required checkpoint: pull + focused `PharmaBidAwardsCapabilityTest`; if PASS, run `tests/Feature/ClientApps`. Manual acceptance should cover partial transfer, full-User transfer, partial remove, removing the final assignment of a User, counter refresh, and reappearance of removed products in the unassigned workflow.

## Checkpoint — Multi-User assignment wizard UX — 2026-09-29

- Replaced native-select option hiding with a real live User result panel. Typing name/email filters visible User rows immediately; tapping a row updates the canonical select/state and enables the hospital step.
- Multiple assignment Step 3 now reports the total allocated-hospital count plus completed/remaining progress.
- Assignable hospitals are sorted before completed hospitals in the Pharma read model; completed hospitals remain visible but disabled.
- Once a hospital is selected, Step 3 collapses to a compact selected-hospital summary with progress and an explicit `Đổi bệnh viện` action. The hospital list is not rendered in that state, allowing Step 4 products to move up on mobile.
- Step 4 shows live selected-product count plus select-all behavior.
- After assigning products, redirect preserves `manager_id` but intentionally drops `hospital_id`: the same User remains selected while the workflow returns to Step 3 for the next hospital. Hospital progress is recomputed from canonical assignments.
- Added focused contract coverage for live User filtering, allocated-hospital totals, completed-last ordering, collapsed hospital state, selected-product count and post-save return semantics.
- Required checkpoint: pull + focused Pharma bid-awards capability test; if PASS, run ClientApps regression. Manual acceptance should verify live User typing, hospital collapse/Change Hospital, partial assignment progress, and same-User continuation after save.

## Checkpoint — Hospital-first multi-User assignment — 2026-09-29

- Refactored the `multiple` manager mode from product-first bulk assignment to the canonical business sequence: `User -> Hospital -> remaining allocated products -> assign`.
- Hospital cards derive progress from real active allocation pairs versus active management assignments and show `assigned/allocated products`.
- Hospitals whose allocated products are all assigned remain visible for progress context but are disabled and labeled `Đã phân công hết`; they cannot be selected for the next User.
- Selecting a hospital renders only products that (a) have an active allocation at that hospital and (b) have no active management assignment.
- Server-side Pharma guard recomputes the allowed product IDs at save time. Forged/stale requests that include an already-assigned product or a product not allocated to the hospital are rejected; the flow never silently overwrites another User's assignment.
- Mutation delegates to canonical `DrugBidAwardCommercialPolicyService::assignManagers(contextAward, awardIds, partnerId, userId, actorId)`.
- Selected manager is carried in `manager_id` while navigating to a hospital and is restored after the server renders the hospital-specific product set.
- Single-manager mode, current-assignment summary and confirmed global reset remain unchanged.
- Added focused contract coverage for hospital progress, completed-hospital disablement, unassigned-product filtering, stale-request guard, canonical hospital assignment mutation and manager state preservation.
- Required checkpoint: pull + focused `PharmaBidAwardsCapabilityTest`; then real UI acceptance for User -> Hospital -> products, including a hospital becoming disabled immediately after its last remaining product is assigned.

## Checkpoint — Current manager visibility + safe assignment reset — 2026-09-29

- Manager assignment now exposes a canonical current-assignment summary before the mode cards: User name/email plus assignment, hospital and product counts.
- Single mode preselects the currently assigned User and changes the primary action to `Thay User phụ trách`; saving reuses canonical `assignManagerToAllAllocations()`, so existing assignment rows are updated rather than duplicated.
- Existing mode remains locked while assignments exist. The UI explicitly explains how to switch modes.
- Added `Gỡ phân công toàn bộ` with a centered confirmation modal. The copy explicitly states allocation quantities and commercial policies are preserved.
- Reset delegates to existing Pharma `DrugBidAwardCommercialPolicyService::removeAllManagers()`; ClientPortal owns no assignment-delete business logic.
- After reset, persisted mode becomes `unassigned`, so the user can choose Single or Multiple again.
- Added focused contract coverage for assignment summary eager-loading, DELETE route/controller delegation, current User presentation, single-mode replacement CTA and confirmation-modal reset semantics.
- Required checkpoint: pull + focused `PharmaBidAwardsCapabilityTest`, then real UI acceptance for current manager display, replace User, cancel reset, confirmed reset, and selecting Multiple after reset.

## Checkpoint — Mode-first User management assignment — 2026-09-29

- Extended the PWA sequence to `Allocation -> Commercial Policy -> User management assignment`.
- Saving the base Commercial Policy now continues to the manager-assignment workspace. The policy page also exposes an explicit `Phân công User quản lý` action and labels the primary save as `Lưu & tiếp tục`.
- The assignment workspace intentionally starts with `Cách phân công` and renders no User/product configuration until a mode is selected.
- Canonical Admin modes are preserved:
  - `single`: Một User phụ trách toàn bộ -> `DrugBidAwardCommercialPolicyService::assignManagerToAllAllocations()`;
  - `multiple`: Nhiều User phụ trách -> `assignManagerToProductAllocations()`.
- Existing assignment data determines the persisted mode. A query-string mode cannot override an already-persisted single/multiple assignment state; the UI explains that all assignments must be removed before changing mode, matching Admin semantics.
- PWA assignment is gated until every active allocation has an effective commercial policy (hospital override or base product policy).
- Only active Users are offered and Pharma workflow revalidates active User status server-side.
- Multiple mode shows only products with active allocations and displays assigned-hospital / allocated-hospital progress. It supports select-all and assigns only across real allocation pairs; it does not create allocations.
- ClientPortal remains a thin adapter; assignment persistence/business mutation stays in the canonical Pharma commercial-policy service.
- Added focused contract coverage for routes, sequence gate, mode-first disclosure, canonical service reuse, active-user guard, mobile UI hooks and no Admin/Livewire reuse.
- Required checkpoint: pull + focused `PharmaBidAwardsCapabilityTest`; if PASS run `tests/Feature/ClientApps`, then real tablet/mobile acceptance of both assignment modes before PR.

## Checkpoint — Compact policy values + incomplete allocation filtering — 2026-09-29

- Base commercial-policy inputs now trim the model's decimal:4 presentation: e.g. `25.0000 -> 25`, while meaningful decimals remain (e.g. `2.5000 -> 2.5`). Persistence semantics are unchanged.
- Product allocation overview now has client-side live product search with clear (×) and a `Chưa phân bổ hết` toggle. Search and incomplete-only filtering compose without navigation/reload.
- Cards with remaining quantity > 0 render a red `Phân bổ chưa hết` badge and subtle warning surface; fully allocated cards retain green `Đã phân bổ hết`.
- Filtering uses the already-rendered finite result-group products and does not add a server route/query or change canonical allocation calculations.
- Added focused contract coverage for compact policy values, search/clear/toggle hooks, incomplete status data and empty-filter feedback.
- Required checkpoint: pull + focused `PharmaBidAwardsCapabilityTest`, then mobile/tablet acceptance of search + incomplete filter.

## Checkpoint — Hospital quantity formatting + base/override CSKD — 2026-09-29

- Removed redundant `Đang phân bổ` and `Hoàn tất` badges from hospital cards; numeric product progress remains the primary status signal.
- Hospital allocation quantity inputs now render integer quantities with Vietnamese thousands separators and reformat while typing.
- Formatted quantity strings are normalized at the ClientPortal request boundary before Laravel validation by stripping non-digits, then validated as positive integers. Example: `1.000 -> 1000`; formatted strings never reach the Pharma allocation service.
- Percentage inputs intentionally do NOT use the quantity parser; percentages retain decimal semantics and 0..100 validation.
- Hospital CSKD now shows two context cards for each allocated product: allocated quantity and canonical base CSKD from `DrugBidAwardProductPolicy.commission_percentage`.
- `CSKD riêng bệnh viện (%)` remains an override. Blank means use the base policy; saving blank now calls canonical `saveHospitalPolicyOverride()` so an existing override is reset to NULL instead of silently being skipped.
- Added focused contract coverage for numeric normalization, formatted display, base-policy context, override reset path, and removed progress badges.
- Required checkpoint: pull + focused `PharmaBidAwardsCapabilityTest`, then verify formatted quantity save/reload and blank override reset in real UI.

## Checkpoint — Collapsible allocation dashboard + product progress cards — 2026-09-29

- Tablet UI acceptance passed for hospital-first allocation, followed by a compactness refinement.
- `Thiết lập chung` is now a section-level toggle; saved setup is collapsed by default while its header retains province/facility/effectivity summary.
- `Bệnh viện nhận phân bổ` is also a section-level toggle; search and hospital cards render only when expanded.
- Added an always-visible product allocation overview between those sections. Each product card shows winning quantity, total active allocated quantity across hospitals, and remaining quantity.
- Remaining is calculated in `ClientBidAwardWorkflow::productAllocationCards()` from canonical active allocations as `max(winning - allocated, 0)`; Blade only presents the result.
- A product with positive winning quantity and remaining = 0 is labelled `Đã phân bổ hết`.
- Hospital-first quantity and hospital-policy workflows are unchanged.
- Required checkpoint: focused `PharmaBidAwardsCapabilityTest`, then tablet/mobile visual acceptance before full ClientApps regression.

## Checkpoint — Hospital-first allocation UX — 2026-09-29

- Reworked the allocation PWA after tablet acceptance feedback: the page no longer expands a product x hospital matrix.
- Canonical Distribution Setup remains first, but its three stages are now compact `<details>` toggles: (1) scope/effectivity, (2) KCB facilities, (3) review/save. Existing saved setup is collapsed into a summary by default.
- After setup, the page renders compact hospital cards with progress: allocated products / total products and status Chưa phân bổ / Đang phân bổ / Hoàn tất.
- Each hospital has `Nhận phân bổ số lượng`, opening a dedicated hospital workspace. Only that hospital's product quantities are rendered there.
- Hospital allocation writes still go through canonical `DrugBidAwardAllocationService`; distribution membership is revalidated server-side.
- Added hospital-specific CSKD workspace. This intentionally uses canonical `DrugBidAwardCommercialPolicyService::saveHospitalPolicyOverride()`, because the domain already stores hospital+product overrides on allocations. Products without an active allocation stay visibly locked.
- The previous global product-policy route remains available for the existing capability, but the hospital-first allocation flow links to the hospital override UI.
- Added focused contract coverage for collapsible setup, absence of the old matrix on the main allocation page, hospital routes/workspaces and allocation-before-CSKD gating.
- Required checkpoint: pull + focused `PharmaBidAwardsCapabilityTest`; then tablet/mobile UI acceptance before ClientApps regression.
- Status: IMPLEMENTED — AWAITING OPERATOR TEST.

## Checkpoint — Bid Awards canonical 3-step Distribution Setup before product allocation — 2026-09-29

- Refactored the PWA allocation flow after real Admin/PWA comparison.
- The first section is now the canonical `Thiết lập chung / Phạm vi & hiệu lực phân bổ`: Step 1 province scope + effective dates, Step 2 official KCB facilities, Step 3 selected-facility review.
- Step 3 renders already selected/saved facilities checked by default and mirrors Step-2 checkbox changes; unchecking in review removes the corresponding Step-2 selection before save.
- Removed session-backed hospital selection as the source of truth. PWA now reads/writes the canonical `DrugBidAwardDistributionScopeService`, matching Admin `ProductWorkspace::saveDistributionScope()`.
- Existing scope is loaded back into the PWA from scope provinces + Partner source references -> OfficialSourceFacility IDs, so revisiting allocation shows the saved hospitals checked.
- Product allocation appears only after a canonical Distribution Scope exists and uses its Partner hospitals. Actual quantity writes continue through `DrugBidAwardAllocationService`.
- CSKD gating remains unchanged: allocation must exist before product policy is allowed.
- Tablet layout keeps the three setup steps in three columns when space permits; mobile stacks them. Product allocation remains card-based with two hospital columns on tablet.
- No Admin Livewire view/controller is reused; only canonical Pharma services/data rules are reused.
- Required checkpoint: pull, run focused `PharmaBidAwardsCapabilityTest`, then revisit the allocation URL and verify saved facilities are checked in Step 2 and Step 3 before any full regression.
- Status: IMPLEMENTED — AWAITING OPERATOR PULL / FOCUSED TEST / UI ACCEPTANCE.

## Checkpoint — Pharma PWA Bid Awards allocation wizard + gated CSKD — 2026-09-29

- Branch: `feat/clientportal-pharma-bid-awards`.
- Existing KQLCNT list/detail remains intact; mutation is opt-in through two new ClientPortal action permissions: `client.pharma.bid-awards.allocate` and `client.pharma.bid-awards.commercial-policy`.
- Added `ClientBidAwardWorkflow` as a thin Pharma-domain adapter. Allocation writes reuse canonical `DrugBidAwardAllocationService`; product policy writes reuse canonical `DrugBidAwardCommercialPolicyService`.
- Allocation sequence is tablet/mobile-first:
  1. select hospitals from the Admin-defined Distribution Scope and save the step into the current User session;
  2. only then show product cards and quantity inputs for the selected hospitals;
  3. writes are validated again by the canonical allocation service (active hospital, in distribution scope, positive quantity, total not above winning quantity, contract commitment guard).
- The Step-1 selection intentionally does not mutate Distribution Scope: that scope is Admin/domain ownership and is broader than a User's temporary wizard selection.
- Commercial Policy UI is locked until the KQLCNT has an active allocation. Product percentages are additionally rejected unless that specific product has an active allocation.
- Detail shows `Phân bổ số lượng` only with allocate permission and `Thiết lập chính sách kinh doanh` only with commercial-policy permission; the latter renders a disabled explanatory card until allocation exists.
- Allocation UI uses searchable hospital cards, one column on mobile/two on tablet, product cards, large touch targets and sticky save actions. No horizontal Admin table was copied into PWA.
- New permissions are manifest-defined and are discovered/synced by `ApplicationPermissionService`; no schema migration is required. They still must be granted to the testing User/role before UI acceptance.
- No allocation cancellation, contract management, import/export or Admin Livewire reuse was added in this checkpoint.
- Required operator checkpoint: `git pull --ff-only`, focused `PharmaBidAwardsCapabilityTest`. Stop on any failure/runtime 500 before ClientApps regression.
- Status: IMPLEMENTED — AWAITING OPERATOR PULL / FOCUSED TEST / PERMISSIONED UI ACCEPTANCE.

## Checkpoint — Pharma PWA Bid Awards filters + tablet/mobile setup UX — 2026-09-29

- Branch: `feat/clientportal-pharma-bid-awards`.
- Added PWA filters matching Admin Drug Bid Awards semantics: Chủ đầu tư, Sản phẩm, Giá trị asc/desc, Thiết lập kinh doanh (CSKD ready/missing, allocation ready/missing).
- Admin's canonical `<x-select-search>` was inspected before implementation. It is Livewire-bound (`$wire`, `wire:ignore`) and is therefore not embedded directly into the plain GET-form ClientPortal PWA; doing so would introduce a runtime dependency/error. PWA keeps native responsive selects with the same option data/semantics.
- Filters live under a mobile-first collapsible `Bộ lọc nâng cao`; active filters reopen the panel and show an active count. Main live search remains always visible.
- Filter changes submit/reset the result list to page 1; result navigation remains progressive `Xem thêm kết quả`, not traditional pagination.
- Removed `SP của tôi` and `SL của tôi` from result cards and removed `BV của tôi` / `SL của tôi` from product cards.
- Result cards now show compact KQLCNT signals: product count, `Phân bổ: Đã/Chưa thiết lập`, `CSKD: Đã/Chưa thiết lập`, total KQLCNT value and contract remaining.
- Setup filters/statuses are global read-only KQLCNT facts, while the existing green responsibility badge remains only when the current User has an active scoped assignment/allocation.
- No Admin UI reuse, mutation, export, permission broadening or migration.
- Required operator checkpoint: `git pull --ff-only`, focused `PharmaBidAwardsCapabilityTest`; after PASS run ClientApps regression, then Desktop/Tablet/Mobile acceptance.
- Status: IMPLEMENTED — AWAITING OPERATOR PULL / FOCUSED TEST.

## Checkpoint — Pharma PWA Bid Awards global KQLCNT + personal context — 2026-09-29

- Branch: `feat/clientportal-pharma-bid-awards`.
- Product decision updated after real Admin/UI acceptance: the Bid Awards list is a read-only KQLCNT catalogue comparable to `/admin/pharma/drug-bid-awards`, not an assignment-only inbox.
- `UserBidAwardWorkspace` now reads all canonical `pharma_drug_bid_awards` result groups while calculating the current User's responsibility context through correlated active assignment + active allocation subqueries.
- Global totals are calculated independently from User assignment joins, preventing duplicated KQLCNT value when one award is assigned to multiple hospitals.
- No other User's assignment/allocation rows are exposed. Personal context only contains aggregate counts/quantities for the current `user_id`.
- Detail lists all products in the selected KQLCNT read-only; products assigned to the current User are identified by `BV của tôi` and `SL của tôi`.
- Result identity follows Admin semantics: TBMT when present, otherwise the individual award id.
- UI remains mobile-first: one card on mobile, two on tablet, three on wide desktop, live search, touch feedback and progressive `Xem thêm`.
- Default managed presentation is now `Kết quả trúng thầu` rather than `Kết quả trúng thầu của tôi`; Admin presentation settings can still override it.
- No Admin controller/Livewire reuse, no mutation/import/export, no permission broadening and no migration.
- Required operator checkpoint: `git pull --ff-only` then focused `PharmaBidAwardsCapabilityTest`; after PASS run `tests/Feature/ClientApps`, then real Desktop/Tablet/Mobile acceptance against the existing 11 Admin TBMT dataset.
- Status: IMPLEMENTED — AWAITING OPERATOR PULL / FOCUSED TEST.

## Checkpoint — Pharma PWA Bid Awards scope verification — 2026-09-29

- Verified `UserBidAwardWorkspace` against canonical `UserCommercialHospitalWorkspace`.
- Both use the same assignment/allocation identity: `drug_bid_award_id + partner_id`, current `user_id`, active management assignment, active allocation.
- Deliberately did not broaden Bid Awards scope merely to populate the UI; a User must not see Admin-wide results outside their responsibility.
- Added a regression contract that locks Bid Awards to the canonical Commercial Workspace scope clauses and guards against auth/global-scope shortcuts.
- Empty state now explicitly says that no bid result has an active allocation in the User's responsibility scope, making missing assignment/allocation data distinguishable from a generic empty list.
- No migration, permission broadening, Admin reuse, mutation or export added.
- Required operator checkpoint: `git pull --ff-only`, focused `PharmaBidAwardsCapabilityTest`; only after PASS run `tests/Feature/ClientApps`.
- Status: IMPLEMENTED — AWAITING OPERATOR PULL / FOCUSED TEST.

## Checkpoint — Pharma PWA Bid Awards responsive summary refinement — 2026-09-29

- Branch: `feat/clientportal-pharma-bid-awards`.
- Refined `/apps/pharma/bid-awards` to mirror the Admin Drug Bid Awards mental model: one responsive card per TBMT/result group, without copying the Admin table or mutation controls.
- User scope remains enforced server-side by active management assignment + active allocation; the PWA never broad-loads all Admin bid awards and hides them in Blade.
- Summary cards now surface assigned product count, hospital count, allocated quantity, allocated value and contract remaining/status where source data supports it.
- Responsive layout is 1 column on mobile, 2 on tablet, 3 on wide desktop; mobile keeps live search and progressive `Xem thêm`.
- Fixed the data-path date formatter from invalid `CarbonCarbon::parse` to `\\Carbon\\Carbon::parse`; the earlier empty-state acceptance did not exercise this branch.
- No Admin controller/Livewire reuse, no mutation/import/export, no permission broadening, no migration.
- Contract coverage updated in `tests/Feature/ClientApps/PharmaBidAwardsCapabilityTest.php`.
- Required operator checkpoint: `git pull --ff-only`, focused Bid Awards capability test, then `tests/Feature/ClientApps` only after focused PASS.
- Status: IMPLEMENTED — AWAITING OPERATOR PULL / FOCUSED TEST / CLIENTAPPS REGRESSION / REAL DATA TABLET-MOBILE ACCEPTANCE.

## Checkpoint — Pharma PWA Bid Awards workspace — 2026-09-29

- Branch: `feat/clientportal-pharma-bid-awards`, based on current `main` after PR #235.
- Scope: activate the existing Pharma PWA `bid-awards` capability as a read-only User workspace. Inventory and Commissions remain deferred.
- Canonical ownership remains in `Modules/Pharma`. New `UserBidAwardWorkspace` exposes only User-scoped reads and requires both ACTIVE management assignment and ACTIVE allocation; ClientPortal does not query Admin controllers/Livewire or duplicate award business rules.
- PWA routes: `/apps/pharma/bid-awards` and SHA-1 scoped detail `/apps/pharma/bid-awards/{scope}`. A scope not derivable from the authenticated User's active assignments returns 404.
- Manifest now supplies `eyebrow`, `page_title`, and `page_description`; runtime consumes them through `ClientPortalSettingsService::featurePresentation()`, so Admin `/admin/client-apps` remains the presentation owner.
- UI is mobile-first: live search with clear action, touch feedback, card presentation, and in-context `Xem thêm` loading for result groups/products. No export/download was added, so this batch does not introduce a new external-file handoff.
- Focused test added: `tests/Feature/ClientApps/PharmaBidAwardsCapabilityTest.php`.
- Required operator checkpoint: `git pull --ff-only`, run focused Bid Awards test first; only if PASS run `php artisan test tests/Feature/ClientApps`. Then perform real Desktop + Tablet/Mobile/PWA acceptance, including permission/404 scope behavior and Admin-managed page content.
- No migration. No PR or merge before operator test/UI acceptance.
- Status: **IMPLEMENTED — AWAITING OPERATOR PULL / FOCUSED TEST / CLIENTAPPS REGRESSION / REAL UI ACCEPTANCE.**

---

# ClientPortal Module — Collaboration Handoff


## Checkpoint — Pharma PWA Inventory Issue/Order read workspace — 2026-09-30

- Branch: `feat/clientportal-pharma-inventory-issues-read`, based on `main` at `cd512c387d8855993100546343c0fb4413fcc801` (PR #238).
- Scope is MR1 only: read-only User-scoped list + detail for Inventory Issue/Order. No create/update/submit/approve/post/revert mutation and no migration.
- Canonical ownership stays in `Modules/Pharma` through `UserInventoryIssueWorkspace`. ClientPortal only authenticates, authorizes, orchestrates and renders.
- Visibility is server-side: an issue is visible only when the authenticated Web User is `manager_user_id` or `created_by`; direct detail access outside that scope returns 404.
- PWA routes: `/apps/pharma/inventory/issues` and `/apps/pharma/inventory/issues/{issue}`, guarded by `client.pharma.inventory.issues`.
- List UI follows the approved mobile reference: centered header/back action, large search, separate filter button, bottom-sheet filter with reset/cancel/apply, status rail, empty state, touch-friendly cards, progressive `Xem thêm`; desktop switches to a wide table.
- Filters: search by document/customer/hospital/investor, source (price list / bid award), current canonical status and issue-date range.
- Detail is read-only and shows source, recipient, manager, price list when applicable, product lines, quantity, unit price, lot/expiry when present, total and notes.
- Existing Inventory read workspace now exposes a permission-aware entry to `Đơn hàng / Phiếu xuất`.
- Focused test added: `tests/Feature/ClientApps/PharmaInventoryIssuesCapabilityTest.php`.
- Required operator checkpoint: `git pull --ff-only`, run the focused Inventory Issues capability test first. Stop on FAIL/500. After PASS run impacted ClientApps regression and then real Desktop + Tablet/Mobile/PWA acceptance.
- Status: **IMPLEMENTED — AWAITING OPERATOR PULL / FOCUSED TEST / UI ACCEPTANCE.**

## Current delivery — ClientPortal Feature Page Content & PWA AI Workflow

- Last updated: 2026-09-29
- Active branch: `feat/clientportal-feature-page-content`
- Base branch: `main`
- Base checkpoint: `9027f08542c964028088cb340519d6f36fbe7c89`
- Branch comparison before this handoff commit: **ahead 15 / behind 0**
- Status: **IMPLEMENTED — FOCUSED PASS — CLIENTAPPS REGRESSION PASS — REAL UI PASS — READY FOR PR**

### Objective

Make routable PWA feature hero/page copy manageable from `/admin/client-apps` without making routes, permissions or business logic configurable, connect the first runtime consumer (Pharma Commercial Workspace), and establish a mandatory AI workflow gate for future ClientPortal/PWA work.

### Delivered contract

Generic feature presentation now supports:

```text
eyebrow
page_title
page_description
```

Resolution path:

```text
application manifest defaults
    -> ApplicationRegistry
    -> ClientPortalSettingsService::featurePresentation()
    -> controller
    -> Blade
```

Admin editing is exposed through:

```text
/admin/client-apps
-> Giao diện Application & Feature
-> Nội dung trang PWA
```

The Admin form edits presentation text only. Route names, permission names, source-module ownership and business behavior remain source-controlled.

### Pharma Commercial runtime integration

The Pharma Commercial feature manifest defines the current defaults:

```text
Commercial Workspace
Công việc bệnh viện của tôi
Chọn Chủ đầu tư / kết quả trúng thầu để xem đúng phạm vi bệnh viện được phân công.
```

`PharmaApplicationController::commercial()` resolves the Commercial feature and passes its resolved presentation to the view. The Commercial Blade consumes managed eyebrow/title/description instead of duplicating those configurable literals.

When a bid-result scope is selected, the existing contextual `Đang xem ...` description remains dynamic; the managed description is the initial/no-selected-result page description.

No migration is required. Existing `client_portal_settings` storage and the generic feature presentation group are reused.

### PWA AI/workflow governance

Added:

```text
docs/modules/ClientPortal/PWA_AI_WORKFLOW.md
```

and linked it as a mandatory gate from:

```text
docs/GITHUB_COLLABORATION_WORKFLOW.md
```

Any new chat/task touching `/my-apps`, `/apps/*`, `Modules/ClientPortal`, installed PWA/mobile Client UX or `/admin/client-apps` must read the PWA AI workflow before code analysis/implementation. The gate defines mandatory ClientPortal docs, conditional file/export/debug docs, architecture/security boundaries, managed page-content rules and test/UI/PR gates.

For PWA file download/open/share/export work, `docs/PWA_EXTERNAL_FILE_HANDOFF.md` remains an additional mandatory gate.

### Automated coverage added/updated

`ClientPortalPwaSettingsTest` covers:

- Commercial feature presentation defaults;
- persisted Admin override resolution;
- updater identity persistence;
- Admin/controller validation for safe page-copy fields;
- no editable route/permission fields in the presentation form.

`PharmaCommercialCapabilityTest` guards:

- controller resolution through `ClientPortalSettingsService::featurePresentation()`;
- managed eyebrow/title/description consumption in the Commercial view.

### Validation evidence

Focused ClientPortal PWA settings + Pharma Commercial capability tests: **PASS**.

Latest ClientApps regression reported by the user:

```text
Tests: 134 passed (1454 assertions)
Duration: 24.37s
```

Real UI acceptance: **PASS**.

Verified through `/admin/client-apps` that Commercial eyebrow/title/description can be edited and persisted, and the Pharma PWA Commercial Workspace renders the managed values while preserving the existing scoped workflow.

The later documentation/workflow additions do not alter rendered UI. Automated and manual UI gates required for this delivery are satisfied.

### Scope / known boundaries

- only Pharma Commercial is connected as the first runtime consumer in this delivery; the generic Admin/schema contract is reusable by later PWA features;
- no route, permission or source-module contract is made Admin-editable;
- no migration/schema change;
- no file download/open behavior changed, therefore PWA external-file platform acceptance is **NOT APPLICABLE** to this batch;
- focused tests, ClientApps regression and real UI acceptance are PASS;
- PR may now be created for review;
- merge still requires explicit user approval.

### Next step

Create/review the PR into `main`. Do not merge until the user explicitly approves the reviewed PR.


## Current delivery — Pharma PWA Medicine Catalog

- Last updated: 2026-09-27
- Active branch: `feat/clientportal-pharma-products`
- Base branch: `main`
- Branch comparison before this handoff commit: **ahead 44 / behind 0**
- Status: **IMPLEMENTED — FOCUSED TEST PASS — DESKTOP UI PASS — READY FOR PR**

### Objective

Deliver the first Pharma business capability in ClientPortal as a read-only, user-oriented Medicine Catalog while keeping Medicine Master ownership and business queries in `Modules/Pharma`.

### Delivered capability

- `GET /apps/pharma/products` and read-only product detail;
- full Medicine Master catalog for Users authorized with `client.pharma.products.view`;
- live-search UX with 350 ms debounce and 25/50/100 pagination;
- circular-group selector plus default ordering by circular group, circular order, medicine name and SKU;
- status filters for awarded products, HSSP and supplier pricing;
- catalog aggregate counts and SKU total;
- compact business-status language: blue = Trúng thầu, emerald = HSSP, amber = Giá NCC;
- desktop/mobile catalog presentation with Nhóm and Giá kê khai;
- declared price uses variant value first and Medicine fallback;
- detail workspace surfaces basic product information, product dossier, recent bid-award information and supplier information when available;
- supplier commercial pricing is separately protected by `client.pharma.products.supplier-pricing`.

### Canonical ownership / security boundary

```text
Modules/Pharma
  MedicineCatalog + MedicineCatalogItem
  owns Medicine/Variant query and business-data composition
  owns catalog grouping, counts and Product Intelligence payload

Modules/ClientPortal/Applications/Pharma
  owns auth:web + client permission boundary
  owns PWA routes/controller orchestration
  owns responsive catalog/detail presentation
```

ClientPortal does not query Pharma tables directly for this capability and does not reuse Admin controllers, Blade, Livewire or `auth:admin`.

Supplier pricing is treated as sensitive company data. A User without `client.pharma.products.supplier-pricing` does not receive supplier price values, does not see the Giá NCC filter/badge, and cannot access the supplier-priced filter by manually crafting the query string.

### Validation evidence

User reported the focused capability test as **PASS**:

```text
php artisan test tests/Feature/ClientApps/PharmaProductsCapabilityTest.php
PASS
```

Manual Desktop UI acceptance: **PASS**.

Accepted UX includes live search, pagination, circular-group filtering, colored business filters/badges, SKU count, Nhóm, Giá kê khai, detail Product Intelligence and aligned filter toolbar.

Per project testing policy, broad ClientPortal/full-project regression is intentionally deferred until merge-to-main validation.

### Scope deliberately excluded

- no Medicine Master create/edit/delete;
- no Medicine import or master verification;
- no HSSP mutation;
- no bid-award mutation;
- no supplier-tracking mutation;
- no Admin Pharma permission reuse;
- no PWA file/export behavior in this capability.

### PR gate

Review the final branch diff and open a PR into `main`. Do not merge automatically. After merge, run the agreed wider main-branch validation before starting the next Pharma PWA capability.

## Current delivery — Pharma PWA Foundation

- Last updated: 2026-09-27
- Active branch: `feat/clientportal-pharma-pwa-foundation`
- Base branch: `main`
- Branch comparison at closeout: **ahead 8 / behind 0** before this handoff commit
- Status: **IMPLEMENTED — FOCUSED/CLIENTAPPS REGRESSION PASS — DESKTOP UI PASS — READY FOR FINAL PR GATE**

### Objective

Establish a separate ClientPortal PWA entry point for Pharma Users without moving Pharma domain ownership into ClientPortal and without reusing Admin authentication, permissions, controllers, Blade or Livewire presentation.

### Delivered foundation

- application discovery through `Modules/ClientPortal/Applications/Pharma/manifest.php` with `source_module = Pharma`;
- launcher/application contract for `/my-apps` → Pharma → `/apps/pharma`;
- route boundary: `web`, `auth:web`, `client.application:pharma`, `client.feature:pharma,overview`;
- ClientPortal permissions for overview, products, price lists, bid awards, commercial, inventory receipts/issues and commissions;
- only Overview is routable in this foundation; future capabilities are visible only when authorized and remain marked `Sắp triển khai`;
- dedicated Pharma PWA controller and responsive ClientPortal application view;
- route availability is resolved in the controller, not by Facade calls inside Blade;
- no Pharma migration, schema rewrite, Admin route change or business-data mutation.

### Canonical ownership boundary

```text
Modules/Pharma
  owns Pharma models/schema/business rules
  owns canonical domain services/query contracts
  owns Admin-only master/configuration/destructive operations

Modules/ClientPortal/Applications/Pharma
  owns PWA routes and presentation
  owns web-guard/client application authorization
  owns client-safe orchestration
  consumes Pharma service/query contracts capability by capability
```

Dependency direction remains `ClientPortal Pharma adapter -> Modules/Pharma`; Pharma must not depend on ClientPortal.

### Validation evidence

Focused Foundation test: **PASS**.

ClientPortal application regression reported by user:

```text
Tests: 120 passed (805 assertions)
Duration: 8.21s
```

Manual Desktop UI smoke for `/apps/pharma`: **PASS**. The page renders the ClientPortal PWA shell, Overview navigation and authorized capability cards without the previous 500 error.

A runtime defect found during UI smoke was corrected: the Blade expression had resolved `IlluminateSupportFacadesRoute` as a class name. Route availability now belongs to the controller and the contract test protects against direct `Route::has()` use in this Blade.

### Security / data-scope boundary

- Foundation permissions are `client.pharma.*` Web permissions; Admin Pharma permissions are not reused.
- Permission does not imply unrestricted row-level Pharma access.
- Future commercial, hospital, commission and other user-sensitive capabilities must combine feature permission with assigned business scope.
- Admin-only master/configuration/destructive workflows remain outside the PWA.

### Known issues / deferred work

- Products, price lists, bid awards, commercial, inventory and commissions are intentionally not implemented in this foundation.
- Mobile/tablet acceptance for future data-heavy capabilities is performed when each capability is implemented.
- No PWA file download/open behavior is introduced by this foundation, so the external-file handoff gate is not applicable here.
- Full-project regression is **NOT APPLICABLE — module-scoped ClientPortal regression strategy**.

### Next step

Complete final PR gate for this Foundation (working-tree clean verification and PR review/merge approval). After Foundation is merged, start the first business capability on a new branch. The planned first capability is read-only Pharma product/Medicine catalog, after inspecting current Pharma service/query boundaries and row-scope requirements.

## Current delivery — Invoices GDT Smart Sync PWA

- Last updated: 2026-09-09
- Active branch: `feat/clientportal-invoices-gdt-smart-sync`
- Status: **IMPLEMENTED — FOCUSED TESTS PASS — UI/RUNTIME PASS — FINAL PINT CONFIRMATION PENDING**

### Objective

Expose a secure PWA GDT synchronization workflow while preserving the ownership boundary: ClientPortal owns client UX/orchestration and Invoices owns GDT/business/data persistence.

### Delivered capability

- server-side GDT token readiness gate before queue dispatch;
- CAPTCHA reconnect flow with challenge key kept in server session;
- no GDT username/password/access token exposed to Blade, JavaScript, localStorage, IndexedDB or PWA cache;
- current-year Smart Date readiness split by `purchase` / `sold` using canonical `issued_date` and `invoice_type` fields;
- suggested start remains the latest invoice date itself, not `+1 day`, so same-day late invoices can be rechecked;
- responsive PWA Smart Sync UI with status/log presentation;
- GDT payload persistence into the canonical `invoices` table before Excel export;
- idempotent identity strategy: prefer `invoice_type + lookup_code`, fallback to `invoice_type + symbol + invoice_number + tax_code + issued_date` when lookup code is absent;
- transactional DB persistence with `created / updated / unchanged` statistics in the sync log;
- Excel export remains available after successful DB persistence.

### Validation evidence

Recorded focused automated gate:

```text
14 tests passed (115 assertions)
```

Manual PWA/UI/runtime acceptance reported by user: **PASS**.

The final Pint run previously exposed one style-only issue in `GdtInvoiceService.php`; the affected formatting rules were corrected in commit `24129a52ef532e916ed282811ae7a8553c18a1c7`. A post-fix Pint confirmation remains the final CLI closeout gate.

### Security / ownership boundary

```text
Modules/Invoices
  owns GDT API/auth integration
  owns invoice persistence/schema/model
  owns Excel/file generation
  owns queue/business behavior

Modules/ClientPortal/Applications/Invoices
  owns PWA routes
  owns web-guard/client authorization
  owns CAPTCHA/connect presentation
  owns Smart Sync responsive UX
```

ClientPortal does not copy Invoices models/schema/core services and does not reuse Admin authentication or Admin presentation.

## Previous delivery — Invoices Partner Report PWA

- Last updated: 2026-09-09
- Active branch: `feat/clientportal-invoices-partner-report`
- Base branch: `main`
- Base merge checkpoint: `b0f4d18ee7430e16b302ac43914c3dbac3065e40`
- Status: **IMPLEMENTED — UI PASS — BOUNDED CLI PASS — READY FOR PR**
- Closeout: `docs/modules/ClientPortal/INVOICES_PARTNER_REPORT_CLOSEOUT.md`

### Objective

Expose the Admin partner-report capability as a dedicated executive ClientPortal/PWA experience without moving Invoices business ownership into ClientPortal and without reusing Admin authentication or Admin presentation.

### Canonical ownership

```text
Modules/Invoices
  owns invoice/partner business data
  owns partner aggregation/query rules
  owns InvoicePartnerReportService

Modules/ClientPortal/Applications/Invoices
  owns PWA routes
  owns web-guard/client-feature authorization
  owns executive responsive presentation
  owns client-safe orchestration
```

ClientPortal does not copy invoice models/schema/core services and does not reuse `auth:admin`, Admin Blade or Admin Livewire for this delivery.

### Delivered client capability

```text
GET /apps/invoices/partners
name: client.invoices.partners
permission: client.invoices.partners.view
```

Implemented Partner Report UX:

- executive partner KPIs;
- Top bán ra;
- Top mua vào;
- partner search by name or MST using the shared compact autocomplete pattern;
- relationship filter: all / customer / supplier;
- relationship-aware default sorting: customer => sold descending, supplier => purchase descending;
- partner detail;
- pagination;
- Mobile/Tablet card presentation below `xl`;
- Desktop table presentation from `xl` upward;
- money values use stable single-line presentation where appropriate;
- responsive filter panel for Mobile/Tablet;
- Desktop horizontal filter workspace remains available.

### Invoice List follow-up included in this branch

`/apps/invoices/list` now supports `Tháng = Cả năm`.

Contract:

- empty `month` => full selected year (`01/01` through `31/12`);
- explicit month => selected month only;
- no `month` parameter on a normal initial request keeps the established current-month default;
- header text reflects either `Cả năm YYYY` or `Tháng MM/YYYY`;
- search, pagination and export preserve the selected yearly/monthly scope;
- export contract remains selected rows => selected only, no selection => all filtered.

### Validation evidence

Manual UI acceptance: **PASS**

```text
Partner report Desktop: PASS
Partner report Tablet: PASS
Partner report Mobile/PWA: PASS
Partner autocomplete: PASS
Relationship-aware sort: PASS
Top sold/top purchase amount visibility on Tablet/Mobile: PASS
Invoice List `Cả năm`: PASS
```

Final bounded CLI acceptance: **PASS**

```text
php artisan test tests/Feature/ClientPortal/InvoicesPartnerReportPwaContractTest.php
PASS

vendor/bin/pint --test <changed PHP files for this delivery>
PASS
```

The first focused run exposed only stale responsive assertions (`md` breakpoint) and one Pint style issue in `InvoicePartnerReportService.php`; both were corrected. The contract now protects the current responsive boundary (`xl:hidden` cards / `xl:block` desktop table).

### Validation policy

Previously PASSed Invoices PWA tests/build/UI scopes are not rerun unless later changes invalidate them. Full-repository Pint is not a merge gate because the repository contains unrelated legacy formatting debt. Only changed/invalidated scopes are gates for this delivery.

### Security / operations boundary

- GDT credentials/tokens remain server-side only.
- No GDT token is placed in localStorage, IndexedDB, PWA cache, JS, Blade or public component state.
- Google Drive configuration, backup/restore and destructive operations remain Admin/Invoices-only.
- No migration, schema rewrite or destructive data operation is part of this delivery.

## Previous completed Invoices PWA delivery

The base Invoices PWA delivery was merged through PR #173 and subsequently corrected by selected-month KPI hotfix PR #175.

Canonical Invoices PWA routes already on `main` include:

```text
GET  /apps/invoices
GET  /apps/invoices/list
GET  /apps/invoices/list/{invoice}
POST /apps/invoices/export
GET  /apps/invoices/list/{invoice}/pdf
GET  /apps/invoices/sync
POST /apps/invoices/sync
```

Base PWA validation previously recorded:

```text
Executive Dashboard UI: PASS
Invoice List Desktop: PASS
Invoice List Tablet: PASS
Invoice List Mobile: PASS
Partner autocomplete: PASS
Selected-vs-filtered export UX: PASS
Focused ClientPortal/Invoices tests: PASS
Latest recorded focused batch from base delivery: 62 passed (378 assertions)
npm run build: PASS
```

## Stable ClientPortal architecture

ClientPortal remains an authenticated Client/WebApp platform that can host multiple applications without placing module-specific business logic into ClientPortal core.

Core rule:

> Không được thêm logic đặc thù Module vào ClientPortal core.

Auth owns authentication/session/logout behavior. ClientPortal owns PWA presentation and consumes canonical module/application contracts.

Historical stable checkpoints:

```text
MR-1 Portal Architecture Foundation: MERGED
MR-2 Adaptive Navigation: MERGED
MR-3 Dynamic Portal Home: MERGED
MR-4 Muasamcong reference migration: MERGED
MR-5 PWA External File Download & Return UX: MERGED — PR #64
MR-6 PWA Install UX: MERGED — PR #65
MR-7 PWA Account Registration & Google Authentication: MERGED — PR #67
MR-8 PWA Header Account Menu: MERGED — PR #68
Canonical web logout corrective: MERGED — PR #86
ClientPortal architecture boundaries refactor: MERGED — PR #124
Invoices executive PWA: MERGED — PR #173
Invoices selected-month KPI hotfix: MERGED — PR #175
Invoices Partner Report PWA: READY FOR PR
Invoices GDT Smart Sync PWA: UI/RUNTIME PASS — FINAL PINT CONFIRMATION PENDING
```

## Deferred debt

- relocate/remove legacy root Muasamcong export jobs only with explicit queued-payload proof;
- remove root model aliases only after caller proof;
- avoid speculative consolidation of small ClientPortal resolver/presenter services without concrete duplication or caller evidence;
- keep Invoices Google Drive configuration, backup/restore and destructive recovery outside ClientPortal PWA unless a separate security/operations scope explicitly authorizes them.
