# ClientPortal Module — Collaboration Handoff

- Last updated: 2026-08-28
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Stable checkpoint: `fafd22b4de6157b2a9d274ab1c0d626aac28f059`
- Completed MR: **MR-2 — Adaptive Navigation**
- Pull request: **#61 — MERGED**
- Merge commit: `fafd22b4de6157b2a9d274ab1c0d626aac28f059`
- MR-2 status: **CLOSED / ACCEPTED**
- Next planned MR: **MR-3 — Dynamic Portal Home**
- MR-3 implementation status: **NOT STARTED / PLAN APPROVAL REQUIRED**

## Stable architecture after MR-2

ClientPortal is an open authenticated Client/WebApp platform that can host multiple applications without adding Module-specific business logic to Portal core.

Core rule:

> Không được thêm logic đặc thù Module vào ClientPortal core.

A new Client application integrates through manifest/contracts, permissions and adapters rather than new application-specific conditions in the shared Portal shell/home.

MR-2 preserves the MR-1 architecture foundation and adds a shared adaptive application navigation presentation layer. The same permission-filtered navigation contract is used at every viewport; there is no separate mobile/tablet/desktop navigation source of truth.

## Stable application contract

`Modules/ClientPortal/Services/ApplicationRegistry.php` normalizes the shared application/UI contract:

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

Backward compatibility remains for manifests that only define `features`; navigation may be derived from features.

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

The Muasamcong and Request applications continue to use the same explicit ClientPortal contract through:

```text
Modules/ClientPortal/Applications/Muasamcong/manifest.php
Modules/ClientPortal/Applications/Request/manifest.php
```

## Portal resolver boundary

The stable shared resolvers remain:

```text
Modules/ClientPortal/Services/PortalAccessResolver.php
Modules/ClientPortal/Services/PortalContextResolver.php
Modules/ClientPortal/Services/PortalNavigationResolver.php
```

`PortalNavigationResolver` remains responsible for permission-filtered navigation/quick actions before presentation.

`PortalContextResolver` provides zero/one/multiple application readiness through:

```text
applications
application_count
single_application
requires_application_selection
has_access
```

Automatic single-application entry and the final 0/1/N Work Home UX remain deferred to MR-3.

## MR-2 delivered scope

### One adaptive navigation model

`Modules/ClientPortal/resources/views/layouts/application.blade.php` resolves application navigation once through the shared resolver and delegates presentation to:

```text
Modules/ClientPortal/resources/views/partials/adaptive-navigation.blade.php
```

The same `primary` and `more` collections drive all supported viewport presentations:

```text
mobile < sm      -> bottom navigation
sm to < lg       -> compact tablet navigation rail
lg and above     -> desktop application sidebar
```

No application-specific mobile/tablet/desktop navigation definitions were introduced.

### Manifest-driven navigation icons

MR-2 added the shared ClientPortal-neutral icon partial:

```text
Modules/ClientPortal/resources/views/partials/navigation-icon.blade.php
```

Navigation renders manifest icon tokens through a generic SVG vocabulary and uses a safe generic fallback for unknown/missing icon tokens.

The implementation uses the existing `ClientPortal::` namespaced Blade include convention. An initial unsupported anonymous component namespace was detected during runtime smoke, corrected before merge, and covered by regression testing.

### Navigation presentation behavior

Across viewports, MR-2 preserves:

- permission-filtered navigation from `PortalNavigationResolver`;
- `primary` / `more` placement;
- active-route state and `aria-current="page"`;
- shared application-neutral rendering;
- touch-oriented mobile navigation and safe-area bottom spacing;
- compact tablet rail presentation;
- desktop icon + label sidebar presentation.

No role-based UI branching or Module-specific condition was added to the adaptive navigation core.

## Permission model confirmed

ClientPortal presentation permissions and domain-operation permissions remain separate layers.

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

Therefore, an administrator may intentionally grant `client.request.create.view` without `request.instance.create`; in that case `Tạo đề nghị` may be visible while the protected domain operation returns `403`. This remains accepted behavior under the current permission model.

## Verification evidence for merged MR-2

Final ClientPortal/ClientApps regression:

