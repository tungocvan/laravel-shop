# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Affiliate Legacy Ownership Cleanup**

Status: **IMPLEMENTED — FINAL VERIFICATION PENDING**

Branch/checkpoint: `refactor/admin-affiliate-ownership-cleanup`

This approved slice removes independent Affiliate business/persistence ownership from the historical Admin duplicates while preserving the currently reachable Website-owned management runtime. It does not create a new Affiliate module, move schema/migrations, or redesign commission business rules.

## Ownership decision

Affiliate remains a cross-module business capability with explicit boundaries:

- `Modules/Website` currently owns the canonical Affiliate admin route/controller, CommissionList/CommissionMatrix management components, AffiliateLevel/AffiliateScheme persistence models, commission orchestration service and rank orchestration service;
- `Modules/Order` retains order and commission state;
- `Modules/Product` retains product ownership/query behavior;
- shared User identity retains affiliate identity/rank state;
- `Modules/Admin` remains the authenticated shell and no longer contains an independent Affiliate implementation.

This is a pragmatic current ownership boundary, not a claim that Affiliate can never become a dedicated module later.

## Canonical reachable runtime

The active route remains:

```text
GET|HEAD admin/affiliate -> Modules\Website\Http\Controllers\Admin\AffiliateController@index
```

The route is guarded by `auth:admin` and `permission:affiliate.view,admin`. Its view renders the Website-owned CommissionList.

Commission mutations continue to require `affiliate.manage` inside the canonical Website Livewire components. This authorization boundary must not be weakened by compatibility cleanup.

## Runtime changes

### Canonical CommissionList

The Website-owned CommissionList remains the reachable management list and now:

- applies the existing level filter through affiliate `affiliate_level_id` instead of silently ignoring it;
- supports only bounded page sizes `10 / 25 / 50 / 100`;
- resets pagination when status, level, search or page-size filters change;
- exposes an explicit reset-filter action;
- eager-loads order items required by the current table/modal presentation;
- uses a dedicated bounded Admin pagination view with white inactive controls and indigo active state;
- keeps approve/reject mutation authorization through `affiliate.manage`.

The UI was aligned with `.codex/standards/ADMIN_UI_STANDARD.md`: visible input/select borders, responsive table overflow, bounded pagination, clear filter controls, accessible button labels, and loading-disabled mutation actions.

### Legacy Admin compatibility adapters

The following historical Admin duplicates no longer contain independent Affiliate business or persistence logic:

- `Modules/Admin/Livewire/Affiliate/CommissionList.php`;
- `Modules/Admin/Livewire/Affiliate/CommissionMatrix.php`;
- `Modules/Admin/Services/AdminAffiliateService.php`;
- `Modules/Admin/Services/AffiliateRankService.php`;
- `Modules/Admin/Models/AffiliateScheme.php`.

They are deprecated compatibility adapters extending their Website canonical equivalents. The broken historical dependency on nonexistent `Modules\Admin\Models\AffiliateLevel` is no longer part of those implementations.

They were deliberately not deleted because repository/static caller search cannot prove every dynamic/external Livewire/class caller absent.

## CommissionMatrix decision

Website CommissionMatrix remains `KEEP` as the canonical commission-scheme component, but direct static route/view reachability was not proven in this slice. It remains a complete Product × AffiliateLevel/User configuration component and all mutations are protected by `affiliate.manage`.

The Admin duplicate is only a deprecated adapter.

This slice does **not** redesign or paginate CommissionMatrix. Its schemes are scoped to one product and changing that UI without proven active reachability would increase regression surface without advancing the ownership objective. Any scalability/UI redesign is separate follow-up debt.

## Schema and data decision

No schema, migration, foreign-key or production-data change is authorized or included.

Existing Affiliate schema/migrations remain where they are today. In particular, the Website-owned AffiliateLevel migration is not moved merely to make repository ownership visually cleaner. Runtime ownership cleanup does not authorize migration-history changes.

Order commission state and User/Product ownership are also not moved into Website.

## Verification completed so far

```text
AdminAffiliateOwnershipContractTest + AdminOwnershipBoundaryContractTest: 9 passed, 51 assertions
admin.affiliate route ownership: PASS — Website controller
working tree after focused verification: clean
```

`tests/Feature/Admin/AdminAffiliateOwnershipContractTest.php` protects:

- Website route ownership and `affiliate.view` route permission;
- `affiliate.manage` mutation authorization;
- Admin Affiliate compatibility-adapter boundaries;
- bounded `10/25/50/100` pagination;
- canonical level filtering;
- no migration of AffiliateLevel schema into Admin.

## Acceptance criteria

- active `/admin/affiliate` route owner: **Website — VERIFIED**;
- route view permission: **`affiliate.view` — PRESERVED**;
- mutation permission: **`affiliate.manage` — PRESERVED**;
- canonical CommissionList: **Website — VERIFIED**;
- level filter: **FUNCTIONAL**;
- pagination standard: **10 / 25 / 50 / 100 — IMPLEMENTED**;
- Admin Affiliate independent business logic: **REMOVED**;
- Admin Affiliate adapters deleted: **NO — compatibility retained pending caller proof**;
- Website CommissionMatrix: **KEEP canonical / static reachability unproven**;
- CommissionMatrix redesign: **OUT OF SCOPE**;
- Affiliate module creation: **NO**;
- commission approve/reject business-rule redesign: **NO**;
- schema/migration/data changes: **NONE**;
- focused ownership regression: **PASS — 9 tests / 51 assertions**;
- final UI/build verification: **PENDING**.

## Material risks and follow-up debt

### Financial/authz boundary

Affiliate approve/reject mutates commission state on Order and approve triggers rank recalculation. Future service movement must preserve transactional behavior and `affiliate.manage` authorization. Do not treat these as presentation-only operations.

### Compatibility adapters

The five deprecated Admin Affiliate adapters remain until dynamic/external caller proof is strong enough for deletion. Their existence does not restore canonical Admin ownership.

### CommissionMatrix reachability/scalability

No direct static caller was found for CommissionMatrix. Do not delete it from that evidence alone. If it becomes a confirmed active management surface and per-product schemes grow materially, assess pagination/search UX separately.

### Future dedicated Affiliate module

A dedicated Affiliate module may become appropriate if the domain grows. That would require a separate architecture decision covering Order commission state, User rank state, Product schemes, permissions, migrations and production migration ledger. It is explicitly not part of this cleanup.

### Remaining Admin compatibility debt

Environment/System settings compatibility adapters and previously deprecated Banner/Header/Flash Sale adapters remain separate caller-proof cleanup debt.

## Next phase

Complete the final focused regression, route/build check and manual UI verification for `/admin/affiliate`. If all gates pass, update this checkpoint to PR-ready and open the PR. Do not begin another Admin legacy family until this branch is merged and the next scope is explicitly approved.
