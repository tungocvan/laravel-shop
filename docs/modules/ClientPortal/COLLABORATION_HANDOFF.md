# ClientPortal Module — Collaboration Handoff

- Last updated: 2026-08-28
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- MR-3 merge commit / stable code checkpoint: `35aa90bf771869c4eb0cf065eac635f8137b1b95`
- Completed MR: **MR-3 — Dynamic Portal Home**
- Pull request: **#62 — MERGED**
- Feature branch: `feat/clientportal-dynamic-portal-home`
- Feature head before merge: `e8d33a75b0ca561cf427e2a9fc65581d3742976c`
- MR-3 status: **CLOSED / ACCEPTED**
- Next planned MR: **MR-4 — Muasamcong reference migration**
- MR-4 implementation status: **NOT STARTED / PLAN APPROVAL REQUIRED**

## Stable architecture after MR-3

ClientPortal remains an open authenticated Client/WebApp platform that can host multiple applications without adding Module-specific business logic to Portal core.

Core rule:

> Không được thêm logic đặc thù Module vào ClientPortal core.

Applications integrate through manifest/contracts, permissions and adapters rather than application-specific conditions in the shared Portal shell/home.

The shared application contract remains normalized by `Modules/ClientPortal/Services/ApplicationRegistry.php`, including `key`, `module`, `name`, `description`, `icon`, `route`, `permission`, `sort_order`, `layout`, `capabilities`, `quick_actions`, `navigation`, and `features`.

The stable shared resolvers remain:

```text
Modules/ClientPortal/Services/PortalAccessResolver.php
Modules/ClientPortal/Services/PortalContextResolver.php
Modules/ClientPortal/Services/PortalNavigationResolver.php
```

`PortalContextResolver` is the source of truth for Portal Home availability through `applications`, `application_count`, `single_application`, `requires_application_selection`, and `has_access`.

## MR-3 delivered scope

MR-3 implements the 0/1/N Portal Home behavior at the canonical `/my-apps` / `client.apps.index` entry point without introducing a new route.

```text
0 applications  -> Work Home / clear no-access state
1 application   -> generic direct entry to the application's manifest route
N applications  -> permission-filtered Work Home / application selection
```

`Modules/ClientPortal/Http/Controllers/PortalController.php` resolves Portal context once. When exactly one application is available, it redirects generically through the `route` declared by `single_application`; it does not branch on an application or Module name.

For zero or multiple applications, `Modules/ClientPortal/resources/views/pages/apps.blade.php` renders the shared Work Home. The zero state preserves a clear no-access message, while the multiple state presents the available application count and application cards.

The implementation does not add role-based routing, named Module conditions, domain queries, or domain authorization logic to ClientPortal core.

## Backward compatibility

The intentional MR-3 behavior change is limited to users with exactly one available application:

```text
Before MR-3: /my-apps -> launcher -> user clicks application
After MR-3:  /my-apps -> direct application entry
```

Users with zero or multiple available applications continue to use `/my-apps` as Work Home. Existing application routes, deep links, MR-2 App Shell behavior, adaptive navigation, application manifests and domain authorization boundaries remain unchanged.

## Automated verification for MR-3

Focused Dynamic Portal Home coverage was added in:

```text
tests/Feature/ClientApps/ClientPortalDynamicHomeTest.php
```

It verifies:

- zero applications render the no-access Work Home;
- exactly one application redirects through the manifest route;
- multiple applications render Work Home without redirect;
- shared Dynamic Home source remains free of named business-application branching and role-based UI conditions.

An initial anti-hardcode assertion produced a false positive because the framework controller legitimately imports `Illuminate\\Http\\Request`. The assertion was corrected to detect business-specific Request conditions without banning the framework class; no implementation behavior was changed by that correction.

Final ClientPortal/ClientApps regression reported by the owner:

```text
php artisan test tests/Feature/ClientApps
PASS — 79 tests, 532 assertions
```

Impact-Based Testing result:

```text
Focused Dynamic Portal Home tests: PASS
ClientPortal/ClientApps regression: PASS — 79 tests, 532 assertions
Unrelated project-wide regression: NOT RUN / NOT REQUIRED for MR-3 checkpoint
```

