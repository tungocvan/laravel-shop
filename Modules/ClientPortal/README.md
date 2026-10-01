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
