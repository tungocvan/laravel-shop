# ClientPortal — Module Contract

## 1. Identity

- Module: `ClientPortal`
- Type: `support`
- Status: `active`
- Manifest: `Modules/ClientPortal/config/module.php`
- Routes: `Modules/ClientPortal/routes/web.php`
- Direct dependency: `Auth`
- Last architecture review: `2026-09-01`

## 2. Purpose

ClientPortal is the authenticated Client/PWA application platform. It owns the launcher, shared Client/PWA presentation shell, application registry, client-side authorization presentation, PWA configuration, account/settings presentation, and application adapters that expose domain capabilities to `web` users.

ClientPortal must remain thin: it may orchestrate and present domain capabilities, but domain business logic, domain persistence and Admin/domain workflows remain owned by their source modules.

## 3. Canonical Ownership

| Capability | Canonical owner | Runtime entry |
|---|---|---|
| Client application launcher | ClientPortal | `/my-apps` |
| Client/PWA account and settings presentation | ClientPortal | `/my-apps/account`, `/my-apps/settings` |
| Application discovery/manifest registry | ClientPortal | `ApplicationRegistry` |
| Client application/feature permission presentation and synchronization | ClientPortal | `ApplicationPermissionService`, `/admin/client-apps/*` |
| PWA presentation settings | ClientPortal | `PwaSettingsController`, `ClientPortalSetting` |
| Client application adapters | ClientPortal | `Applications/{Application}` |
| Shared Client/PWA navigation/context/access presentation | ClientPortal | Portal resolver/presenter services |

## 4. Explicit Non-Ownership

| Capability | Canonical owner | Relationship |
|---|---|---|
| Authentication, session lifecycle, logout and Google account linking | Auth | consumes canonical Auth contract |
| Muasamcong domain models/services/Admin workflows | Muasamcong | adapter consumes domain services/models |
| Request domain workflow, models, policies and persistence | Request | adapter consumes domain capability |
| Future application domain logic | Source domain module | ClientPortal provides adapter/presentation only |

A domain module must not depend on ClientPortal in order to boot or operate its Admin/domain routes.

## 5. Dependencies

### Direct dependencies

| Module | Reason | Required |
|---|---|---|
| Auth | shared `web` authentication/session/account-linking contract | Yes |

This list must remain synchronized with `Modules/ClientPortal/config/module.php`.

### Integration dependencies

Application adapters may integrate with their `source_module` only when that module is enabled. These are integration dependencies rather than unconditional module boot dependencies.

Current adapters:

- `Request` → source module `Request`
- `Muasamcong` → source module `Muasamcong`

## 6. Consumers

- Authenticated `web` users consume launcher and application surfaces.
- Admin users consume `/admin/client-apps/*` for ClientPortal application permission and PWA presentation administration.
- Source domain modules are providers to adapters, not consumers of ClientPortal core.

## 7. Canonical Routes

ClientPortal owns:

- `/my-apps*`
- `/admin/client-apps*`
- `/apps/{application}*` routes registered by ClientPortal application adapters
- public ClientPortal share routes whose lifecycle is owned by a specific ClientPortal adapter

Route ownership is based on runtime responsibility, not URL prefix alone.

## 8. Canonical Runtime Components

### Controllers

- `PortalController`
- `AccountController`
- ClientPortal Admin controllers
- controllers under `Applications/{Application}/Http`

### Services

- `ApplicationRegistry`
- `ApplicationPermissionService`
- `ClientPortalSettingsService`
- Portal access/context/navigation/account presentation services

### Models

- `ClientPortalSetting` is core ClientPortal persistence.
- Adapter-specific client state belongs under the corresponding `Applications/{Application}` namespace even when legacy table names retain the `client_portal_*` prefix for data compatibility.

## 9. Persistence Ownership

| Table/storage | Owner | Notes |
|---|---|---|
| `client_portal_settings` | ClientPortal core | PWA/ClientPortal presentation configuration |
| `client_portal_sync_requests` | ClientPortal Muasamcong adapter | client-side sync orchestration state; table name retained for compatibility |
| `client_portal_public_shares` | ClientPortal Muasamcong adapter | client-created public share lifecycle; table name retained for compatibility |
| `client_portal_price_list_exports` | ClientPortal Muasamcong adapter | client export orchestration/delivery state; table name retained for compatibility |

