# Request v1 Traceability Matrix

Status values: `Specified` means documentation is complete; implementation evidence is intentionally pending `/create-module Request` approval and delivery.

## 1. Product and architecture

| ID | Requirement | Authoritative spec | Acceptance evidence | Status |
|---|---|---|---|---|
| ARC-01 | Focused internal Request, not generic Workflow | `REQUIREMENTS` 1–3; Master 1–3 | AR-05 | Specified |
| ARC-02 | Shell-only dependencies | `REQUIREMENTS` 4; ADR; AI contract 3 | AR-01–03; dependency scan | Specified |
| ARC-03 | Request owns all runtime data; no domain pointer | Master 4–5; DB spec | AR-02; schema review | Specified |
| ARC-04 | Repository-native bootstrap/module state | Master 9–10; AI contract 3 | AR-01, AR-03–04 | Specified |
| ARC-05 | Workflow deferred and no shared Approval extraction | ADR | AR-05; repo scan | Specified |
| ARC-06 | No digital signature/multi-company/domain posting | `REQUIREMENTS` 2–3 | scope/repo review | Specified |

## 2. Definition and forms

| ID | Requirement | Authoritative spec | Acceptance evidence | Status |
|---|---|---|---|---|
| DEF-01 | Group/type/draft/publish/retire lifecycle | `REQUIREMENTS` 6.1; Form spec 1–2 | RT-01–06 | Specified |
| DEF-02 | Immutable published version/checksum | Domain 4; Form 2, 9 | RT-03–05 | Specified |
| DEF-03 | Versioned bounded JSON schema, no EAV | `REQUIREMENTS` 2, 6.2; Form 3–7 | RF-02–03; unit schema tests | Specified |
| DEF-04 | Supported field types/server validation | Form 4–6 | RF-02–05 | Specified |
| DEF-05 | Audience discover/create authorization | Form 8; RBAC 6–7 | RF-01 | Specified |
| DEF-06 | Safe definition package creates draft only | Form 10; Reporting 7 | RT-07; security corpus | Specified |

## 3. Request lifecycle

| ID | Requirement | Authoritative spec | Acceptance evidence | Status |
|---|---|---|---|---|
| REQ-01 | Draft/save/submit and immutable payload/run | Requirements 6.3; Domain 5 | RF-04, RS-01–04 | Specified |
| REQ-02 | Approved/rejected/returned/cancelled states | Requirements 6.3; Domain 5 | AP-01–06; RF-06 | Specified |
| REQ-03 | Return/resubmit retains version/history/new run | Domain 5.5; Approval 6 | RS-05–06 | Specified |
| REQ-04 | Reject terminal; cancellation restricted/reasoned | Domain 5.4, 5.6 | AP-03, AP-05, AP-08 | Specified |
| REQ-05 | Optimistic concurrency/idempotency | Domain 9; API 4 | RS-03–04; CC-01–06 | Specified |
| REQ-06 | Historical snapshots remain readable | Domain 10; DB spec | RT-04–06, RS-05 | Specified |

## 4. Approval and actors

| ID | Requirement | Authoritative spec | Acceptance evidence | Status |
|---|---|---|---|---|
| APR-01 | Sequential ordered stages | Requirements 6.4; Approval 1 | AP-01 | Specified |
| APR-02 | `single` semantics | Approval 2.1, 5 | AP-01 | Specified |
| APR-03 | `parallel_all` semantics | Approval 2.2, 5 | AP-02–03; CC-03 | Specified |
| APR-04 | `parallel_any` semantics | Approval 2.3, 5 | AP-04–06; CC-02, CC-04 | Specified |
| APR-05 | Candidate snapshot and self-approval denial | Domain 6; Actors 5 | AP-07, AP-09; resolver unit tests | Specified |
| APR-06 | Fixed users/role/form user field resolvers | Actors 2–3 | AP-09; resolver/security tests | Specified |
| APR-07 | Manager/department reserved, not implemented | Actors 4; ADR | registry/UI/repo scan | Specified |
| APR-08 | Authorized audited replacement reassignment | Domain 7; Approval 7 | AP-08; CC-05 | Specified |
| APR-09 | Safe later-stage activation failure/retry | Approval 3; API 8 | AP-10; RE-05 | Specified; representation due in CREATE_PLAN |

## 5. Data, security, and reliability

| ID | Requirement | Authoritative spec | Acceptance evidence | Status |
|---|---|---|---|---|
| DAT-01 | Request-owned normalized schema plus bounded JSON | DB spec 1–7, 9 | migration/schema tests | Specified |
| DAT-02 | Public ULID, unique number, indexed access | DB spec 3–4, 8, 10 | constraint/concurrency/query tests | Specified |
| SEC-01 | Permission + policy + visibility + field scope | Requirements 8; Actors 6–7; Security 3 | AR/RF/AP IDOR matrix | Specified |
| SEC-02 | Private upload/download and package safety | Security 6–8 | CO-03–05; corpus tests | Specified |
| SEC-03 | Private authorized safe exports | Security 9; Reporting 4–6 | RE-02–04 | Specified |
| SEC-04 | Minimal immutable audit | Security 4–5 | snapshot/redaction tests | Specified |
| REL-01 | Transaction/lock/idempotency/outbox | Requirements 11; Domain 9; API 8 | CC-01–06 | Specified |
| REL-02 | Safe operation retry only | API 8; Security; Reporting | RE-05 | Specified |