```text
php artisan test tests/Feature/ClientApps
PASS — 75 tests, 513 assertions
```

Impact-Based Testing result:

```text
Focused adaptive-navigation tests: PASS
ClientPortal/ClientApps regression: PASS — 75 tests, 513 assertions
Unrelated project-wide regression: NOT RUN / NOT REQUIRED for MR-2 checkpoint
```

The focused suite includes source/contract assertions for:

- App Shell delegation to shared adaptive navigation;
- one navigation contract across mobile/tablet/desktop;
- application-neutral navigation rendering;
- manifest-driven icons and generic fallback;
- Blade compilation coverage for the shared adaptive navigation path.

## Manual UI acceptance for MR-2

Owner-verified smoke evidence:

```text
Request mobile / 430x932: PASS
- application rendered without HTTP 500
- bottom navigation rendered
- manifest SVG icons rendered
- active state rendered
- permission-filtered requester/approver navigation verified

Tablet / 768x1024: PASS
- mobile bottom navigation hidden
- compact left navigation rail rendered
- content not overlapped by navigation
- no visible horizontal overflow

Desktop / 1024x1366: PASS
- desktop sidebar rendered with icon + label
- primary and more navigation accessible
- active state rendered
- content/sidebar/header layout rendered without visible overlap
```

A runtime smoke initially exposed:

```text
Unable to locate a class or view for component [client-portal::navigation-icon]
```

This was corrected before merge by replacing the unsupported anonymous component namespace with the established `ClientPortal::partials.navigation-icon` include convention. Runtime Request smoke and the full ClientApps regression then passed.

## Merge closeout

MR-2 was merged through PR #61.

```text
Feature head before merge:
0b1ca00978be0a80852098ca601166526c62925e

Merge commit / stable main checkpoint:
fafd22b4de6157b2a9d274ab1c0d626aac28f059
```

GitHub `main` was verified at `fafd22b4de6157b2a9d274ab1c0d626aac28f059` after merge.

MR-2 is therefore closed. Do not continue implementation on `feat/clientportal-adaptive-navigation`.

## Production safety boundary

MR-2 did not:

- enable or disable any Module;
- change runtime Module-state storage;
- migrate/reset databases;
- seed/delete production data;
- change production role assignments;
- deploy/rebuild containers;
- enable private authenticated response caching;
- alter Muasamcong or Request domain authorization rules;
- implement Dynamic Portal Home behavior.

Existing Muasamcong-specific queue-status and price-list presentation logic predates MR-2 and remains deferred for a later migration/refactor phase.

## Deferred roadmap

Still deferred after MR-2:

- automatic direct entry when a user has exactly one application;
- new Work Home behavior for zero/one/multiple applications;
- full shared action-sheet/filter/search component library;
- migration of remaining Muasamcong-specific shell presentation concerns;
- general offline caching of authenticated business content;
- organization/department/branch schema or hardcoded role-based routing.

Adaptive tablet navigation rail and desktop application sidebar are no longer deferred; they were delivered by MR-2.

Current roadmap order:

```text
MR-1 — Portal Architecture Foundation: MERGED
MR-2 — Adaptive Navigation: MERGED
MR-3 — Dynamic Portal Home: NEXT PLANNED
MR-4 — Muasamcong reference migration: PLANNED
```

## Next-step boundary

MR-3 — Dynamic Portal Home is the next planned phase, but implementation is **not authorized by this handoff alone**.

Before creating an MR-3 branch or changing code:

1. Read this handoff and `.codex/standards/CLIENT_APP_UI_STANDARD.md` from current `main`.
2. Inspect the current `PortalContextResolver`, portal controller/home/launcher source and the merged MR-2 App Shell/navigation behavior.
3. Define a narrow MR-3 Dynamic Portal Home scope for zero/one/multiple application states, including test/UI acceptance and backward-compatibility boundaries.
4. Present the plan to the owner.
5. Create a new feature branch and implement only after explicit owner approval.

Do not broaden MR-3 into application migration, Muasamcong domain behavior, Request domain authorization, organization/department routing, or unrelated runtime/production changes without a separately approved scope.
