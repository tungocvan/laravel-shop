# ClientPortal

`Modules/ClientPortal` owns the authenticated Client/PWA experience.

## Boundary

- `ClientPortal` owns `/my-apps`, `/apps/*`, PWA application UI, Client application/feature permissions, Client-side jobs and application adapters.
- Domain modules such as `Muasamcong`, `Invoices`, `Admission`, `Pharma` own their models, database, services and domain/Admin workflows.
- Client adapters may consume public/domain services from an enabled source module, but domain modules must not depend on `ClientPortal`.
- Disabling `ClientPortal` must not disable or change Admin/domain routes.

## Application adapter convention

Each Client application lives under:

```text
Modules/ClientPortal/Applications/{Application}/
├── manifest.php
├── routes.php
├── Http/
├── Jobs/
└── ...
```

`manifest.php` must declare `source_module`. `ApplicationRegistry` only exposes an adapter when that source module is enabled.

Example:

```php
return [
    'key' => 'muasamcong',
    'source_module' => 'Muasamcong',
    'route' => 'client.muasamcong.dashboard',
    'permission' => 'client.muasamcong.access',
    'features' => [
        // ...
    ],
];
```

## Permissions

Use the namespace:

```text
client.{application}.access
client.{application}.{feature}.view
client.{application}.{feature}.{action}
```

Client permissions use guard `web`. Admin permissions continue to use guard `admin`.

## Adding an application

1. Create `Applications/{Application}/manifest.php`.
2. Create `Applications/{Application}/routes.php` and guard route registration by the source module enabled state.
3. Put Client controllers/jobs/adapters under `ClientPortal`, not the domain module.
4. Reuse services from the source domain module; do not copy domain logic.
5. Add ClientPortal views under `resources/views/applications/{application}`.
6. Run ClientPortal permission sync from `/admin/client-apps`.
7. Add focused tests under `tests/Feature/ClientApps`.


## PWA feature completion and `/admin/client-apps` parity

Every ClientPortal/PWA feature must keep its editable presentation contract in sync with `/admin/client-apps`.

When a feature is implemented, refactored, or declared UI-complete:

1. Confirm the feature is declared in the application's `manifest.php` with its canonical key, route, permission, name, description and actions.
2. Confirm `/admin/client-apps` can discover that feature through the manifest and its existing Application/Feature presentation flow.
3. Route user-editable presentation copy through `ClientPortalSettingsService::featurePresentation()` instead of introducing new hard-coded configurable copy in the PWA view.
4. Keep authorization and business contracts immutable from presentation settings: route names, permission names, domain rules and workflow state must remain manifest/domain owned.
5. Add or update focused contract tests whenever a completed feature adds configurable presentation fields.
6. Do not mark a PWA feature complete until this parity check has been performed. If the current delivery intentionally defers the Admin UI wiring, record that deferral in the handoff/checklist and complete it before the overall PWA parity refactor is closed.

For the Pharma PWA refactor, apply this rule progressively to each remaining feature (Products, Price Lists, Bid Awards, Commercial, Orders, Inventory, Receipts, Commissions and subsequent manifest features) as that feature reaches its completion checkpoint. Do not build a second settings engine; extend the existing manifest + `ClientPortalSettingsService` + `/admin/client-apps` presentation mechanism.

## PWA native interaction and motion

ClientPortal PWA surfaces should feel responsive and app-like on touch devices without adding artificial latency or changing business semantics.

When a PWA feature is implemented or refactored:

1. Provide immediate visual press feedback for tappable controls and cards. Keep motion subtle and short; do not add arbitrary `setTimeout()` delays before navigation or server actions.
2. Give server-backed actions an explicit pending state when practical: disable duplicate submission and show clear loading/progress feedback while preserving the existing authorization and workflow contract.
3. Use short, consistent transitions for UI state changes such as page/content entry, expandable filters, dialogs, bottom sheets, status changes and load-more results. Motion must support comprehension rather than decoration.
4. Avoid layout jumps. Preserve stable space, scroll context and task context while loading or changing local UI state where practical.
5. Prefer skeleton/pending feedback for perceptible loading instead of leaving an apparently unresponsive control or blank region.
6. Respect `prefers-reduced-motion` (including Tailwind `motion-reduce:*` utilities where applicable). Core navigation, submission and business actions must never depend on animation completing.
7. Keep touch targets suitable for mobile use (normally at least about 44px for primary interactive controls) and make pressed, disabled and pending states visually distinguishable.
8. Centralize repeated interaction behavior into existing/shared ClientPortal primitives when multiple active screens need the same contract. Do not scatter incompatible per-view timing scripts across Blade templates.
9. Preserve the file-handoff rules in `docs/PWA_EXTERNAL_FILE_HANDOFF.md`; motion or loading feedback must not reintroduce top-level binary navigation in installed PWAs.
10. Validate interaction quality manually on an installed iOS PWA as well as normal desktop/browser behavior before declaring a feature UI-complete.

Recommended interaction sequence:

```text
touch
  ↓ immediate pressed feedback
action/navigation
  ↓ pending feedback when work is perceptible
content/state transition
  ↓ short settled state
success/error feedback when applicable
```

The goal is immediate tactile feedback plus meaningful transition, not simulated slowness. Navigation and actions should remain as fast as the underlying workflow allows.

For the Pharma PWA parity program, treat this as a shared UI/UX contract. Commercial, Inventory, Orders, Receipts, Commissions and subsequent refactors should consume the same interaction principles, while already-completed screens can be brought into parity through a separately reviewed shared-foundation checkpoint rather than ad-hoc rewrites.

