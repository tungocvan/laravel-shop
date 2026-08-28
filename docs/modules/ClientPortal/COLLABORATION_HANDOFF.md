# ClientPortal Module — Collaboration Handoff

- Last updated: 2026-08-28
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Stable main checkpoint before MR-3: `b5f377319419bd2c9eed1516f101e99675338158`
- Current MR: **MR-3 — Dynamic Portal Home**
- Feature branch: `feat/clientportal-dynamic-portal-home`
- MR-3 status: **IMPLEMENTED / AUTOMATED TESTS PASS / MANUAL ACCEPTANCE PASS / PR READY**
- Merge status: **NOT MERGED — OWNER APPROVAL REQUIRED**

## Stable architecture

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

`PortalContextResolver` is the source of truth for Dynamic Portal Home availability through `applications`, `application_count`, `single_application`, `requires_application_selection`, and `has_access`.

## MR-3 delivered scope

MR-3 implements the previously deferred 0/1/N Portal Home behavior at the canonical `/my-apps` / `client.apps.index` entry point without introducing a new route.

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

Manual acceptance therefore confirms the core MR-3 contract:

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

## Explicitly deferred / out of MR-3

Still outside MR-3:

- MR-4 Muasamcong reference migration;
- Muasamcong-specific queue-status or price-list refactoring;
- Request authentication/domain authorization changes;
- shared action-sheet/filter/search component library;
- organization/department/branch context or hardcoded role routing;
- general offline caching of authenticated business content;
- unrelated runtime/production changes.

## Roadmap checkpoint

```text
MR-1 — Portal Architecture Foundation: MERGED / CLOSED
MR-2 — Adaptive Navigation: MERGED / CLOSED
MR-3 — Dynamic Portal Home: IMPLEMENTED / ACCEPTED / PR READY
MR-4 — Muasamcong reference migration: PLANNED — NOT STARTED
```

## PR and merge gate

MR-3 implementation and owner acceptance are complete on `feat/clientportal-dynamic-portal-home`.

Before merge:

1. Review the final feature-branch diff against current `main`.
2. Confirm the branch is not behind `main` and contains only approved MR-3 scope.
3. Open/review the MR-3 pull request and inspect GitHub mergeability/check status.
4. Merge only after explicit owner approval.
5. After merge, verify the resulting `main` checkpoint and update this handoff to CLOSED / ACCEPTED with the PR and merge commit.

Do not begin MR-4 from the MR-3 feature branch.