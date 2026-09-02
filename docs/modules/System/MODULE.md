# System — Module Contract

## 1. Identity

- Module: `System`
- Type: `shell`
- Status: `active`
- Manifest: `Modules/System/config/module.php`
- Routes: `Modules/System/routes/web.php`
- Last architecture review: `2026-09-02`

## 2. Purpose

`System` owns administrator-facing system operations and infrastructure configuration: System dashboard/health, runtime module control, canonical system settings, environment/infrastructure configuration, database operations, restricted command/script operations, and infrastructure integrations such as Google Drive.

System is a shell/operations module. It must not absorb business-domain configuration or UI ownership merely because a capability is visible under `/admin`.

## 3. Canonical Ownership

| Capability | Canonical owner | Runtime entry |
|---|---|---|
| System administration dashboard | System | `admin.system.dashboard` / `SystemDashboardController` |
| System operations workspace | System | `admin.system.index` / `SystemController` |
| Runtime module administration | System | `admin.system.modules` / `SystemModuleControlService` |
| System settings workspace | System | `admin.system.settings.index` / `SettingForm` |
| System setting persistence | System | `Setting` + `SettingsService` |
| Environment configuration | System | `admin.system.settings.env` and `Services/Env/*` |
| Database administration | System | `admin.system.database.*` |
| Restricted Artisan/script operations | System | `admin.system.artisan`, `admin.system.scripts` |
| Google Drive infrastructure integration | System | `admin.system.settings.cloud.google.*` and `Services/Cloud/*` |
| Admin post-login landing preference | System | `AdminLoginRedirectService` + `system.settings.partials.login-redirect` |

## 4. Explicit Non-Ownership

| Capability | Canonical owner | Relationship |
|---|---|---|
| Global Admin layout/shell/navigation primitives | Admin | System consumes Admin shell/components |
| Admin menu management | Admin | System must not embed or own the Admin menu manager in System settings |
| Roles/permissions implementation | Role | System consumes authorization contracts |
| Public website/home route | Website | System may expose the root landing route as a selectable post-login destination when registered; Website remains owner |
| Business-domain settings | Respective domain module | System may link/integrate but must not become canonical owner |
| Root module catalog/graph/registry infrastructure | `App\Modules` + root module provider | System consumes public runtime-control boundaries |

## 5. Dependencies

### Direct dependencies

| Module | Reason | Required |
|---|---|---|
| Admin | Admin authentication, layout and shell integration | Yes |
| Role | permissions/authorization used by System operations | Yes |

These dependencies must remain synchronized with `Modules/System/config/module.php`.

### Integration dependencies

- Website: optional root `/` route (`home`) may be offered as the preferred post-login landing target when the route is currently registered.
- Root module runtime infrastructure: `ModuleCatalog`, `ModuleGraphValidator`, `ModuleRegistry`, lifecycle/state/permission services are consumed by System runtime administration without transferring canonical ownership into System.

## 6. Consumers

| Consumer | Capability |
|---|---|
| Admin authenticated operators | System dashboard, settings, module, database and operations workspaces |
| Admin authentication/login flow | configured post-login landing route |
| Scheduler/console infrastructure | System automation/health services where explicitly wired |

## 7. Canonical Routes

System owns the authenticated Admin route group under `/admin/system/*`, including:

- `/admin/system/dashboard`
- `/admin/system`
- `/admin/system/modules`
- `/admin/system/artisan`
- `/admin/system/scripts`
- `/admin/system/settings`
- `/admin/system/settings/env`
- `/admin/system/settings/cloud/google/connect`
- `/admin/system/settings/cloud/google/callback`
- `/admin/system/database`
- `/admin/system/database/backup-restore`
- `/admin/system/database/download/{filename}`

`/admin/settings` remains a compatibility redirect to `/admin/system/settings` until caller proof supports removal.

The public root `/` route is not owned by System. When it exists as a named, parameterless GET route, System may expose it as a post-login landing option.

## 8. Canonical Runtime Components

### Controllers

- `SystemDashboardController` — dashboard shell.
- `SystemController` — System operations shell.
- `SettingController` — settings/module/command page shells.
- `EnvConfigController` — environment settings shell.
- `DatabaseController` — database administration routes.
- `GoogleDriveOAuthController` — Google Drive OAuth integration boundary.

### Livewire / UI Components

- `Settings/SettingForm` — System settings tab/workspace selector.
- `Settings/Partials/*` — System-owned settings sections.
- `Settings/ModulesForm` — module runtime management surface.
- `Database/*` — database management surfaces.

System settings must not embed `admin.header.menu-manager`; Admin owns menu management.

### Services

Canonical families:

- `SettingsService` — DB-backed system setting persistence/access.
- `AdminLoginRedirectService` — validated post-login landing selection with compatibility fallback.
- `Services/Env/*` — environment/infrastructure configuration and diagnostics.
- `Services/Database/*` — extracted database administration responsibilities.
- `Services/Cloud/*` — Google Drive/cloud integration responsibilities.
- `SystemModuleControlService`, `SystemModuleOverviewService`, `SystemRealtimeControlService` — System adapters/orchestrators over root module/runtime infrastructure.
- `SystemDashboardService`, `SystemOperationService`, `SystemScriptOperationService` — operational read/action boundaries.

Legacy/overlapping facades are not deleted solely because a newer specialized service exists; caller proof is mandatory.

### Models

- `Setting` — canonical model for the `settings` table; value interpretation belongs in `SettingsService`.

## 9. Persistence Ownership