Persistence rehoming must not rename/drop tables without a separately approved data migration plan.

## 10. Integration Boundaries

Application manifests declare `source_module`. `ApplicationRegistry` exposes an adapter only while the source module is enabled. Adapter code may call source-module public/domain services and models as required by the client workflow, but must not duplicate domain calculations or domain Admin behavior.

Auth owns authentication/session/logout/linking. ClientPortal owns how those capabilities are presented inside Client/PWA surfaces.

## 11. Compatibility / Deprecated Boundaries

Legacy root namespaces for Muasamcong-specific ClientPortal models may remain temporarily as compatibility aliases while callers/tests move to `Applications/Muasamcong`. Removal requires caller proof plus ClientPortal/Muasamcong regression.

Root ClientPortal export jobs that are Muasamcong-specific are transitional debt and must not be reused by another adapter.

## 12. Quarantine

- Existing migration/table names for adapter-specific persistence are quarantined from rename/drop during namespace cleanup.
- Public-share security semantics, tokens and existing records are quarantined from destructive migration without explicit approval.
- Export files/history and queued-job compatibility are quarantined from class deletion until queue/caller proof exists.

## 13. Refactor Invariants

1. `/my-apps` and existing application routes remain stable unless explicitly approved.
2. `web` and `admin` guards remain separated.
3. Auth remains canonical owner of authentication/session/logout/account linking.
4. Domain modules remain owners of domain business logic and persistence.
5. Application adapters are disabled when their source module is disabled.
6. Domain/Admin routes must not depend on ClientPortal being enabled.
7. Existing persisted ClientPortal adapter data is preserved during namespace cleanup.
8. Existing PWA file/download behavior must follow `docs/PWA_EXTERNAL_FILE_HANDOFF.md`.
9. Admin ClientPortal UI follows `.codex/standards/ADMIN_UI_STANDARD.md`.
10. Deprecated compatibility artifacts stay until caller-proof exists.

## 14. Refactor Classification

| Boundary | Classification |
|---|---|
| Portal shell, registry, permission/settings services | KEEP |
| ClientPortalSetting | KEEP |
| Request and Muasamcong adapter surfaces | KEEP |
| Muasamcong-specific ClientPortal models in root namespace | REHOME with compatibility aliases |
| Muasamcong-specific root export jobs | DEFER/QUARANTINE until queued-job compatibility proof |
| Domain business logic discovered inside ClientPortal adapters | REHOME to source module when proven |
| Existing adapter persistence tables | QUARANTINE from destructive schema changes |

## 15. Required Regression Scope

Minimum gates for architectural refactor:

- focused ClientPortal contract/tests for changed boundary;
- ClientPortal/ClientApps regression;
- Auth regression when auth/session/account contract changes;
- source-module regression for adapters whose boundary changes (`Request`, `Muasamcong` as applicable);
- route verification for `/my-apps`, `/apps/*`, `/admin/client-apps/*`;
- Pint for changed PHP files;
- frontend build when UI/assets change;
- representative desktop/mobile/PWA UI smoke when presentation changes.

Full-project regression is not automatic.

## 16. Architectural Change Rules

Update this contract in the same PR whenever changing responsibility, ownership/non-ownership, direct dependencies, canonical routes, integration boundaries, persistence ownership, compatibility/deprecation, quarantine or refactor invariants.

## 17. Deferred Debt

| Debt | Owner/target | Reason | Exit condition |
|---|---|---|---|
| Muasamcong-specific root export jobs | ClientPortal `Applications/Muasamcong` | queued-job class compatibility risk | caller/queue proof + focused export regression |
| Legacy root model aliases after rehome | ClientPortal `Applications/Muasamcong` | backward compatibility | repository caller proof + regression |
| Potential resolver/presenter consolidation | ClientPortal core | avoid speculative cleanup | demonstrated overlap plus contract tests |

## 18. Architecture Decisions

### 2026-09-01 — Thin Client/PWA platform with adapter ownership

**Decision:** ClientPortal remains a support module and adapter host. Adapter-specific client state belongs to the adapter namespace, while source domain modules retain domain ownership.

**Reason:** This preserves dependency direction, prevents Portal core from accumulating domain logic, and allows applications to be added/removed without changing the ClientPortal core contract.

**Impact:** Muasamcong-specific ClientPortal state is rehomed by namespace without destructive table migration; compatibility boundaries are removed only after caller proof.