# ClientPortal

`Modules/ClientPortal` is the project's authenticated Client/PWA application platform.

Current implementation documentation:

- `docs/modules/ClientPortal/README.md` — architecture/continuation overview;
- `docs/modules/ClientPortal/INFORMATION.md` — implementation map and current behavior;
- `docs/modules/ClientPortal/FUNCTIONS.md` — detailed functional guide;
- `docs/modules/ClientPortal/PWA.md` — PWA installer, `/my-apps/login`, browser behavior and verification;
- `docs/modules/ClientPortal/ANALYSIS.md` — architecture findings and refactor priorities captured during module analysis.

## Role in the project

ClientPortal is a **support module**, not a business-domain module.

Its role is:

```text
Public Website / installed PWA
        ↓
Client login + /my-apps launcher
        ↓
Application registry + permissions
        ↓
Application adapter
        ↓
Source domain module/service
```

The dependency rule remains:

```text
ClientPortal -> Muasamcong / other domain modules
Domain module -X-> ClientPortal
```

A domain module owns its canonical data/business services. ClientPortal owns the Client/PWA experience, Client permissions and Client-specific workflow state.

## Current PWA entry flow

The manifest starts at:

```text
/my-apps
```

Guest flow:

```text
Open installed PWA
    -> /my-apps
    -> unauthenticated
    -> /my-apps/login
    -> web-guard login
    -> /my-apps
    -> permitted applications
```

The dedicated PWA login route is:

```text
GET /my-apps/login
name: client.apps.login
```

This login screen is mobile-first and visually independent from the generic `/login` and `/admin/login` screens, but it **reuses the canonical `Modules\Auth\Livewire\Auth\LoginForm` authentication logic**. PWA UI must not introduce a duplicate credential/authentication implementation.

See `PWA.md` for the complete routing and browser contract.

## Website PWA installer

The public Website footer exposes a PWA installer instead of treating PWA as an App Store/Google Play download.

Main implementation:

```text
Modules/Website/resources/views/partials/pwa-installer.blade.php
```

Behavior:

- Android Chromium: uses `beforeinstallprompt` and the native install prompt;
- iPhone/iPad Safari: shows a bottom-sheet guide for `Chia sẻ -> Thêm vào Màn hình chính -> Thêm`;
- iOS non-Safari: instructs the user to open the site in Safari;
- standalone PWA: displays installed state instead of another install prompt.

Important iOS limitation: a normal Safari tab cannot reliably determine whether the same PWA is already installed elsewhere on the device. Only the current standalone browsing context can be detected reliably.

## Current application

The primary adapter is:

```text
Applications/Muasamcong
```

It provides:

- drug pricing search;
- database-first lookup;
- queued selected-row synchronization;
- search/sync history;
- Wishlist;
- public drug sharing;
- share management;
- Price List workspace;
- queued XLSX generation;
- queued PDF conversion;
- private downloads;
- public Price List link;
- queued Price List email delivery;
- delivery/share tracking.

## Application convention

New applications should follow:

```text
Modules/ClientPortal/Applications/{Application}/
├── manifest.php
├── routes.php
├── Http/
├── Jobs/
└── Services/
```

The manifest declares the source domain module. `ApplicationRegistry` hides an adapter when that source module is disabled.

## Permission convention

Use:

```text
client.{application}.access
client.{application}.{feature}.view
client.{application}.{feature}.{action}
```

Client permissions use guard `web`; Admin management remains guard `admin`.

A `.view` permission should not automatically authorize destructive/mutating actions unless that is an explicit product contract. `ANALYSIS.md` records current authorization/refactor findings.

## PWA security contract

PWA metadata/resources:

```text
/manifest.webmanifest
/service-worker.js
/pwa/*
```

Authenticated navigation must remain network-first and must not be stored as reusable authenticated HTML in Cache Storage.

ClientPortal pages are permission/user-sensitive. Future offline support requires an explicit security-reviewed data design rather than generic page caching.

## Authentication ownership

The intended ownership is:

```text
Website
    -> public installer UX

ClientPortal
    -> /my-apps
    -> /my-apps/login presentation
    -> Client route redirect behavior

Auth
    -> credentials
    -> guards
    -> session regeneration
    -> reusable LoginForm logic
```

Current Google OAuth is Admin-oriented: it authenticates the `admin` guard through `Modules\Admin\Services\AuthService`. Therefore it must **not** be added to the PWA login UI until a dedicated `web`-guard Client OAuth flow is implemented.

## Domain access rule

