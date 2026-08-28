# ClientPortal Module — Collaboration Handoff

- Last updated: 2026-08-28
- Repository: `tungocvan/laravel-shop`
- Base branch: `main`
- Active feature branch: `feat/clientportal-portal-architecture-foundation`
- Pull request: **NOT CREATED**
- Branch status: **IMPLEMENTATION COMPLETE / MANUAL UI SMOKE PENDING**
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

Owner-executed focused verification after MR-1 contract/navigation implementation:

```text
php artisan test tests/Feature/ClientApps/ClientApplicationRegistryTest.php
PASS — 9 tests, 40 assertions
```

Owner-executed ClientPortal regression:

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

## Manual UI acceptance still required

Because the shared mobile App Shell navigation changed, manual UI smoke remains required before PR/merge readiness is declared.

Minimum smoke scope:

1. Sign in as a user with Muasamcong access and open a Muasamcong Client page on a mobile-width viewport.
2. Confirm the bottom navigation renders from the manifest, primary items are usable, and the `Thêm`/more group is reachable.
3. Sign in as a user with Request access and repeat the mobile navigation check.
4. Verify a representative desktop Client application page still renders correctly and has no unexpected mobile bottom navigation.
5. Confirm permissions still hide inaccessible navigation items.
6. Confirm existing Muasamcong queue-status/Price List behavior used during normal navigation is not visibly broken.

Manual UI smoke: **PENDING OWNER CONFIRMATION**.

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
Manual UI smoke: PENDING
Git diff/check cleanliness: PENDING
Local working-tree cleanliness: PENDING
PR review: NOT STARTED
```

## Next authorized step

1. Perform the minimum manual UI smoke for Muasamcong + Request navigation and one desktop representative page.
2. If UI smoke passes, run narrow Git cleanliness checks.
3. Refresh this handoff with final gate evidence.
4. Create the MR-1 pull request for review.
5. Do not merge until owner acceptance and PR gates are satisfied.
6. After merge, update the stable handoff with the actual merge checkpoint before beginning MR-2.
