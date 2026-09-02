# Muasamcong — Module Contract

## 1. Identity

- Module: `Muasamcong`
- Type: `domain`
- Status: `active`
- Manifest: `Modules/Muasamcong/config/module.php`
- Routes: `Modules/Muasamcong/routes/web.php`, `Modules/Muasamcong/routes/api.php`
- Last architecture review: `2026-09-02`

## 2. Purpose

Muasamcong owns the procurement-domain capabilities used to search, snapshot, verify, synchronize, curate and export public-procurement pricing and contractor information. It also owns the module-specific Personal Session boundary required to integrate with the upstream procurement source.

The module is a domain owner. Admin and ClientPortal may expose Muasamcong capabilities, but they do not own or duplicate Muasamcong domain logic or persistence.

## 3. Canonical Ownership

| Capability | Canonical owner | Runtime entry |
|---|---|---|
| Admin Muasamcong dashboard | Muasamcong | `muasamcong.dashboard` |
| Smart Pricing search and saved search snapshots | Muasamcong | `muasamcong.index`, `TracuuThuoctrungthau`, `PricingSearchSnapshotService` |
| Synced pricing dataset and export profiles | Muasamcong | `muasamcong.synced`, `SyncedPricingList` |
| Pricing Wishlist | Muasamcong | `muasamcong.wishlist`, `PricingWishlistService` |
| HSMT / KQLCNT lookup and snapshots | Muasamcong | `muasamcong.hsmt`, HSMT/KQLCNT services |
| Contractor lookup, history, jobs, archives and manually verified lots | Muasamcong | contractor Admin routes and services |
| Personal Session and session-import tokens | Muasamcong | config/session tool and API import boundary |
| Muasamcong-specific Excel/export formatting | Muasamcong | Muasamcong export controllers/services |

## 4. Explicit Non-Ownership

| Capability | Canonical owner | Relationship |
|---|---|---|
| Admin shell/layout/navigation | Admin | Muasamcong renders inside the Admin shell |
| PWA/application shell and adaptive navigation | ClientPortal | ClientPortal consumes Muasamcong capabilities |
| Authentication identities and global permission framework | Auth/Role/System owners | Muasamcong declares and enforces capability permissions |
| Generic shared Import/Export primitives | Shared | Muasamcong may consume shared infrastructure when compatible |

## 5. Dependencies

### Direct dependencies

`Modules/Muasamcong/config/module.php` currently declares no direct module dependency. Keep `depends => []` unless runtime evidence proves that another module is required for Muasamcong to boot and operate.

Admin and ClientPortal are integration/consumer surfaces, not hard dependencies merely because they display Muasamcong capabilities.

### Integration dependencies

- Admin provides the canonical Admin shell used by Muasamcong Admin pages.
- ClientPortal may consume Muasamcong application/domain boundaries without taking ownership of domain logic or persistence.
- Shared infrastructure may be used for generic UI/import/export primitives when the shared contract is compatible.

## 6. Consumers

| Consumer | Capability |
|---|---|
| Admin operators | Dashboard, Smart Pricing, synced pricing, Wishlist, HSMT, contractor and config workspaces |
| ClientPortal application | Muasamcong PWA/client workflows |
| Authenticated API clients | Muasamcong pricing/search API |

## 7. Canonical Routes

Canonical route groups are owned by `Modules/Muasamcong/routes/web.php` and `Modules/Muasamcong/routes/api.php`.

Preserve these route families during refactor:

- `/admin/muasamcong/*`
- `/api/muasamcong/*` as registered by the module API provider

Route ownership must be traced as:

`Route → Controller → View/Livewire → Service → Model/Persistence → Callers → Cross-module dependencies`.

## 8. Canonical Runtime Components

### Controllers

Admin page controllers should remain thin. Mutation/export endpoints may validate scope and authorization but should delegate domain/query/export work to services or bounded exporter objects.

### Livewire / UI Components

Livewire owns interaction state such as search/filter/pagination/selection/modal state. Core procurement rules, persistence orchestration and export mapping do not belong in Blade and should progressively move out of oversized Livewire components.

### Services

Services are the canonical location for upstream procurement integration, snapshots, synchronization, contractor/HSMT/KQLCNT orchestration, configuration, Personal Session behavior and reusable export/query scope logic.

### Models

Models under `Modules/Muasamcong/Models` represent Muasamcong-owned persistence. They are not candidates for rehome merely because another module consumes their data through an integration boundary.

## 9. Persistence Ownership

Muasamcong owns its `muasamcong_*` tables and module-specific stored export-profile assets, including pricing results, pricing wishlists, contractor bids/searches/items/jobs/manual lots, KQLCNT records, pricing search snapshots, Personal Sessions, session-import tokens and synced export preferences.

Persistence is a protected boundary. No table/model/migration/storage path may be deleted or rehomed without ownership proof, compatibility/data migration planning and explicit approval.