Application adapters may consume source-domain public services/models, but reusable persistence rules should stay in the source domain.

Preferred:

```text
Client Controller
    -> Client application service
    -> Muasamcong domain service
    -> Muasamcong model/database
```

Avoid duplicating canonical Muasamcong rules inside ClientPortal controllers.

## Price List architecture

The rich Price List renderer intentionally uses PhpSpreadsheet because it requires capabilities beyond a basic table export:

- Admin-defined columns/headers;
- String/Number/Date typing;
- widths/decimals/alignment;
- logo/signature drawings;
- company header/footer;
- configurable A4 page setup;
- Excel-to-PDF conversion;
- private file lifecycle;
- sharing/email delivery.

Preserve this specialized renderer while progressively separating workbook building, artifact storage, share lifecycle and delivery lifecycle into smaller services.

## Private artifact rule

Price List XLSX/PDF files are private artifacts and remain in private storage. Downloads go through authenticated/authorized routes except when the user explicitly creates a high-entropy public share URL.

Every generated artifact should use an immutable unique path. `ANALYSIS.md` contains the remaining artifact-lifecycle/refactor findings.

## Queue rule

Long operations remain queued:

```text
Drug pricing sync
XLSX generation
PDF conversion
Email delivery
```

State transitions should remain durable:

```text
queued -> processing -> completed/failed
```

External side effects such as email need explicit retry/idempotency behavior. Raw internal exceptions/process output should be logged server-side and sanitized before display to Client users.

## Sharing rule

Public sharing should define:

- ownership;
- high-entropy token;
- payload/file scope;
- expiry;
- revoke behavior;
- audit/delivery semantics;
- retention.

Drug sharing already supports expiry/revoke. Price List sharing still has lifecycle improvements documented in `ANALYSIS.md`.

## Export Profile rule

Price List uses Muasamcong Admin `SyncedExportProfile` configuration. The long-term publication model must remain explicit: global published template, organization/user-owned template, or explicitly shared template.

Do not silently broaden access to configuration records merely because they exist in the table.

## Recommended refactor direction

The analysis recommendation remains **Major Refactor, not rebuild**.

Preserve the module/adapter architecture and incrementally separate responsibilities:

```text
ClientPortal
├── Registry / permissions
├── PWA launcher / login shell
├── Applications/Muasamcong
│   ├── Search presentation service
│   └── Controllers
└── Price List workflow
    ├── Workspace service
    ├── Workbook builder
    ├── Artifact storage
    ├── Share lifecycle
    └── Delivery lifecycle
```

Queue jobs should become thin orchestrators around those services.

## Verification

Minimum ClientPortal regression:

```bash
php artisan test tests/Feature/ClientApps
```

PWA-specific targeted regression:

```bash
php artisan test \
  tests/Feature/ClientApps/ClientApplicationRegistryTest.php \
  tests/Feature/ClientApps/ClientPwaFoundationTest.php
```

Manual PWA checks should include:

- public Website footer installer on desktop/mobile;
- Android install prompt;
- iPhone Safari Home Screen instructions;
- iPhone non-Safari browser guidance;
- installed standalone state;
- guest `/my-apps` -> `/my-apps/login`;
- successful PWA login -> `/my-apps`;
- guest `/apps/*` -> `/my-apps/login`;
- Admin login unaffected;
- service worker does not cache authenticated navigation.

Manual Muasamcong checks should include:

- database/snapshot/API search source;
- selected synchronization;
- History;
- Wishlist;
- drug share + revoke/expiry;
- Price List creation/edit/recreate;
- Excel formatting/Print Preview;
- PDF conversion;
- file permissions/download;
- sharing;
- email Excel/PDF/both;
- delivery/share history.

## Continuation guidance for another AI

Before modifying ClientPortal, read in this order:

```text
.codex/bootstrap/CODEX_BOOTSTRAP.md
.codex/bootstrap/PROJECT_BOOTSTRAP.md
.codex/bootstrap/AI_PROJECT_CONTEXT.md
.codex/standards/MODULE_STANDARD.md
.codex/standards/ADMIN_UI_STANDARD.md

docs/modules/ClientPortal/README.md
docs/modules/ClientPortal/PWA.md
docs/modules/ClientPortal/FUNCTIONS.md
docs/modules/ClientPortal/INFORMATION.md
docs/modules/ClientPortal/ANALYSIS.md
```

Treat `ANALYSIS.md` as an assessment/refactor backlog snapshot and the other documents as the implemented/current behavior guide. Verify source and tests before acting on any stale detail.