## 6. API, notification, and operations

| ID | Requirement | Authoritative spec | Acceptance evidence | Status |
|---|---|---|---|---|
| API-01 | Shared service path for Livewire and `/api/request/v1` | Requirements 9; API 1–4 | feature/API parity tests | Specified |
| API-02 | Bounded resources/errors/pagination | API 3–6 | API contract tests | Specified |
| EVT-01 | Versioned minimal events/outbox | API 7–8 | event schema/outbox tests | Specified |
| NOT-01 | Database/in-app and email first; realtime gated | API 9–10 | delivery/idempotency tests | Specified |
| OPS-01 | Correlation/log/metric safety | API 12; Security | observability snapshot review | Specified |

## 7. UX, responsive, and PWA

| ID | Requirement | Authoritative spec | Acceptance evidence | Status |
|---|---|---|---|---|
| UX-01 | Mobile-first create/My Requests/inbox/decision | Requirements 6.7–6.8; UX 2–4 | UI-01–02 | Specified |
| UX-02 | Tablet/desktop-first accessible designer | UX 3.7, 4, 7 | UI-03 | Specified |
| UX-03 | Explicit loading/error/offline/stale/conflict states | UX 6 | UI-02, UI-04 | Specified |
| PWA-01 | Shell owns manifest/service worker | UX 8 | AR-04; asset/registration scan | Specified |
| PWA-02 | Sanitized expiring reads and non-sensitive drafts | UX 9–10 | UI-04–06 | Specified |
| PWA-03 | No offline mutation replay; clear on lifecycle events | UX 9–11 | UI-04, UI-07 | Specified |

## 8. Reporting and release

| ID | Requirement | Authoritative spec | Acceptance evidence | Status |
|---|---|---|---|---|
| RPT-01 | Bounded authorized operational reports | Reporting 1–3 | RE-01–02; query tests | Specified |
| EXP-01 | Private queued exports/shared capability reuse | Reporting 4–6 | RE-02–04 | Specified |
| TPL-01 | Safe starter templates as drafts | Template catalog | template validation/mapping tests | Specified |
| TST-01 | Full functional/concurrency/security/accessibility suite | Test spec | CI/manual evidence | Specified |
| RELS-01 | Deployment/storage/worker/enablement verification | Master 10; Test 7–8 | release runbook evidence | Specified |

## 9. Explicitly deferred traceability

| Deferred ID | Capability | Re-entry requirement |
|---|---|---|
| FUT-ORG | Manager/department resolver | Canonical Shell organization contract + ADR/tests |
| FUT-WF | Graph/conditions/timers/SLA/subflow/delegation | Workflow ownership/scope ADR after Request v1 evidence |
| FUT-SIG | Digital/legal signature | Legal/security/storage/provider decision |
| FUT-INT | Domain adapters/business posting | Separate module analysis and public contract; no direct dependency shortcut |
| FUT-OFF | Offline business mutation queue | Security/conflict/product review; not implied by current PWA |
| FUT-RT | Realtime/web push | Canonical Shell broadcasting/push contract |

Deferred rows are not implementation backlog hidden inside v1. Each requires explicit analysis/approval.

## 10. Implementation evidence

### MR-01 — Shell contracts and module bootstrap

| Traceability | Implementation evidence | Automated evidence | Result |
|---|---|---|---|
| `ARC-02` Shell-only dependencies | `Modules/User/Contracts/UserDirectory.php`, `Modules/User/Data/UserIdentity.php`, `Modules/Role/Contracts/RoleDirectory.php`, `Modules/Role/Data/RoleIdentity.php`, and their owning Shell adapters/providers | `tests/Feature/User/UserDirectoryTest.php`, `tests/Feature/Role/RoleDirectoryTest.php`, `tests/Feature/Request/Architecture/RequestArchitectureTest.php` | Implemented for MR-01; architecture guard remains active for later slices |
| `ARC-04` repository-native bootstrap/module state | Request manifest/config/provider/routes/translations skeleton; provider contains no duplicate resource registration | `tests/Feature/Request/Architecture/RequestBootstrapTest.php`, `tests/Feature/Request/ModuleState/RequestModuleStateTest.php`, `tests/Feature/Request/Authorization/RequestPermissionTest.php` | Implemented for MR-01; Request remains default OFF |
| `ARC-05` Workflow deferred | No Workflow runtime artifact, dependency, provider, route, table, or shared Approval module added | `tests/Feature/Request/Architecture/RequestArchitectureTest.php` and repository `rg` scan | Preserved |