## 10. Integration Boundaries

### ClientPortal → Muasamcong

- Business owner: Muasamcong.
- Consumer: ClientPortal.
- Allowed direction: `ClientPortal → Muasamcong`.
- ClientPortal owns shell/navigation/PWA presentation; Muasamcong owns procurement-domain behavior and data.
- Do not duplicate procurement search/sync/export business rules inside ClientPortal.

### Admin shell → Muasamcong

Admin provides layout/navigation infrastructure. Muasamcong owns the feature workspace rendered inside that shell.

## 11. Compatibility / Deprecated Boundaries

| Artifact | Canonical replacement | Status | Removal condition |
|---|---|---|---|
| Large multi-purpose controller/export methods retained during extraction | Bounded controller/service/export boundary | transitional | caller proof + replacement tests + route compatibility |
| Oversized Livewire orchestration retained during incremental extraction | focused component/service boundaries | transitional | behavioral parity + focused regression |

Deprecated/transitional does not mean dead code.

## 12. Quarantine

The following boundaries are quarantined from destructive cleanup unless separately approved:

- Personal Session secrets and session-import token lifecycle;
- raw/snapshot procurement payloads and persisted lineage;
- contractor/manual-lot provenance and verification history;
- database migrations and existing table ownership;
- production storage paths and generated export-profile assets.

## 13. Refactor Invariants

Every Muasamcong refactor must preserve:

1. canonical Admin/API route URIs and route names unless explicitly approved;
2. server-side authentication and capability authorization;
3. Muasamcong persistence ownership and existing data compatibility;
4. ClientPortal → Muasamcong dependency direction;
5. upstream-source versus manually enriched data provenance;
6. no heuristic contractor-to-lot/medicine association where user/source verification is required;
7. export compatibility unless a format change is explicitly approved;
8. checkbox export semantics: selected rows when selection is non-empty, otherwise the full approved export scope;
9. page checkbox semantics mean the visible page unless the UI explicitly states otherwise;
10. quarantined secrets/raw payload/schema boundaries.

## 14. Required Refactor Audit

Before architectural implementation:

`Route → Controller → View/Livewire → Service → Model/Persistence → Callers → Cross-module dependencies`

Classify affected artifacts as `KEEP`, `REHOME`, `DELETE`, `QUARANTINE` or `DEFER`. Zero code-search results alone are not deletion proof.

## 15. Required Regression Scope

Minimum applicable gates:

- focused authorization/export/pagination/selection tests for the changed slice;
- `tests/Feature/Muasamcong` module regression;
- impacted ClientPortal tests only when a ClientPortal/public integration contract changes;
- Muasamcong route verification;
- Pint for changed PHP files;
- frontend build when Blade/assets change;
- manual Admin desktop/mobile UI smoke for changed workspaces;
- export smoke proving `no selection => all approved scope` and `selection => selected only`.

Full-project regression is not a default gate.

## 16. Architectural Change Rules

Update this contract in the same PR whenever responsibility, ownership/non-ownership, direct dependencies, canonical routes, integration boundaries, persistence ownership, compatibility/deprecation, quarantine or refactor invariants change.

## 17. Deferred Debt

| Debt | Owner/target module | Reason | Exit condition |
|---|---|---|---|
| Atomic session-import token claim | Muasamcong | security-sensitive boundary | dedicated concurrency/security slice and tests |
| Contractor job/sync idempotency | Muasamcong | operational behavior | explicit idempotency contract and regression |
| Snapshot/raw payload/file retention thresholds | Muasamcong | persistence/capacity concern | retention policy plus operational evidence |
| Oversized controller/Livewire extraction beyond current coherent slice | Muasamcong | avoid risky rewrite | bounded service/component replacement with parity tests |
| Legacy/Pint cleanup unrelated to changed boundaries | Muasamcong | non-blocking historical debt | separately approved cleanup slice |

## 18. Architecture Decisions

### 2026-09-02 — Keep Muasamcong as the procurement domain owner

**Decision:** Muasamcong remains the canonical owner of procurement search, snapshots, synchronization, Wishlist, contractor/HSMT/KQLCNT data, Personal Session integration and Muasamcong-specific export behavior.

**Reason:** Runtime routes, services, models and migrations form one procurement-domain boundary; Admin and ClientPortal are presentation/consumer surfaces rather than replacement domain owners.

**Impact:** Refactor should extract responsibilities inside Muasamcong instead of rehoming domain logic to ClientPortal/Admin. Persistence remains protected.

### 2026-09-02 — Standardize checkbox export scope

**Decision:** On checkbox-enabled export surfaces, a non-empty selection exports selected records; an empty selection exports the full approved scope rather than only the visible page.

**Reason:** This matches the repository Admin UI contract and avoids page-selection ambiguity.

**Impact:** Export endpoints must explicitly resolve scope and enforce server-side capability authorization.