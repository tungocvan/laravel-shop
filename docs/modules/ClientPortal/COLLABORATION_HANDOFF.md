# ClientPortal Module — Collaboration Handoff

- Last updated: 2026-08-28
- Repository: `tungocvan/laravel-shop`
- Base branch: `main`
- Active feature branch: `feat/clientportal-portal-architecture-foundation`
- Pull request: **NOT CREATED**
- Branch status: **IMPLEMENTATION COMPLETE / PRE-PR CLEANLINESS PENDING**
- Base checkpoint: `85c400d8489f974f371178d6ede5afdd9b1b7a53`
- Current MR: **MR-1 — Portal Architecture Foundation**
- Next planned MR: **MR-2 — Adaptive Navigation**

## Goal

Establish an open ClientPortal architecture that can host multiple Client applications without adding Module-specific logic to Portal core. The Portal must support users who may have zero, one or many available applications, while authorization remains permission-driven and domain business rules remain owned by source Modules.

Core rule:

> Không được thêm logic đặc thù Module vào ClientPortal core. A new Client application should integrate through manifest/contracts, permissions and adapters rather than new `if application == ...` branches in the Portal shell/home.

## Implemented scope

### Application manifest contract

`Modules/ClientPortal/Services/ApplicationRegistry.php` now normalizes a stable UI/application contract with:

```text
key
module
name
description
icon
route
permission
sort_order
layout
capabilities
quick_actions
navigation
features
```

Backward compatibility is retained for manifests that only define `features`; navigation can be derived from those features.

Supported layout modes:

```text
standard
workspace
focus
full-width
```

Navigation supports:

```text
primary
more
```

### Portal resolvers

Added:

```text
Modules/ClientPortal/Services/PortalAccessResolver.php
Modules/ClientPortal/Services/PortalContextResolver.php
Modules/ClientPortal/Services/PortalNavigationResolver.php
```

`PortalContextResolver` provides readiness for zero/one/multiple applications through:

```text
applications
application_count
single_application
requires_application_selection
has_access
```

MR-1 intentionally does **not** auto-redirect single-application users. The 0/1/N application UX belongs to the later Dynamic Portal Home phase.

### Explicit application contracts

Updated:

```text
Modules/ClientPortal/Applications/Muasamcong/manifest.php
Modules/ClientPortal/Applications/Request/manifest.php
```

Both applications now explicitly declare layout, capabilities, quick actions and navigation. This demonstrates that different domain Modules can use the same ClientPortal contract without Portal-core special cases.

### App Shell foundation

`Modules/ClientPortal/resources/views/layouts/application.blade.php` no longer loads application-specific `partials.mobile-nav` files for mobile navigation.

The shell resolves application navigation through `PortalNavigationResolver` and renders `primary` plus `more` navigation generically.

Existing Muasamcong-specific queue-status and price-list presentation logic predates this MR and remains in place. Moving those remaining application-specific presentation concerns out of the shared shell is deferred to the Muasamcong migration/refactor phase rather than being broadened inside MR-1.

### Portal controller

`Modules/ClientPortal/Http/Controllers/PortalController.php` now obtains available applications through `PortalContextResolver` instead of reading the registry directly.

Launcher behavior is otherwise retained in MR-1.

### Client UI / architecture standard

Added:

```text
.codex/standards/CLIENT_APP_UI_STANDARD.md
```

The standard defines ClientPortal ownership boundaries, responsive shell direction, manifest-driven navigation/capabilities, PWA/private-data safety, permissions, accessibility, domain separation and Impact-Based Testing.

## Permission model clarified during manual acceptance

Client application visibility permissions and domain-operation permissions remain separate layers.

For Request, permissions such as:

```text
client.request.create.view
client.request.mine.view
client.request.inbox.view
```

control whether the corresponding ClientPortal feature/navigation is presented. Domain permissions such as `request.instance.create` continue to control whether the underlying business operation is actually authorized.

Therefore, if an administrator intentionally grants `client.request.create.view` without `request.instance.create`, the `Tạo đề nghị` navigation may be visible while the protected domain operation returns `403`. This is accepted behavior for the current permission model, not a Portal navigation defect.

A temporary all-of permission experiment introduced during acceptance was fully reverted after this clarification. Final focused and ClientPortal regression tests passed after the revert.