MR-01 intentionally contains no Request business migrations, models, approval runtime, Livewire components, or UI. Later traceability rows remain `Specified` until their owning merge requests provide evidence.

### MR-02 — Definition persistence and publication

| Traceability | Implementation evidence | Automated evidence | Result |
|---|---|---|---|
| `DEF-01`, `REQ-06` definition lifecycle and history | Request-owned group/type/version/audience/stage models and services implement create, draft save, validate, publish, clone, compare, retire, and archive without runtime request tables | `tests/Feature/Request/Definition/RequestDefinitionServiceTest.php` | Implemented for RT-01..06 within MR-02; requester runtime remains deferred to MR-03 |
| `DEF-02` immutable publication and checksum | `PublishTypeVersion` locks the type/draft, validates and canonicalizes the definition, advances pointers, and writes audit/outbox atomically; published model mutations are rejected | `tests/Feature/Request/Definition/RequestDefinitionServiceTest.php` | Implemented for MR-02 with optimistic stale-write rejection |
| `DAT-01`, `SEC-04`, `REL-01` definition/reliability persistence | Migrations 1–3 create only definition, audit, outbox, and idempotency tables; factories and restrictive relationships support isolated tests | `tests/Feature/Request/Definition/RequestDefinitionMigrationTest.php`; fresh/migrate/rollback/migrate validation | Implemented for the MR-02 persistence slice |
| `SEC-01` definition authorization | Request group/type/version policies and permission-protected admin routes guard the minimal group/type/version UI; lookups use public ULIDs | `tests/Feature/Request/Definition/RequestDefinitionPolicyTest.php`; `tests/Feature/Request/Architecture/RequestBootstrapTest.php` | Implemented for MR-02 definition administration |
| `ARC-02`, `ARC-04`, `ARC-05` architecture invariants | Request consumes only approved Shell contracts, remains default OFF, and its provider binds only owned registries/policies | `tests/Feature/Request/Architecture/*`; `tests/Feature/Request/ModuleState/*`; repository import scan | Preserved |

MR-02 intentionally contains no request instance/payload/run/task persistence, requester/approver runtime, shared Approval module, Workflow changes, or MR-03 UI.

### MR-03 — Form rendering and requester drafts

| Traceability | Implementation evidence | Automated evidence | Result |
|---|---|---|---|
| `DEF-03`, `DEF-04` bounded dynamic payloads | `FormPayloadNormalizer`, `FormPayloadValidator`, and `VisibilityRuleEvaluator` normalize/validate all approved initial field types, reject unknown input, and strip hidden/computed browser values | `tests/Feature/Request/Draft/RequestDraftServiceTest.php` | Implemented for RF-02/03 draft behavior; attachment ownership remains RF-05/MR-06 |
| `DEF-05`, `SEC-01` audience and requester scope | Catalog queries intersect active published versions with direct User/Role audiences through Shell contracts; My Requests scopes by requester before lookup; policies use the explicit `admin` permission guard | `tests/Feature/Request/Draft/RequestAudienceAndQueryTest.php`, `InternalRequestPolicyTest.php`, `RequestDraftLivewireTest.php` | Implemented for RF-01 and draft IDOR boundaries |
| `REQ-01`, `REQ-05`, `REL-01` requester drafts | `CreateInternalRequest`, `SaveRequestDraft`, and `CancelInternalRequest` use locks, expected versions, scoped idempotency fingerprints, immutable payload revisions, audit, and outbox | `tests/Feature/Request/Draft/RequestDraftServiceTest.php` | Implemented for RF-04/RF-06; submit/run activation remains MR-04 |
| `DAT-01`, `DAT-02` runtime base and numbering | Migration 4 adds only request aggregate/payload/run base; numbers derive from unique DB IDs as `REQ-{UTC_YEAR}-{ID_PADDED_8}` | `RequestDefinitionMigrationTest.php`, `RequestAudienceAndQueryTest.php`; fresh/migrate/rollback/migrate validation | Implemented for MR-03; task/decision tables remain absent |
| `UX-01`, `UX-03` requester draft UI | Audience-scoped catalog, mobile My Requests cards, dynamic draft detail, bounded search/filter/reset/pagination, explicit save/conflict/cancel/loading states | `RequestDraftLivewireTest.php`, `RequestBootstrapTest.php` | Implemented for the MR-03 online draft surface; submit and offline persistence remain later slices |
| `ARC-02`, `ARC-04`, `ARC-05` architecture invariants | Request consumes only approved Shell contracts, remains default OFF, and adds no Workflow, task/decision, API mutation, service worker, or MR-04 artifact | `tests/Feature/Request/Architecture/*`, `tests/Feature/Request/ModuleState/*`, repository scans | Preserved |

MR-03 intentionally does not submit requests or create runs, tasks, candidates, or decisions. It adds no approver runtime, API mutation, attachment storage, offline persistence, Workflow change, or MR-04 behavior.
