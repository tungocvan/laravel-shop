# Muasamcong — Module Contract

## 1. Identity

- Module: `Muasamcong`
- Type: `domain`
- Status: `active`
- Manifest: `Modules/Muasamcong/config/module.php`
- Routes: `Modules/Muasamcong/routes/web.php`, `Modules/Muasamcong/routes/api.php`
- Last architecture review: `2026-09-04`

## 2. Purpose

Muasamcong owns procurement-domain capabilities used to search, snapshot, verify, synchronize, recover, curate and export public-procurement pricing, contractor and KQLCNT information. It also owns the module-specific Personal Session boundary required to integrate with the upstream procurement source.

Admin and ClientPortal are presentation/consumer boundaries; they do not own or duplicate Muasamcong domain logic or persistence.

## 3. Canonical Ownership

| Capability | Canonical owner | Runtime entry |
|---|---|---|
| Admin Muasamcong dashboard | Muasamcong | `muasamcong.dashboard` |
| Smart Pricing search and snapshots | Muasamcong | `muasamcong.index`, pricing services |
| Synced pricing and export profiles | Muasamcong | `muasamcong.synced` |
| Pricing Wishlist | Muasamcong | `muasamcong.wishlist` |
| HSMT / KQLCNT lookup and snapshots | Muasamcong | HSMT/KQLCNT services |
| Contractor lookup/history/jobs/archive/manual lots | Muasamcong | contractor Admin routes/services |
| Historical KQLCNT recovery, mapping, preview, import lineage and four-sheet export | Muasamcong | `muasamcong.contractors.kqlcnt-recovery*`, `KqlcntHistoricalImportService`, `ContractorKqlcntExportService` |
| Personal Session and session-import tokens | Muasamcong | config/session tool and API boundary |
| Muasamcong-specific Excel/export formatting | Muasamcong | module exporters/controllers |

## 4. Explicit Non-Ownership

| Capability | Canonical owner | Relationship |
|---|---|---|
| Admin shell/layout/navigation | Admin | Muasamcong renders inside Admin shell |
| PWA/application shell/adaptive navigation | ClientPortal | ClientPortal consumes Muasamcong capabilities |
| Authentication identities/global permission framework | Auth/Role/System | Muasamcong declares/enforces capability permissions |
| Generic shared Import/Export primitives | Shared | Muasamcong may consume compatible shared infrastructure |

## 5. Dependencies and Consumers

`Modules/Muasamcong/config/module.php` keeps `depends => []` unless runtime evidence proves a hard module dependency. Admin operators consume all Admin capabilities. ClientPortal may consume Muasamcong domain boundaries. Authenticated API clients consume the Muasamcong API.

## 6. Canonical Routes

Canonical route families remain:

- `/admin/muasamcong/*`
- `/api/muasamcong/*`

Historical KQLCNT recovery is additive under `/admin/muasamcong/contractors/history/{contractorSearch}/kqlcnt-recovery*`.

Route ownership must be traceable as:

`Route → Controller → View/Livewire → Service → Model/Persistence → Callers → Cross-module dependencies`.

## 7. Runtime Boundaries

Admin page controllers remain thin. Mutation/export endpoints validate scope and authorization then delegate to services/exporters. Livewire owns interaction state, not persistence rules. Services own upstream integration, snapshots, recovery/import normalization, deduplication/conflict classification and export scope. Models under `Modules/Muasamcong/Models` represent Muasamcong-owned persistence.

## 8. Persistence Ownership

Muasamcong owns all `muasamcong_*` tables and module-specific stored assets, including pricing results/wishlists, contractor bids/searches/items/jobs/manual lots, KQLCNT records, KQLCNT import batches, normalized KQLCNT award items, pricing search snapshots, Personal Sessions, session-import tokens and export preferences.

Historical recovery uses additive persistence:

- `muasamcong_kqlcnt_records` remains the package/contractor snapshot and records `api`, `import` or `mixed` provenance;
- `muasamcong_kqlcnt_import_batches` stores import audit/preview lineage;
- `muasamcong_kqlcnt_award_items` stores normalized medicine/lot award rows used for recovery and export.

Existing API snapshots, raw payloads and verified lots are preserved. Import data must never be silently deleted by a later API synchronization.

## 9. Integration Boundaries

### ClientPortal → Muasamcong

Allowed direction remains `ClientPortal → Muasamcong`. ClientPortal owns shell/navigation/PWA presentation; Muasamcong owns procurement business behavior and persistence.

### Admin shell → Muasamcong

Admin provides layout/navigation infrastructure. Muasamcong owns its feature workspace.

### Upstream KQLCNT API + Historical Import → canonical persistence

API data is preferred when available. Historical Excel import is an explicit recovery/supplement source when upstream data is incomplete or no longer retrievable. Both sources normalize into Muasamcong persistence with provenance retained. Conflict resolution is explicit; import does not silently overwrite API values.

## 10. Quarantine

The following remain quarantined from destructive cleanup unless separately approved:

- Personal Session secrets/session-import token lifecycle;
- raw/snapshot procurement payloads and lineage;
- contractor/manual-lot/KQLCNT recovery provenance;
- database migrations and existing table ownership;
- production storage paths/generated export assets.

## 11. Refactor Invariants

Every Muasamcong change must preserve:

1. canonical Admin/API routes unless explicitly approved;
2. server-side authentication and capability authorization;
3. Muasamcong persistence ownership/data compatibility;
4. ClientPortal → Muasamcong dependency direction;
5. upstream versus manually/import-enriched provenance;
6. no heuristic contractor-to-lot association without source/user verification;
7. export compatibility unless explicitly approved;
8. checkbox export semantics: selected rows when selection is non-empty, otherwise full approved scope;
9. visible-page checkbox semantics unless UI explicitly states otherwise;
10. quarantined secrets/raw/schema boundaries;
11. historical import requires preview before persistence;
12. recovery imports are scope-bound to the selected ContractorSearch/TBMT history;
13. API synchronization must not delete or silently overwrite imported recovery rows;
14. conflicts require explicit confirmation before overwrite.

## 12. Required Regression Scope

Minimum gates:

- focused authorization/import/preview/conflict/export tests;
- `tests/Feature/Muasamcong` regression;
- impacted ClientPortal tests only when its integration contract changes;
- Muasamcong route verification;
- Pint for changed PHP files;
- frontend build when Blade/assets change;
- manual Admin UI smoke for changed workspaces;
- export smoke proving selected/all semantics.

Full-project regression is not a default gate.

## 13. Deferred Debt

- atomic session-import token claim;
- contractor job/sync idempotency;
- snapshot/raw payload/import-batch retention thresholds;
- chunked/streaming export beyond current bounded scopes;
- deeper oversized controller/Livewire extraction;
- unrelated historical Pint cleanup.

## 14. Architecture Decisions

### 2026-09-02 — Keep Muasamcong as procurement domain owner

Muasamcong remains canonical owner of procurement search, snapshots, synchronization, Wishlist, contractor/HSMT/KQLCNT data, Personal Session integration and module-specific export behavior.

### 2026-09-02 — Standardize checkbox export scope

Non-empty selection exports selected records; empty selection exports the full approved scope rather than only the visible page.

### 2026-09-04 — Historical KQLCNT recovery is additive canonical persistence

**Decision:** Introduce previewed Excel recovery for KQLCNT data that the upstream system no longer exposes. Imported award rows are normalized, scope-validated, deduplicated and persisted with explicit lineage. Existing API snapshots remain intact; package provenance becomes `api`, `import` or `mixed`.

**Reason:** Historical TBMT/KQLCNT data can disappear upstream, but reporting/export requires durable internal history.

**Impact:** Recovery persistence and four-sheet KQLCNT export are canonical Muasamcong responsibilities. Conflicts require explicit confirmation and API synchronization may not delete imported recovery data.
