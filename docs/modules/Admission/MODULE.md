# Admission — Module Contract

## 1. Identity

- Module: `Admission`
- Type: `domain`
- Status: `active`
- Manifest: `Modules/Admission/config/module.php`
- Routes: `Modules/Admission/routes/web.php`
- Last architecture review: `2026-09-02`

## 2. Purpose

Admission owns the small, focused primary-school admission workflow: grade-1 registration, public application lookup, admin review, admission settings/catalog data, admission-specific import/export, and application documents/receipts.

It is not a generic workflow, authentication, queue-infrastructure, or document-conversion module.

## 3. Canonical Ownership

| Capability | Canonical owner | Runtime entry |
|---|---|---|
| Grade-1 registration | Admission | public registration + `AdmissionRegistrationService` |
| Public application lookup | Admission | `/admission/search` + Admission search Livewire component |
| Application administration/review | Admission | `/admin/admission/*` + `AdmissionApplicationAdminService` |
| Admission settings/catalogs | Admission | Admission admin settings/catalog surfaces |
| Admission import/export | Admission | Admission controller/services/exports |
| Admission documents/receipts | Admission | Admission document service/job + download routes |
| Admission dashboard | Admission | `admin.admission.dashboard` |

## 4. Explicit Non-Ownership

| Capability | Canonical owner | Relationship |
|---|---|---|
| Global `/admin` shell/dashboard | Admin | Admission links into Admin shell |
| Authentication/users/roles | Account/Admin security infrastructure | Admission consumes authenticated admin + permissions |
| Shared queue tables (`jobs`, `job_batches`) | shared application infrastructure | Admission consumes Laravel queue/batch runtime |
| Generic Office/PDF conversion | application/shared service | Admission consumes conversion capability |

## 5. Dependencies

### Direct dependencies

Runtime dependency declarations remain governed by `Modules/Admission/config/module.php`; this refactor must not invent undeclared cross-module ownership.

### Integration dependencies

Admission consumes the Admin shell/authz boundary and shared document/queue infrastructure without taking ownership of them.

## 6. Consumers

| Consumer | Capability |
|---|---|
| Public website | registration and application lookup |
| Admin users | application review, import/export, settings, locations, documents |

## 7. Canonical Routes

- Public lookup: `/admission/search`
- Admission admin: `/admin/admission/*` with `admin.admission.*` route names
- Compatibility Admission admin/document routes under `/admission/*` remain authenticated and permission protected until separately retired.

Ownership trace:

`Route → Controller/Livewire → Service → Admission model/persistence → caller/consumer`

## 8. Canonical Runtime Components

### Controllers

`AdmissionController` is orchestration-only: views, validation, authorization, service delegation, and response construction. Heavy document conversion and bulk business logic must not live in the controller.

### Livewire / UI Components

- public registration/lookup components own form interaction only;
- admin application component owns UI state and delegates application work to `AdmissionApplicationAdminService`.

### Services

- `AdmissionRegistrationService`: canonical registration/edit form boundary;
- `AdmissionApplicationAdminService`: canonical admin query/review/bulk/export boundary;
- Admission document orchestration must have one canonical service path shared by synchronous downloads and queued generation.

### Models

Admission models own admission-specific persistence only.

## 9. Persistence Ownership

| Table / storage | Owner | Notes |
|---|---|---|
| `admission_applications` | Admission | core application + review metadata |
| `admission_locations` | Admission, pending cross-module caller proof | keep while admission-specific |
| `admission_catalogs` | Admission | admission catalog data |
| `admission_settings` | Admission | school/admission settings |
| Admission import run/error tables | Admission | import audit/error tracking |
| `job_batches` | shared infrastructure | historical Admission migration is not ownership evidence |

No persistence-sensitive ownership correction may drop or recreate shared tables without schema + migration-ledger proof.

## 10. Integration Boundaries

Admission may call shared document conversion but must not duplicate LibreOffice/process handling in controllers. Admission may use Laravel batches but must not treat `job_batches` as domain-owned data.

## 11. Compatibility / Deprecated Boundaries

| Artifact | Canonical replacement | Status | Removal condition |
|---|---|---|---|
| lookup credentials embedded in `/admission/search/{identifier}/{password}` | clean `/admission/search` form flow | deprecated compatibility | caller proof + regression |
| placeholder Admission API returning 501 | no supported API contract | quarantined | caller proof |
| authenticated `/admission/*` document aliases | canonical admin Admission surfaces | compatibility | caller proof + route regression |

Deprecated does not mean dead code.

## 12. Quarantine

- `job_batches` historical migration ownership;
- placeholder Admission API surface;
- legacy public lookup URLs carrying credentials;
- any location/DVHC rehome until cross-module callers are proven.

Do not perform destructive cleanup of these boundaries without proof.

## 13. Refactor Invariants

1. Preserve grade-1 registration and lookup behavior.
2. Preserve Admission application data and review status metadata.
3. Preserve explicit Admission permissions and `auth:admin` boundaries.
4. Keep `/admin/admission/*` as canonical admin ownership.
5. Do not expose new credentials/secrets in URLs.
6. Do not duplicate document conversion in controllers.
7. Do not drop/recreate shared queue infrastructure from Admission cleanup.
8. Remove compatibility artifacts only after caller proof.

## 14. Required Refactor Audit

Affected artifacts are classified as:

- application lifecycle/schema: `KEEP`;
- registration service: `KEEP`;
- admin application service: `KEEP / CONSOLIDATE`;
- controller-local document generation: `REHOME`;
- URL-carried lookup credentials: `QUARANTINE / DEPRECATE`;
- placeholder API: `QUARANTINE`;
- Admission-owned `job_batches` migration: `QUARANTINE / REHOME`;
- location ownership: `DEFER` pending caller proof.

## 15. Required Regression Scope

Minimum closeout gate:

- focused `tests/Feature/Admission` regression;
- impacted Admin route/navigation tests only when Admission navigation changes;
- Admission route inspection;
- Pint on touched PHP files;
- frontend build only when compiled frontend assets/templates require it;
- manual Admission registration/search/admin UI acceptance.

## 16. Architectural Change Rules

`MODULE.md` is the architectural source of truth for Admission. Update it in the same PR whenever ownership, routes, dependencies, persistence boundaries, compatibility, or quarantine changes.

## 17. Deferred Debt

| Debt | Owner/target module | Reason | Exit condition |
|---|---|---|---|
| shared `job_batches` migration ownership | shared infrastructure | historical ownership drift; persistence-sensitive | ledger/schema proof + separately safe migration plan |
| placeholder 501 API | Admission | no supported API contract | caller proof |
| legacy credential URL | Admission | compatibility risk | caller proof and clean-route migration |
| DVHC/location sharing question | Admission / future shared owner | current cross-module use not proven | caller inventory |

## 18. Architecture Decisions

### 2026-09-02 — Compact Admission refactor

**Decision:** Keep Admission as a small domain module centered on grade-1 registration and lookup; consolidate existing services instead of rebuilding it.

**Reason:** Current runtime already contains useful registration/admin service boundaries, while remaining debt is localized to route drift, document duplication, legacy lookup compatibility, and shared-infrastructure ownership.

**Impact:** One compact refactor branch and one focused regression cycle are preferred over multiple phase branches.
