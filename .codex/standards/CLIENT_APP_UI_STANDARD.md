# Client App UI & Architecture Standard

## 1. Purpose

This standard defines the contract for authenticated ClientPortal/WebApp experiences used by customers, collaborators, employees, approvers and other web-guard users.

ClientPortal is a shared work portal. It must not be coupled to one business module, one department or one user type.

## 2. Core architecture boundary

ClientPortal owns:

- portal entry and authenticated work-home experience;
- application discovery and presentation;
- application shell;
- adaptive navigation;
- shared interaction primitives;
- PWA presentation/runtime integration;
- permission-aware UI visibility.

Business/domain modules own:

- domain models and persistence;
- domain rules and policies;
- business services;
- business jobs and side effects;
- authoritative authorization and data scope enforcement.

Required flow:

`Client UI -> Client application adapter/service -> Domain public service -> Domain data`

ClientPortal must not duplicate domain rules.

## 3. Open application contract

A client application is registered through `Modules/ClientPortal/Applications/{Application}/manifest.php`.

Adding a new application must not require application-specific conditions in ClientPortal core.

Forbidden in portal core:

```php
if ($application === 'muasamcong') {
    // application-specific behavior
}
```

Differences between applications must be expressed through manifest metadata, permissions, capabilities, policies or adapter services.

The normalized application contract supports:

- `key`
- `source_module`
- `name`
- `description`
- `icon`
- `route`
- `permission`
- `sort_order`
- `features`
- `navigation`
- `quick_actions`
- `capabilities`
- `layout`

Existing manifests remain valid. When explicit navigation is absent, ClientPortal may derive navigation from routable features.

## 4. Module and application are not the same concept

A domain module may expose zero, one or multiple client applications.

ClientPortal discovers applications, not domain internals.

A module disabled by runtime module state must not expose an available client application.

Availability is resolved in this order conceptually:

`Module enabled -> Application registered -> Application permission -> Feature/action permission -> Domain scope`

UI visibility never replaces server-side authorization.

## 5. Portal context

Authenticated presentation should consume a resolved portal context instead of independently querying roles, permissions and modules inside Blade views.

The portal context is responsible for stable presentation inputs such as:

- current user identity reference;
- available applications;
- application count;
- single-application candidate;
- whether application selection is required.

Future organization, branch, department or workspace context may be added without changing individual feature pages.

Do not hardcode department or role names in navigation logic.

## 6. Roles, permissions and scope

Role names are administrative groupings, not UI conditions.

Do not write presentation logic such as:

```php
if ($user->hasRole('Sales')) { ... }
```

Prefer capability/permission checks.

Recommended permission layers:

1. application access, e.g. `client.orders.access`;
2. feature/action permissions, e.g. `client.orders.create`;
3. domain scope, e.g. own/department/branch/organization/all, enforced by the domain authorization layer.

## 7. Adaptive application shell

One navigation model must support multiple viewport presentations.

- Mobile: bottom navigation, sheets, cards, sticky actions.
- Tablet: navigation rail where appropriate.
- Desktop: compact sidebar/header and denser workspace presentation.

Applications must not maintain separate business navigation definitions for mobile/tablet/desktop.

Application layout modes are limited to portal-controlled modes:

- `standard`
- `workspace`
- `focus`
- `full-width`

An application may select a supported mode but must not replace the global shell arbitrarily.

## 8. Navigation contract

Navigation metadata should provide, at minimum:

- stable `key`;
- user-facing `name`;
- named `route`;
- optional `permission`;
- `icon` token;
- `sort_order`;
- `placement` (`primary` or `more`).

Permission filtering is resolved before rendering.

Mobile primary navigation should normally contain no more than five destinations. Secondary destinations belong in `more` or contextual actions.

## 9. Quick actions and capabilities

Applications may expose permission-aware quick actions for portal home or application dashboards.

Capabilities describe reusable UI/runtime needs such as:

- `search`
- `filter`
- `camera`
- `upload`
- `share`
- `export`
- `background-jobs`

A capability is presentation metadata. It must not grant authorization.

## 10. Client home behavior

The authenticated portal entry must support three states:

- no available applications: show a clear no-access state;
- one available application: architecture must support direct entry without forcing an unnecessary launcher choice;
- multiple applications: show a work-home/application selection experience.

Behavior changes such as automatic single-app redirect must be introduced intentionally with tests and backward-compatibility review.

## 11. Shared UI primitives

Prefer shared ClientPortal primitives instead of application-local duplicates for:

- AppShell
- AppHeader
- BottomNavigation / NavigationRail
- PageHeader
- SearchBar
- FilterSheet
- ActionSheet
- StatusBadge
- EntityCard
- EmptyState
- ErrorState
- Skeleton
- Toast
- BackgroundJobStatus
- ConfirmDialog
- StickyActionBar
- Camera/FileUpload primitives

Do not build unused component libraries speculatively. Introduce a primitive when an active MR needs it and keep its contract application-neutral.

## 12. PWA and responsive requirements

Client pages must be mobile-first and safe for installed PWA use.

Required considerations:

- `viewport-fit=cover`;
- safe-area insets for fixed headers/actions/navigation;
- use dynamic viewport sizing when viewport height matters;
- touch targets generally at least 44-48px;
- no hover-only required actions;
- clear loading, empty, error and offline states;
- deterministic browser back/deep-link behavior.

Authenticated private HTML/API data must not be broadly cached for offline reuse unless a separate security-reviewed offline design explicitly authorizes it.

## 13. Forms and interaction

- Labels must remain visible; placeholders do not replace labels.
- Validation must appear near the related field.
- Use input types appropriate for email, phone, numbers and dates.
- Long mobile forms should use clear primary actions, optionally a portal-controlled sticky action bar.
- Destructive actions require permission-aware visibility and confirmation where appropriate.
- Long-running operations should queue when appropriate and expose durable status rather than blocking the page.

## 14. Icons and design language

Use a consistent SVG icon vocabulary for production UI. Do not use emoji or arbitrary Unicode symbols as the primary production icon system.

Feature pages must not invent an independent design language. Shared semantic tokens/components should control spacing, surfaces, borders, states, radii and typography conventions.

## 15. Testing strategy

ClientPortal changes use impact-based testing.

Order:

1. syntax/static checks for changed files;
2. focused ClientPortal tests for the changed contract/feature;
3. related-module tests only when the change crosses a real integration boundary;
4. broader ClientApps regression at merge checkpoints;
5. full project regression only when required by the repository workflow or impact warrants it.

If a focused test fails, stop before widening the test scope.

UI changes also require targeted manual smoke checks for affected mobile/tablet/desktop states.

## 16. Anti-patterns

Do not:

- rebuild ClientPortal as a SPA merely because it is a PWA;
- couple portal core to a named application or domain module;
- hardcode department/role names into navigation;
- make every application implement its own shell;
- define different navigation truth sources per viewport;
- put domain/DB mutation logic in Blade;
- treat hidden UI as authorization;
- broadly cache authenticated private business content;
- force users with one available application through unnecessary module-selection UX.

## 17. Change rule

Any MR that introduces a new ClientPortal architectural primitive must state:

- why the primitive belongs in portal core;
- how at least one current application consumes it;
- how a future application can consume it without modifying core;
- focused tests proving the contract;
- backward-compatibility impact.