## Manual UI acceptance for MR-3

Owner-verified 0/1/N behavior:

```text
0 applications: PASS
- /my-apps renders the clear no-access Work Home
- no HTTP 403/500 observed
- responsive presentation verified at 430x932, 768x1024 and 1024x1366
- header/logout and empty-state presentation remain usable
- no visible horizontal overflow or content overlap observed

1 application: PASS
- /my-apps enters the single available application directly
- no intermediate application launcher is shown
- direct-entry behavior matches the approved MR-3 contract

2 applications: PASS
- /my-apps renders Work Home/application selection
- Mua sắm công and Đề nghị & Phê duyệt were presented for the test account
- mobile presentation stacks cards vertically
- tablet/desktop presentation uses the available responsive grid
- no visible horizontal overflow or content overlap observed
```

Manual acceptance therefore confirms:

```text
0 app  -> Work Home / no-access       PASS
1 app  -> Direct entry                PASS
N apps -> Work Home / app selection   PASS
Responsive 430 / 768 / 1024           PASS
```

## MR-2 preserved behavior

MR-3 does not replace or duplicate MR-2 adaptive navigation. Application pages continue to use the shared adaptive navigation contract:

```text
mobile < sm      -> bottom navigation
sm to < lg       -> compact tablet navigation rail
lg and above     -> desktop application sidebar
```

Permission-filtered navigation, `primary` / `more` placement, active-route state, manifest-driven icons and the ClientPortal-neutral App Shell remain owned by the merged MR-2 implementation.

## Permission and domain boundary

ClientPortal presentation permissions and domain-operation permissions remain separate layers. MR-3 only decides how the user enters or selects an application from the permission-filtered application collection.

Domain services and permissions remain authoritative for business operations. MR-3 does not alter Request authorization, Muasamcong behavior, application domain scope, role assignments or Module enablement.

## Production safety boundary

MR-3 did not:

- enable or disable any Module;
- change runtime Module-state storage;
- migrate/reset databases;
- add or modify schema/seeders;
- change production role assignments;
- deploy/rebuild containers;
- enable private authenticated response caching;
- alter Muasamcong or Request domain authorization rules;
- add organization/department/branch routing.

## Explicitly deferred after MR-3

Still outside MR-3:

- MR-4 Muasamcong reference migration;
- Muasamcong-specific queue-status or price-list refactoring;
- Request authentication/domain authorization changes;
- shared action-sheet/filter/search component library;
- organization/department/branch context or hardcoded role routing;
- general offline caching of authenticated business content;
- unrelated runtime/production changes.

## Merge closeout

MR-3 was merged through PR #62 after explicit owner approval.

```text
Feature head before merge:
e8d33a75b0ca561cf427e2a9fc65581d3742976c

Merge commit / stable code checkpoint:
35aa90bf771869c4eb0cf065eac635f8137b1b95
```

MR-3 is therefore **CLOSED / ACCEPTED**. Do not continue implementation on `feat/clientportal-dynamic-portal-home`.

## Roadmap checkpoint

```text
MR-1 — Portal Architecture Foundation: MERGED / CLOSED
MR-2 — Adaptive Navigation: MERGED / CLOSED
MR-3 — Dynamic Portal Home: MERGED / CLOSED
MR-4 — Muasamcong reference migration: NEXT PLANNED
```

## Next-step boundary

MR-4 — Muasamcong reference migration is the next planned phase, but implementation is **not authorized by this handoff alone**.

Before creating an MR-4 branch or changing code:

1. Read this handoff and `.codex/standards/CLIENT_APP_UI_STANDARD.md` from current `main`.
2. Inspect the current Muasamcong ClientPortal adapter, manifest, App Shell integration and remaining Muasamcong-specific presentation concerns.
3. Define a narrow MR-4 migration scope with explicit domain/ClientPortal ownership boundaries, testing and manual acceptance.
4. Present the plan to the owner.
5. Create a new feature branch and implement only after explicit owner approval.

Do not broaden MR-4 into Request domain behavior, organization/department routing, unrelated runtime/production changes, or speculative shared component work without separately approved scope.