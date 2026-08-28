# ClientPortal Module — Collaboration Handoff

- Last updated: 2026-08-28
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Stable checkpoint: `3069ac381189f307fedf197700a7e02df29d8936`
- Completed MR: **MR-1 — Portal Architecture Foundation**
- Pull request: **#60 — MERGED**
- Merge commit: `3069ac381189f307fedf197700a7e02df29d8936`
- MR-1 status: **CLOSED / ACCEPTED**
- Next planned MR: **MR-2 — Adaptive Navigation**
- MR-2 implementation status: **NOT STARTED / PLAN APPROVAL REQUIRED**

## Stable architecture after MR-1

ClientPortal is an open authenticated Client/WebApp platform that can host multiple applications without adding Module-specific business logic to Portal core.

Core rule:

> Không được thêm logic đặc thù Module vào ClientPortal core.

A new Client application integrates through manifest/contracts, permissions and adapters rather than new application-specific conditions in the shared Portal shell/home.

## MR-1 delivered scope

### Application manifest contract

`Modules/ClientPortal/Services/ApplicationRegistry.php` now normalizes the shared application/UI contract:

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

Backward compatibility is retained for manifests that only define `features`; navigation may be derived from features.

Supported layout modes:

```text
standard
workspace
focus
full-width
```

Navigation placement supports:

```text
primary
more
```

### Portal resolvers

MR-1 added:

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

MR-1 deliberately does not implement automatic single-application redirect or the final 0/1/N Work Home UX.

### Application contracts

The Muasamcong and Request applications now use the same explicit ClientPortal contract through:

```text
Modules/ClientPortal/Applications/Muasamcong/manifest.php
Modules/ClientPortal/Applications/Request/manifest.php
```

Both declare layout, capabilities, quick actions and navigation without ClientPortal-core special cases.

### Shared App Shell foundation

`Modules/ClientPortal/resources/views/layouts/application.blade.php` no longer loads application-specific `partials.mobile-nav` files for mobile navigation.

The shared shell resolves navigation through `PortalNavigationResolver` and renders `primary` plus `more` navigation generically.

Existing Muasamcong-specific queue-status and price-list presentation logic predates MR-1 and remains deferred for a later migration/refactor phase.

### Portal controller

`Modules/ClientPortal/Http/Controllers/PortalController.php` now obtains application availability through `PortalContextResolver`.

Launcher behavior is otherwise retained.

### Client UI / architecture standard

MR-1 added:

```text
.codex/standards/CLIENT_APP_UI_STANDARD.md
```

The standard defines ClientPortal ownership boundaries, responsive shell direction, manifest-driven navigation/capabilities, PWA/private-data safety, permission boundaries, accessibility, domain separation and Impact-Based Testing.

## Permission model confirmed

ClientPortal presentation permissions and domain-operation permissions are separate layers.

For Request, permissions such as:

```text
client.request.create.view
client.request.mine.view
client.request.inbox.view
```

control whether the corresponding ClientPortal feature/navigation is presented.

Domain permissions such as:

```text
request.instance.create
```

remain authoritative for the underlying business operation.

Therefore, an administrator may intentionally grant `client.request.create.view` without `request.instance.create`; in that case `Tạo đề nghị` may be visible while the protected domain operation returns `403`. This is accepted behavior under the current permission model.

A temporary all-of permission experiment introduced during MR-1 acceptance was reverted before merge.

## Verification evidence for merged MR-1

Final focused verification:

```text
php artisan test tests/Feature/ClientApps/ClientApplicationRegistryTest.php
PASS — 9 tests, 40 assertions
```

Final ClientPortal regression:

```text
php artisan test tests/Feature/ClientApps
PASS — 70 tests, 490 assertions
```

Impact-Based Testing result:

```text
Focused ClientPortal contract tests: PASS
ClientPortal/ClientApps regression: PASS
Unrelated project-wide regression: NOT RUN / NOT REQUIRED for MR-1 checkpoint
```

Git cleanliness before PR:

```text
git diff --check main...HEAD
PASS / no output

git status --short
PASS / no output
```

GitHub PR readiness before merge:

```text
PR #60: OPEN / non-draft / mergeable
GitHub CI status checks: none configured/returned for the PR head
```

## Manual UI acceptance for MR-1

Owner-verified smoke evidence:

```text
Muasamcong mobile / iPhone 15 Pro Max 430px: PASS
- bottom navigation rendered
- active state rendered
- primary navigation rendered
- Thêm/more group rendered

Request mobile / 430px: PASS
- bottom navigation rendered
- navigation followed granted ClientPortal presentation permissions

Representative 1024px viewport: PASS
- mobile bottom navigation hidden
- workspace rendered without visible overflow/breakage

Permission behavior: PASS / MODEL CONFIRMED
- ClientPortal view permission controls presentation
- domain permission independently enforces business operations
```

## Merge closeout

MR-1 was merged through PR #60.

```text
Feature head before merge:
a576c74d95594d36548fd8c00e3859bab05aa2c4

Merge commit / stable main checkpoint:
3069ac381189f307fedf197700a7e02df29d8936
```

GitHub `main` was verified at the merge checkpoint after merge. Local `main` was then switched and fast-forwarded from `85c400d8` to `3069ac38`, bringing all 12 MR-1 changed files into the local stable branch.

MR-1 is therefore closed. Do not continue implementation on `feat/clientportal-portal-architecture-foundation`.

## Production safety boundary

MR-1 did not:

- enable or disable any Module;
- change runtime Module-state storage;
- migrate/reset databases;
- seed/delete production data;
- change production role assignments;
- deploy/rebuild containers;
- enable private authenticated response caching;
- alter Muasamcong or Request domain authorization rules.

## Deferred roadmap

Still deferred after MR-1:

- automatic direct entry when a user has exactly one application;
- new Work Home behavior for zero/one/multiple applications;
- tablet navigation rail;
- desktop application sidebar;
- full shared action-sheet/filter/search component library;
- migration of remaining Muasamcong-specific shell presentation concerns;
- general offline caching of authenticated business content;
- organization/department/branch schema or hardcoded role-based routing.

Current roadmap order:

```text
MR-1 — Portal Architecture Foundation: MERGED
MR-2 — Adaptive Navigation: NEXT PLANNED
MR-3 — Dynamic Portal Home: PLANNED
MR-4 — Muasamcong reference migration: PLANNED
```

## Next-step boundary

MR-2 is the next planned phase, but implementation is **not yet authorized by this handoff alone**.

Before creating an MR-2 branch or changing code:

1. Read this handoff and `.codex/standards/CLIENT_APP_UI_STANDARD.md` from current `main`.
2. Inspect the current shared App Shell/navigation source at the stable checkpoint.
3. Define a narrow MR-2 Adaptive Navigation scope and test/UI acceptance plan.
4. Present the plan to the owner.
5. Create a new feature branch and implement only after explicit owner approval.

Do not broaden MR-2 into Dynamic Portal Home, application migration, or unrelated domain behavior without a separately approved scope.