## Test correction discovered during MR-1

`tests/Feature/ClientApps/ClientPwaFoundationTest.php` contained a stale Website-footer assertion expecting the PWA installer to be included directly by:

```text
Modules/Website/resources/views/partials/footer.blade.php
```

Current Website architecture renders the installer through the footer component/slot path and eventually:

```text
Modules/Website/resources/views/components/footer/app-install.blade.php
→ Website::partials.pwa-installer
```

The test was aligned to the current runtime architecture. Website runtime code was not changed.

## Verification evidence

Final owner-executed focused verification:

```text
php artisan test tests/Feature/ClientApps/ClientApplicationRegistryTest.php
PASS — 9 tests, 40 assertions
```

Final owner-executed ClientPortal regression:

```text
php artisan test tests/Feature/ClientApps
PASS — 70 tests, 490 assertions
```

An implementation defect was detected by the focused test before wider regression:

```text
Undefined array key "placement"
```

in `ApplicationRegistry::normalizeNavigation()` when a navigation item omitted `placement`. It was corrected by normalizing the default before validation. Focused and module regression tests passed after the fix.

Impact-Based Testing result:

```text
Focused ClientPortal contract tests: PASS
ClientPortal/ClientApps regression: PASS
Unrelated project-wide regression: NOT RUN / NOT REQUIRED AT THIS checkpoint
```

## Manual UI acceptance

Owner-verified manual UI smoke:

```text
Muasamcong mobile / iPhone 15 Pro Max 430px: PASS
- bottom navigation rendered
- Tổng quan active state rendered
- Tra cứu / Bảng giá / Lịch sử rendered
- Thêm group rendered

Request mobile / 430px: PASS
- bottom navigation rendered
- Tổng quan active state rendered
- Tạo đề nghị / Của tôi / Phê duyệt / Thêm rendered according to granted ClientPortal view permissions

Representative 1024px viewport: PASS
- mobile bottom navigation hidden
- Request workspace rendered without visible overflow/breakage

Permission behavior: PASS / MODEL CONFIRMED
- ClientPortal view permission controls presentation
- domain permission independently enforces the business operation
- an intentionally visible Request feature can return 403 when its domain-operation permission is not granted
```

Manual UI smoke: **PASS**.

## Scope intentionally deferred

MR-1 does not implement:

- automatic direct entry when a user has exactly one application;
- new Work Home behavior for zero/one/multiple applications;
- tablet navigation rail;
- desktop application sidebar;
- full shared action-sheet/filter/search component library;
- migration of all Muasamcong-specific shell presentation out of the shared layout;
- general offline caching of authenticated business content;
- organization/department/branch schema or hardcoded role-based routing.

These remain follow-up work, with MR-2 expected to focus on adaptive navigation and later MRs on Dynamic Portal Home and application migrations.

## Architecture boundaries

ClientPortal owns:

```text
Portal context
Application discovery contract
Access/navigation/capability resolution
Shared App Shell and Client UI primitives
PWA presentation/runtime boundaries
```

Application adapters own:

```text
Application manifest
Application routes/controllers/presentation
Mapping into domain services
```

Domain Modules own:

```text
Business rules
Data ownership
Scope enforcement
Domain services and persistence
```

Roles are permission collections, not Portal routing conditions. Department/organization concepts must not be hardcoded into ClientPortal navigation. Any future scope model must be introduced through explicit domain/context contracts.

## Production safety boundary

MR-1 does not:

- enable or disable any Module;
- change runtime Module-state storage;
- migrate/reset databases;
- seed/delete production data;
- change production role assignments;
- deploy/rebuild containers;
- enable private authenticated response caching;
- alter Muasamcong or Request business-domain rules.

## Remaining pre-PR gates

```text
Focused tests: PASS
ClientPortal regression: PASS
Manual UI smoke: PASS
Git diff/check cleanliness: PENDING
Local working-tree cleanliness: PENDING
PR review: NOT STARTED
```

## Next authorized step

1. Run narrow Git cleanliness checks on the feature branch.
2. Refresh this handoff with final Git-clean evidence.
3. Create the MR-1 pull request for review.
4. Do not merge until owner acceptance and PR gates are satisfied.
5. After merge, update the stable handoff with the actual merge checkpoint before beginning MR-2.