| Table / storage | Owner | Migration/source | Notes |
|---|---|---|---|
| `settings` | System | existing settings schema | Canonical system setting key/value metadata store |
| `Modules/System/data/system_tabs.json` | System | file-backed override | System tab override only; not business-domain configuration |

Module runtime state storage such as `storage/app/system/module-state.json` is part of the root module-runtime contract consumed by System control surfaces; its schema must not be changed casually by System UI refactors.

## 10. Integration Boundaries

### Admin shell

- Owner: Admin.
- Consumer: System.
- Direction: System depends on Admin.
- System uses the canonical Admin layout/components and must not duplicate global navigation/menu ownership.

### Role/permissions

- Owner: Role/shared authorization infrastructure.
- Consumer: System.
- Direction: System depends on Role.
- Every mutation remains server-side permission protected; hiding UI controls is not authorization.

### Website root landing

- Owner: Website.
- Consumer: System login-landing setting.
- Direction: optional integration only.
- System may select the registered parameterless GET root `/` route. If unavailable, System must fall back to an allowed Admin route without creating or taking ownership of `/`.

### Root module runtime

- Owner: `App\Modules` and root module provider/lifecycle infrastructure.
- Consumer: System module-control UI/services.
- System must use public catalog/graph/registry/state boundaries rather than reintroducing independent filesystem discovery.

## 11. Compatibility / Deprecated Boundaries

| Artifact | Canonical replacement | Status | Removal condition |
|---|---|---|---|
| `/admin/settings` | `admin.system.settings.index` | compatibility redirect | caller proof + regression + explicit approval |
| Stored legacy `admin_login_redirect_route` values naming valid Admin routes | validated landing target | compatible | retained and resolved while valid |
| Monolithic/overlapping database/settings services | specialized service families | transitional | caller proof + focused regression before removal |

## 12. Quarantine

The following are persistence/security/operations sensitive and must not be removed or broadly rewritten outside an approved coherent slice:

- database backup/restore/download behavior;
- environment file write/backup/snapshot behavior;
- Google Drive credentials/OAuth/backup automation;
- module migration recovery/state persistence;
- `LegacySettingsAuditService` / `LegacySettingsMigrationService` until historical-data callers are proven;
- monolithic `DatabaseService` until caller/reachability proof exists;
- restricted Artisan/script execution paths.

## 13. Refactor Invariants

Every System refactor must preserve:

1. canonical `/admin/system/*` route contracts unless separately approved;
2. `auth:admin` and permission middleware/server-side authorization;
3. `Admin` and `Role` direct dependencies;
4. `settings` persistence compatibility;
5. root module catalog/graph/registry/state contracts;
6. no browser-driven deletion/movement of tracked module source;
7. production restrictions around dangerous command/script operations;
8. database backup/restore safety and filename validation;
9. Admin UI standard, responsive behavior and bounded pagination where datasets apply;
10. Admin-owned global menu/navigation ownership;
11. optional Website root-route integration without making Website a hard dependency;
12. safe fallback when a configured post-login route disappears or becomes invalid.

## 14. Required Refactor Audit

Before implementation:

`Route → Controller → View/Livewire → Service → Model/Persistence → Callers → Cross-module dependencies`

Affected artifacts must be classified as `KEEP`, `REHOME`, `DELETE`, `QUARANTINE`, or `DEFER`. No destructive cleanup is justified by naming similarity alone.

## 15. Required Regression Scope

Minimum applicable gates:

- focused tests for the changed System boundary;
- System Feature regression;
- Admin regression when Admin shell/navigation/auth integration changes;
- impacted root-module/consumer regression only when those contracts are changed;
- System route verification;
- Pint for changed PHP files;
- frontend build when Blade/assets materially change;
- desktop/mobile manual UI smoke for changed Admin surfaces.

Full-project regression is not automatic.

## 16. Architectural Change Rules

Update this file in the same PR whenever changing System responsibility, ownership/non-ownership, direct dependencies, canonical routes, integration boundaries, persistence ownership, compatibility/deprecation, quarantine or refactor invariants.

## 17. Deferred Debt

| Debt | Owner/target module | Reason | Exit condition |
|---|---|---|---|
| Dependency-topological module boot ordering | root module runtime | intentionally preserved existing boot order | separately approved runtime phase + graph/boot regression |
| Distributed locking for concurrent module transitions | root module runtime/System integration | concurrency hardening outside current UI/settings slice | lock design + lifecycle regression |
| Split/retire remaining overlapping settings/env service names | System | requires complete caller proof | imports/callers mapped + focused regression |
| Retire monolithic `DatabaseService` where replaced | System | backup/restore is persistence-sensitive | method-by-method caller proof + backup/restore regression |
| Legacy settings audit/migration cleanup | System | historical-data compatibility risk | data/caller proof + explicit removal plan |

## 18. Architecture Decisions

### 2026-09-02 — Establish System architectural contract

**Decision:** Define System as the shell owner of system administration and infrastructure operations, while explicitly excluding global Admin menu ownership and business-domain settings.

**Reason:** Runtime has accumulated multiple generations of settings/database/infrastructure components; durable ownership is required before further cleanup.

**Impact:** Refactors must converge on the service families above and keep uncertain legacy in quarantine/defer until caller proof exists.

### 2026-09-02 — Root route may be a post-login landing target

**Decision:** The currently registered root `/` parameterless GET route may be selected as the preferred post-login landing destination even though it is not an `admin.*` route. Existing valid Admin route-name values remain compatible. If the selected route disappears, resolve to a safe available fallback.

**Reason:** Operators may prefer entering the application at `/`; System configures the preference but does not own or create the Website route.

**Impact:** `AdminLoginRedirectService` must validate both Admin routes and the root route and must never create a hard Website dependency.
