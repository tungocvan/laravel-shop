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
