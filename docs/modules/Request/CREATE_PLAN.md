# Request v1 — CREATE PLAN

Status: **IMPLEMENTATION VERIFIED — SEE SECTION 33**
Command: `/create-module Request`  
Target module: `Modules/Request`  
Module type: `domain`  
Plan base inspected: `main` at `62aaa741b5ba904840269638c10d0ed5bfaaee8b` (2026-08-24)  
Specification: `docs/modules/Request`, version `1.0.0`
Approval: explicitly approved by the repository owner on 2026-08-24; planning PR `#23` merged as `06e6f67b5d36f82f03149131681a206b87879020`

## 0. Execution contract and approval gate

This document is the approved implementation plan required by `.codex/tasks/create-module.md`. It turns the approved Request v1 specification into ordered, independently testable merge requests. Approval authorizes implementation beginning with MR-01; it does not authorize scope outside this plan.

The planning approval gate is satisfied. At implementation start, Codex must:

1. fetch the current `main`, record its commit, and inspect `git status`;
2. re-read this file, `README.md` in its stated order, repository instructions, and the current bootstrap code;
3. compare the current bootstrap/reference files with the source observations in this plan;
4. update this plan first if repository changes invalidate a locked decision;
5. deliver the vertical slices in section 27 without silently expanding scope.

The implementation agent may make ordinary code-level choices consistent with this plan. It must stop for a new decision only when a stop condition in section 31 occurs.

## 1. Goal, users, and module boundary

Build a complete internal Request product for one company:

- authorized administrators design and publish versioned request types and ordered approval stages;
- authenticated employees create, save, submit, track, comment on, and cancel eligible requests;
- resolved approvers process an inbox and approve, reject, or return tasks;
- operators audit and safely retry delivery or stage-activation failures;
- reports and exports remain bounded, private, scoped, and explainable;
- mobile create/inbox/decision journeys and tablet/desktop design journeys are first-class;
- installed-PWA/browser use is online-first, with safe read snapshots and non-sensitive offline drafts only.

Request owns only its internal request domain. It must not read, write, import, query, or point to a business-domain module.

### Explicit exclusions

- Workflow/BPMN/graph runtime, conditions, timers, SLA, escalation, delegation, quorum, subflows.
- Manager/department resolution until a canonical Shell organization contract is approved.
- Digital/legal signature and PKI.
- Multi-company/tenant behavior.
- Domain posting or integrations with Order, HR, Finance, Administrative, Account, or any other domain module.
- Anonymous/public requests or token-only public lookup.
- Import of live request instances, runs, tasks, candidates, decisions, comments, attachments, or audit records.
- Offline replay of submit, decision, cancel, reassign, comment, upload, publish, import, export, or retry commands.
- A second service worker, manifest, app shell, module registry, or modular-framework package.

## 2. Locked implementation decisions

These decisions resolve the open questions in `11-AI_IMPLEMENTATION_CONTRACT.md`.

| Topic | Locked decision |
|---|---|
| Aggregate/model name | `InternalRequest`; table `request_instances`. Never create a `Modules\\Request\\Models\\Request` class. |
| Direct dependencies | Exactly `Admin`, `Auth`, `User`, `Role`, `Shared`; each must still declare `type => shell`. |
| Default state | `default_enabled => false`; do not enable by editing the manifest during implementation. Runtime enablement uses `ModuleStateRepository`/existing command or UI only. |
| Auth guards | Web/Livewire: `auth:admin`; API: `auth:sanctum`; permissions use guard `admin`. |
| User/role access | Request calls new stable User/Role Shell contracts. It never imports `App\\Models\\User`, Spatie models, `Modules\\Account`, or identity tables directly. |
| Request number | Insert with a collision-proof temporary value, obtain the database ID, then update in the same transaction to `REQ-{UTC_YEAR}-{ID_PADDED_8}`. Unique index is authoritative; never use `MAX()+1`. Public addressing uses ULID. |
| Draft version pin | A new request draft pins the current published version at draft creation. Publishing a newer version does not invalidate that draft. Type retirement or availability expiry blocks unsubmitted drafts; the UI offers a reviewed duplicate/migration into the current version only while the type is available. |
| Resubmit | Same request/public ID/number and pinned type version; new immutable payload revision and new run; restart at stage 1. |
| Later-stage resolver failure | Keep request `pending`; close current evaluation transaction with run `failed_activation`, target `current_stage_position`, safe `activation_error_code`, `activation_failed_at`, and incremented retry count. Do not skip or fabricate an approver. An authorized idempotent retry re-resolves that same stage. |
| `parallel_any` rejection | A rejection closes only that user's task. Stage rejects only when all effective tasks reject. First approval skips remaining active peers. |
| Return | Immediate for every stage mode; cancels peers, closes the run, changes request to `returned`. |
| Reject | Terminal in v1. |
| Cancel | Requester: own `draft`/`returned`; privileged administrator: `pending` with required reason. |
| Self-approval | Denied. Requester is removed from candidates; an empty result fails safely. |
| Candidate timing | Snapshot at each stage activation. A later role stage sees role membership current at that later activation. |
| Definition package | Request-owned JSON package, optionally ZIP only after archive-safety tests; dry-run/diff/mapping; creates a draft only. |
| Live request import | Not implemented. |
| Exports | Request-owned private export orchestration. Current Shared panel/base service is not used where it produces public or unbounded artifacts. Safe low-level helpers may be reused only after tests prove the contract. |
| PWA | Reuse the global Shell manifest/service worker. Request data uses a namespaced IndexedDB store; no authenticated HTML/attachment cache and no mutation replay. |
| Frontend framework | Blade, Livewire 3, existing Admin shell, Bootstrap/AdminLTE/Tailwind conventions, and a small vanilla ES module for IndexedDB/connectivity. Do not add a second JS framework. |
| Queues | Existing Laravel queue. Stable queue names from config: `request-outbox`, `request-notifications`, `request-exports`. Jobs receive IDs only and are idempotent. |
| File storage | Existing private/default disk under `request/attachments`, `request/exports`, `request/tmp`; no public URL/symlink. |
| Seed data | Permissions come from the manifest. Menu/template seeders are deterministic and idempotent; starter templates create drafts only and are opt-in. No Faker and no production demo data. |
| Workflow | Remains deferred. No Workflow table, route, provider, permission, event, or shared Approval extraction. |

## 3. Sources of truth and conflict order

Implementation must read the Request `README.md` list in full. If instructions conflict, apply this order:

1. current repository source, root instructions, and current deployment/runtime architecture;
2. `Modules/ModuleServiceProvider.php`, `app/Modules/ModuleStateResolver.php`, `ModuleStateRepository.php`, and current Shell manifests;
3. `REQUIREMENTS.md` and accepted `ADR-001`;
4. numbered Request v1 specifications and this plan;
5. deferred Workflow v4 and supplied Workflow v3 archive;
6. generic Laravel examples or old prompts.

Material conflict with levels 1–3 requires the stop/change-control process in section 31. Generic examples never justify `module.json`, `nwidart/laravel-modules`, public sensitive storage, or domain coupling.

## 4. Reference modules: reuse and rejection

### 4.1 `Modules/Administrative` — bounded reference only

Reuse these conventions after re-inspection:

- thin controllers/Livewire components calling an application service;
- transactions, `lockForUpdate`, optimistic locking, and private authorized downloads;
- bounded page sizes `10/25/50/100`;
- status history/audit-oriented UI patterns;
- admin route grouping and permission middleware.

Do **not** copy:

- its direct `Modules\\Account\\Models\\User` dependency;
- anonymous submission, public lookup, access tokens, or receipt links;
- soft-delete or delete-all behavior for evidence;
- procedure CRUD schema or public-file assumptions;
- any import/export behavior without proving privacy and bounds.

### 4.2 `Modules/Admin`

Reuse `Admin::layouts.master`, route/layout conventions, Livewire/Blade component styles, permission middleware, responsive shell behavior, and menu model/cache conventions. Do not copy unrelated System/domain imports already present in shell views.

### 4.3 `Modules/Shared`

Inspect `Modules/Shared/Services/ImportExport` before each relevant slice. Reuse only safe primitives such as normalization or spreadsheet formula neutralization when their tests and signatures fit. The current generic panel/base service is **not** approved for Request live data because its observed defaults include an unbounded `get()`, public-disk artifacts, and destructive `replace` UI. Request must not fork a second generic CRUD import engine.

### 4.4 `Modules/User` and `Modules/Role`

Reuse their current canonical auth/Spatie adapters behind new Shell contracts described in section 7. Request consumes only those contracts and immutable DTOs.

## 5. Bootstrap contract

| Bootstrap item | Request decision | Owner/registration |
|---|---|---|
| Directory | `Modules/Request` | repository filesystem discovery |
| Manifest | `Modules/Request/config/module.php` | required |
| Module type | `domain` | manifest |
| Dependencies | `Admin`, `Auth`, `User`, `Role`, `Shared` | manifest; Shell-only architecture test |
| Default enabled | `false` via `default_enabled`; retain `enabled => false` only if current resolver compatibility needs both | manifest/runtime resolver |
| Module provider | `Modules/Request/Providers/RequestServiceProvider.php` | root provider discovers it once |
| Module config | `config/settings.php`, `forms.php`, `files.php`, `exports.php`, `notifications.php`, `offline.php` | root provider auto-merges under `request.*` |
| Web routes | `routes/web.php` | root provider once |
| API routes | `routes/api.php`; file prefix `request/v1` because root adds `/api` | root provider once |
| Migrations | module `database/migrations` | root provider once |
| Views | namespace `Request` from `resources/views` | root provider once |
| Translations | `resources/lang/en`, `resources/lang/vi` | root provider once |
| Livewire | `Livewire` namespace/folder | root provider auto-discovery once |
| Blade components | only if a Request-owned reusable primitive is required | root auto-discovery; no manual registration |
| Console | no business command in v1; scheduled cleanup/dispatch commands only if queue scheduler cannot express it | provider/console auto-discovery only when justified |
| Runtime state | existing `ModuleStateResolver` and `ModuleStateRepository` | never read/write state JSON directly |
| PWA shell | existing global manifest/service worker | Request adds no global registration |

`RequestServiceProvider` binds only Request-owned interfaces, policies, resolver registry, and implementation adapters. It must not load routes, config, views, migrations, translations, Livewire, Blade components, or console classes already handled by the root provider. Bindings must be lazy and must not depend on module config having been merged before provider registration.

## 6. Manifest, config, and permissions

### 6.1 Manifest

`Modules/Request/config/module.php`:

- `name => 'Request'`
- `type => 'domain'`
- `enabled => false`
- `default_enabled => false`
- `depends => ['Admin', 'Auth', 'User', 'Role', 'Shared']`
- `permissions_required => true`
- `permissions` exactly the list below
- `tables` exactly the Request-owned tables in section 10

Permissions:

```text
request.dashboard.view
request.group.view
request.group.create
request.group.update
request.group.archive
request.type.view
request.type.create
request.type.update
request.type.publish
request.type.retire
request.type.import
request.type.export
request.instance.view-own
request.instance.view-participant
request.instance.view-all
request.instance.create
request.instance.update-own
request.instance.submit
request.instance.cancel-own
request.instance.cancel-any
request.task.view
request.task.decide
request.task.reassign
request.comment.create
request.attachment.upload
request.attachment.download
request.audit.view
request.report.view
request.export
request.operation.view
request.operation.retry
```

`ModulePermissionManager` remains the permission source; do not create a second permission registry. When Request is runtime-disabled its permissions are absent from the active sync. Deployment must enable the module, then run `RolesAndPermissionsSeeder`/the current permission sync command.

### 6.2 Config keys and default limits

| File/key | Initial value/contract |
|---|---|
| `request.settings.page_sizes` | `[10,25,50,100]`; default `25`, max `100` |
| `request.settings.request_number_prefix` | `REQ`; format service uses UTC year + padded DB ID |
| `request.settings.max_stage_count` | `20` |
| `request.settings.max_candidates_per_stage` | `100` |
| `request.forms.max_fields` | `200`; sections `30`; nesting depth `8`; schema bytes `262144`; payload bytes `524288` |
| `request.forms.string_max` | `10000` globally, with stricter field-specific rules |
| `request.files.disk` | current private/default Laravel disk; reject `public` in production validation |
| `request.files.max_count` | `20` per request, `5` per attachment field unless schema is lower |
| `request.files.max_bytes` | `10 MiB` per file, `50 MiB` per request |
| `request.files.allowed_mimes` | PDF, PNG, JPEG, DOCX, XLSX; validate MIME, extension, and signature where supported |
| `request.files.scan_driver` | `none|clamav`; default `none`; risky content stays unavailable until accepted by configured policy |
| `request.exports.sync_row_limit` | `500`; above it must queue |
| `request.exports.max_rows` | `100000`; fail with scoped error, never silently truncate |
| `request.exports.expiry_hours` | `24`; private download reauthorizes |
| `request.notifications.channels` | `database,email` |
| `request.notifications.queue` | `request-notifications` |
| `request.offline.enabled` | true only for allowed read/draft storage |
| `request.offline.snapshot_ttl_hours` | `24`; draft TTL `168` hours |
| `request.offline.max_bytes_per_user` | `5242880` |
| `request.offline.forbidden_classifications` | `confidential`, `secret`, attachment/binary, computed server-only |

Limits are config-backed, validated, documented, and covered by boundary tests. Production values may be reduced by environment configuration; increasing security-relevant limits requires review.

## 7. Required Shell capability work

Request cannot satisfy the Shell-only invariant through the current placeholder User/Role models or by importing the Account domain. Slice MR-01 must add these generic capabilities to the owning Shell modules before Request runtime code.

### 7.1 User Shell

Files:

- `Modules/User/Contracts/UserDirectory.php`
- `Modules/User/Data/UserIdentity.php`
- `Modules/User/Services/AuthUserDirectory.php`
- `Modules/User/Providers/UserServiceProvider.php`
- `tests/Feature/User/UserDirectoryTest.php`

Contract:

```php
interface UserDirectory
{
    public function findActive(int $userId): ?UserIdentity;
    public function findManyActive(array $userIds, int $limit): array;
    public function searchActive(string $term, int $limit): array;
}
```

`UserIdentity` is immutable and contains only `id`, `displayName`, optional masked email/avatar reference, `active`, and safe locale/timezone metadata. It never exposes an Eloquent model.

`AuthUserDirectory` resolves the configured `admin` auth provider model dynamically from `config/auth.php`, verifies it is an Eloquent/auth identity, applies `is_active`/soft-delete predicates when those columns/traits exist, selects only safe columns, preserves input order for `findManyActive`, deduplicates IDs, and enforces `1..100`. The Shell service—not Request—may use `App\\Models\\User` or repository auth-provider details.

### 7.2 Role Shell

Files:

- `Modules/Role/Contracts/RoleDirectory.php`
- `Modules/Role/Data/RoleIdentity.php`
- `Modules/Role/Services/SpatieRoleDirectory.php`
- `Modules/Role/Providers/RoleServiceProvider.php`
- `tests/Feature/Role/RoleDirectoryTest.php`

Contract:

```php
interface RoleDirectory
{
    public function findAdminRole(int $roleId): ?RoleIdentity;
    public function activeMemberIds(int $roleId, int $limit): array;
    public function searchAdminRoles(string $term, int $limit): array;
}
```

The adapter uses Spatie's configured models/tables with guard `admin`, then passes member IDs through `UserDirectory` so inactive/deleted users never resolve. It returns IDs/DTOs, not Spatie models. It enforces the candidate bound and reports `not_found`, `wrong_guard`, or `candidate_limit_exceeded` as safe typed failures.

### 7.3 Shell prerequisite gates

- Both providers are discovered once by the root provider and bind their contracts.
- Contract tests run with the configured auth provider and Spatie table configuration.
- Request architecture tests allow imports only from `Modules\\Admin`, `Modules\\Auth`, `Modules\\User\\Contracts|Data`, `Modules\\Role\\Contracts|Data`, and approved `Modules\\Shared` contracts/helpers.
- If the adapters require an Account/domain model at runtime, stop; do not add Account to Request dependencies.
- These Shell contracts are generic capabilities. They must not contain Request-specific method names or policies.

## 8. Module structure and ownership

```text
Modules/Request/
├── Application/
│   ├── Commands/          # immutable command DTOs
│   ├── Data/              # view/API DTOs and results
│   ├── Queries/           # visibility-scoped bounded query services
│   └── Services/          # use-case coordinators and transaction boundaries
├── Contracts/             # Request-owned ports
├── Domain/
│   ├── Enums/
│   ├── Exceptions/
│   ├── Forms/
│   ├── Approval/
│   ├── Identity/
│   └── ValueObjects/
├── Events/                # versioned Request-owned integration event DTOs
├── Http/
│   ├── Controllers/
│   ├── Controllers/Api/V1/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/Api/V1/
├── Jobs/
├── Livewire/
│   ├── Requester/
│   ├── Approver/
│   ├── Admin/
│   └── Shared/
├── Models/
├── Notifications/
├── Policies/
├── Providers/
├── Support/
├── config/
├── database/factories/
├── database/migrations/
├── database/seeders/
├── data/templates/
├── resources/js/
├── resources/lang/en/
├── resources/lang/vi/
├── resources/views/
└── routes/
```

Transport layers validate HTTP/Livewire concerns, authorize, build commands, and call application services. They never write state columns directly. Models provide persistence relationships/scopes/casts but do not hold cross-aggregate orchestration. Application services own locks/transactions/idempotency/audit/outbox. Pure domain services own form validation, transition matrices, canonical JSON, actor resolution rules, and stage evaluation.

## 9. Provider bindings and registries

`RequestServiceProvider` binds:

- `Clock` -> Laravel/system UTC clock adapter;
- `CorrelationIdProvider` -> request/job correlation adapter;
- `ActorResolverRegistry` -> server-owned registry containing `fixed_users`, `role_members`, `form_user_field` only;
- `RequestNumberGenerator` -> DB-ID format generator;
- `PrivateRequestFileStore` -> Laravel Storage adapter;
- `RequestDefinitionPackage` -> JSON package adapter;
- `RequestExportWriter` -> private CSV/XLSX/PDF adapters gated by proven libraries;
- policies for all Request models;
- any Request query/service singleton that is stateless.

Registry entries are concrete server code, never browser-selected classes. Unknown resolver/field/operation/export keys fail closed. The provider must not run queries or create directories during boot.

## 10. Database plan and migration order

Use repository-supported integer primary keys, unique ULID `public_id` (`char(26)`), UTC timestamps, restrictive foreign keys, and database-compatible enum/check constraints plus PHP enums. User references may use foreign keys to the canonical `users.id` only if current Shell migrations guarantee that table in every supported installation; otherwise use indexed unsigned IDs with Shell-contract validation. Never add an FK to a domain-owned table.

Historical/evidence tables are not hard-deleted. Rollback is acceptable only before production data exists; production rollback is application rollback plus module disable/data preservation.

### Migration 1 — definitions

`2026_09_01_000001_create_request_definition_tables.php`

Creates:

- `request_groups`
- `request_types` initially without the two version-pointer FKs
- `request_type_versions`
- `request_type_audiences`
- `request_stage_definitions`

Required constraints/indexes follow `02-DATABASE_ERD_AND_SCHEMA.md`, including unique codes, `(type_id, version_number)`, `(version_id, stage_key)`, `(version_id, position)`, audience uniqueness, availability/list indexes, and bounded JSON columns. `request_types.active_draft_version_id` is not used to enforce uniqueness alone; service locks plus the pointer and unique version/status validation provide portability.

### Migration 2 — definition pointers

`2026_09_01_000002_add_request_type_version_pointers.php`

Adds nullable restricted FKs/indexes:

- `request_types.current_published_version_id -> request_type_versions.id`
- `request_types.active_draft_version_id -> request_type_versions.id`

### Migration 3 — core audit/reliability

`2026_09_01_000003_create_request_core_reliability_tables.php`

Creates:

- `request_audit_events` initially without the optional `request_instance_id` FK;
- `request_outbox_messages`;
- `request_idempotency_keys`.

These tables land before the first publish/draft mutation so authorization, audit, idempotency, and transactional outbox are never deferred to a cleanup slice. Definition audit rows use the allowlisted aggregate type/public ID. The later pointer migration adds the optional Request-instance FK after that table exists.

### Migration 4 — aggregate/runtime base

`2026_09_01_000004_create_request_runtime_tables.php`

Creates:

- `request_instances` without `current_payload_revision_id/current_run_id` FKs;
- `request_payload_revisions`;
- `request_runs`.

In addition to specified fields, `request_runs` includes nullable `activation_error_code`, `activation_failed_at`, unsigned `activation_retry_count default 0`, and `last_activation_correlation_id`. Index `(status, current_stage_position, activation_failed_at)` supports operations. `request_instances.request_number` accepts the temporary transaction-safe value until its final same-transaction update.

### Migration 5 — tasks and decisions

`2026_09_01_000005_create_request_task_tables.php`

Creates:

- `request_tasks`
- `request_task_candidates`
- `request_decisions`

Use unique `(request_run_id, stage_position, assignee_user_id, replacement_generation)`, unique `(request_task_id,user_id)`, unique terminal decision per task, and the inbox/run-stage indexes from the schema. Replacement links are nullable self-FKs added after table creation within the same migration or by a portable follow-up statement.

### Migration 6 — runtime pointers

`2026_09_01_000006_add_request_runtime_pointers.php`

Adds nullable restricted FKs/indexes:

- `request_instances.current_payload_revision_id -> request_payload_revisions.id`
- `request_instances.current_run_id -> request_runs.id`
- optional `request_audit_events.request_instance_id -> request_instances.id`, restricted/null according to the final evidence-retention policy.

### Migration 7 — collaboration/delivery

`2026_09_01_000007_create_request_collaboration_delivery_tables.php`

Creates:

- `request_comments`
- `request_attachments`
- `request_export_jobs`
- `request_notification_deliveries`

`request_export_jobs` stores requester, immutable filter/field/auth-scope snapshot, format, status, row count, checksum, private disk/path, expiry, attempts, error code, and idempotency hash. `request_notification_deliveries` stores logical notification key, channel, recipient ID, template/version, status/attempt/error metadata, and unique logical-delivery key. Neither table is business truth.

Audit rows from migration 3 are append-only by policy, service design, database permissions where practical, and tests. Attachment paths are opaque private paths and never URLs.

### Schema rules

- All JSON columns have explicit byte/depth/count validators, canonicalization, version, and redaction policy.
- Money is currency + decimal string/minor units; no float.
- Stable statuses are PHP-backed enums and database validation where portable.
- Foreign-key delete actions are `restrict` for evidence and `nullOnDelete` only for non-authoritative display actor references when the specification permits it.
- Public endpoints bind by ULID, never by sequential ID. Human numbers are display/search keys, not authorization.
- Never edit old migrations after a slice is merged; use additive follow-ups.

## 11. Models, enums, and value objects

Models:

- `RequestGroup`, `RequestType`, `RequestTypeVersion`, `RequestTypeAudience`, `RequestStageDefinition`
- `InternalRequest`, `RequestPayloadRevision`, `RequestRun`
- `RequestTask`, `RequestTaskCandidate`, `RequestDecision`
- `RequestComment`, `RequestAttachment`, `RequestAuditEvent`
- `RequestOutboxMessage`, `RequestIdempotencyKey`, `RequestExportJob`, `RequestNotificationDelivery`

Enums:

- `RequestTypeStatus`, `RequestTypeVersionStatus`, `RequestStatus`, `RunStatus`, `TaskStatus`
- `StageMode`, `DecisionType`, `AudienceActorType`, `AudienceCapability`
- `PayloadSource`, `CandidateSource`, `AttachmentClassification`, `AttachmentScanStatus`
- `OutboxStatus`, `IdempotencyStatus`, `ExportStatus`, `NotificationDeliveryStatus`

Value objects/pure services:

- `PublicUlid`, `RequestNumber`, `OptimisticVersion`, `CanonicalJson`, `SchemaChecksum`, `PayloadChecksum`, `MoneyValue`
- `RequestTransitionMap`, `RunTransitionMap`, `TaskTransitionMap`, `StageEvaluator`
- `SafeReason`, `CorrelationId`, `IdempotencyKeyHash`, `RequestFingerprint`

Models use guarded/fillable allowlists and casts; transport arrays are never passed directly to `create`, `fill`, or `update`.

## 12. Definition, schema, and publication services

Required services/classes:

- `CreateRequestGroup`, `UpdateRequestGroup`, `ArchiveRequestGroup`
- `CreateRequestType`, `CreateTypeDraft`, `SaveTypeDraft`, `ValidateTypeDraft`
- `PublishTypeVersion`, `CloneTypeVersion`, `RetireRequestType`, `CompareTypeVersions`
- `FormFieldRegistry`, `FormSchemaValidator`, `FormPayloadNormalizer`, `FormPayloadValidator`
- allowlisted visibility grammar parser/evaluator; no arbitrary expressions
- `DefinitionCanonicalizer` and checksum service

Publication transaction lock order:

1. request type;
2. active draft version;
3. related audience/stage rows in stable primary-key order.

It reruns full validation, canonicalizes schema/policy/presentation/stages/audience, freezes the version, advances the current pointer, clears the active draft pointer, marks the previous published version `superseded` for new creation, and appends audit/outbox in one transaction. Published-version update/delete paths are absent and protected by tests.

Field types are exactly those in the spec. Each registry handler owns configuration validation, payload normalization/validation, display sanitization, export policy, sensitivity classification, and offline eligibility. Unknown keys/operators/types are rejected.

## 13. Draft, submission, approval, and transaction services

Required command services:

- `CreateInternalRequest`, `SaveRequestDraft`, `SubmitInternalRequest`
- `DecideRequestTask`, `ReturnInternalRequest`, `CancelInternalRequest`
- `ResubmitInternalRequest`, `ReassignRequestTask`, `RetryStageActivation`
- `AddRequestComment`, `UploadRequestAttachment`, `RemoveUncommittedAttachment`

Shared internal services:

- `IdempotentCommandExecutor`
- `ApprovalStageActivator`
- `StageOutcomeEvaluator`
- `RequestAuditAppender`
- `RequestOutboxAppender`
- `RequestVisibilityQuery`
- `RequestParticipantResolver`

### Lock order

All state-changing runtime paths lock in this order to reduce deadlocks:

1. `request_instances`;
2. current `request_runs`;
3. current-stage `request_tasks` ordered by ID;
4. targeted candidate/decision rows ordered by ID;
5. idempotency row using a unique-key insert/lock strategy.

No notification, mail, export generation, or file streaming occurs inside the business transaction. Audit and outbox rows do.

### Idempotency/optimistic concurrency

- Every command accepts `Idempotency-Key` and `expected_version` (Livewire generates/persists an equivalent opaque key per user action).
- Store only a keyed hash, normalized request fingerprint, safe response reference/status, actor, command, aggregate public ID, expiry, and correlation ID.
- Same key + same fingerprint returns the completed safe result.
- Same key + different fingerprint returns `409 idempotency_conflict`.
- A stale expected version returns `409 stale_version` with current safe version/status.
- Processing timeouts are recovered only through an allowlisted command-specific strategy.

### Submit

Inside one transaction: lock request; verify module runtime enabled, ownership/audience/type availability/pinned version/status/version/idempotency; normalize/validate payload; pre-resolve stage 1; create immutable payload revision/run/tasks/candidates; finalize number if needed; set pending/current pointers; increment lock version; append audit/outbox. Any failure rolls back all rows.

### Decision and next-stage activation

`DecideRequestTask` applies the locked `single`/ALL/ANY rules. When a completed stage has a next stage, actor resolution occurs in the same transaction. If that resolution fails, the valid prior decision remains committed, the run becomes `failed_activation`, and safe failure metadata/audit/outbox are recorded. The request remains `pending`; no next task exists. Retry locks the same aggregate and activates only the recorded target stage once.

## 14. Actor resolver registry

Request-owned interface:

```php
interface ActorResolver
{
    public function key(): string;
    public function validateConfig(array $config, RequestTypeVersionContext $context): ValidationResult;
    public function resolve(ActorResolutionContext $context): ResolvedActors;
}
```

Built-ins:

- `FixedUsersResolver` uses `UserDirectory::findManyActive`.
- `RoleMembersResolver` uses `RoleDirectory::activeMemberIds`, then User DTOs.
- `FormUserFieldResolver` validates the field type/classification and uses `UserDirectory::findActive`.

Resolution deduplicates IDs, removes the requester, enforces active status and the configured maximum, sorts deterministically, and produces a safe source/user snapshot. `single` requires exactly one final actor. ALL/ANY require at least one. Missing/deactivated/deleted identities fail with a typed safe error. No fallback user or Super Admin is injected.

## 15. Authorization and visibility

Policies:

- `RequestGroupPolicy`, `RequestTypePolicy`, `RequestTypeVersionPolicy`
- `InternalRequestPolicy`, `RequestTaskPolicy`, `RequestCommentPolicy`, `RequestAttachmentPolicy`
- `RequestAuditEventPolicy`, `RequestExportJobPolicy`, `RequestOperationPolicy`

Authorization is permission **and** record state/scope:

- requester scope before fetching My Requests;
- current candidacy and active task/run/request before fetching/deciding Inbox tasks;
- participant/owner/admin scope before request detail, comments, files, and audit;
- audience discover/create rules before catalog/direct route/API binding;
- field-classification scope before serialization, notification, local cache, report, or export;
- current authorization is rechecked on every private file/export download.

Use explicit scoped query objects. Never fetch a record globally and then hide controls in the view. Route-model binding uses a policy-scoped ULID resolver or controller query.

## 16. Web, API, and route map

### 16.1 Web/Livewire

All routes are inside `['web','auth:admin']`, prefix `admin/requests`, name `request.`. Static permission middleware is applied at route level; policies handle records.

| Route | Name | Surface/permission |
|---|---|---|
| `GET /admin/requests` | `request.dashboard` | dashboard; `request.dashboard.view` |
| `GET /admin/requests/catalog` | `request.catalog` | eligible catalog; `request.instance.create` |
| `GET /admin/requests/create/{typePublicId}` | `request.create` | create/resume; policy audience |
| `GET /admin/requests/mine` | `request.mine` | My Requests; own scope |
| `GET /admin/requests/inbox` | `request.inbox` | active tasks; `request.task.view` |
| `GET /admin/requests/{requestPublicId}` | `request.show` | scoped detail |
| `GET /admin/requests/{requestPublicId}/attachments/{attachmentPublicId}` | `request.attachments.download` | authorized private stream |
| `GET /admin/requests/exports/{exportPublicId}` | `request.exports.download` | reauthorized private stream |
| `GET /admin/requests/admin/groups` | `request.admin.groups` | group admin |
| `GET /admin/requests/admin/types` | `request.admin.types` | type admin |
| `GET /admin/requests/admin/types/{typePublicId}/designer` | `request.admin.types.designer` | draft designer |
| `GET /admin/requests/admin/types/{typePublicId}/versions` | `request.admin.types.versions` | version/diff/publish |
| `GET /admin/requests/admin/reports` | `request.admin.reports` | `request.report.view` |
| `GET /admin/requests/admin/operations` | `request.admin.operations` | `request.operation.view` |

Put static admin routes before the `{requestPublicId}` catch-all and constrain ULIDs. State mutations occur through Livewire actions/application services or explicit POST endpoints only where streaming/upload transport requires them.

### 16.2 API v1

`routes/api.php` uses `auth:sanctum`, version prefix `request/v1`, named rate limiters, correlation middleware, and JSON-only problem responses. Root bootstrap supplies `/api` and `api` middleware.

Resources:

- `GET /catalog`, `POST /requests`, `GET /requests`, `GET /requests/{publicId}`
- `PATCH /requests/{publicId}/draft`, `POST /requests/{publicId}/submit`
- `GET /inbox`, `POST /tasks/{publicId}/decisions`
- `POST /requests/{publicId}/cancel`, `POST /requests/{publicId}/comments`
- authorized attachment initiate/complete/download endpoints if Livewire upload cannot satisfy private staging safely
- `POST /requests/{publicId}/resubmit`, `POST /tasks/{publicId}/reassign`
- admin definition/report/export/operation endpoints only when a Livewire transport is insufficient; never create a generic CRUD API.

Every mutation requires `Idempotency-Key`, `If-Match` or body `expected_version`, policy checks, and a normalized fingerprint. Responses use public IDs, stable error codes, bounded cursor pagination, safe DTO allowlists, and correlation IDs. Sanctum identity must map to the same canonical user/permission guard; parity tests are mandatory before exposing mutation endpoints.

## 17. Livewire and UI/UX plan

Primary components:

- Requester: `Dashboard`, `Catalog`, `CreateRequest`, `MyRequests`, `RequestDetail`, `CommentComposer`, `AttachmentManager`
- Approver: `Inbox`, `DecisionPanel`
- Admin: `GroupIndex`, `TypeIndex`, `TypeDesigner`, `FormBuilder`, `StageBuilder`, `VersionHistory`, `VersionDiff`, `ReportIndex`, `OperationsIndex`, `DefinitionPackagePanel`
- Shared: `StatusBadge`, `ValidationSummary`, `ConnectivityBanner`, `ConflictDialog`, `EmptyState`, `Timeline`

All views extend `Admin::layouts.master`, use localization keys, visible bordered controls, server-side authorization, and existing design tokens/components. Request does not copy the Admin shell.

### Responsive behavior

- Phone: card lists, sticky primary action area where safe, one-column form, condensed stage context, full-width decision sheet; create/inbox/decision are the highest priority.
- Tablet landscape: two-pane designer with palette/outline and configuration panel; keyboard alternative for reorder.
- Desktop: bounded content width, optional three-pane designer, tables with responsive column priority.
- No page-level horizontal scroll, hover-only controls, icon-only destructive actions, or hidden focus.
- All async states: initial loading, saving, saved, empty, validation error, authorization loss, network error, offline, stale snapshot, optimistic conflict, retrying, terminal.

### Admin list evaluation checklist

Each applicable list must explicitly implement/test:

| Capability | Required behavior |
|---|---|
| Search | debounced, bounded, indexed fields only |
| Filters | visible active chips/summary; server validated |
| Reset | one control resets search/filter/sort/page/selection |
| Pagination | bounded `10/25/50/100`, default 25; no unbounded `get()` |
| Selection | only authorized visible row public IDs; cleared on scope/filter change |
| Bulk actions | none for business decisions; safe admin archive/retire/export only after explicit confirmation and per-row policy |
| Import | definition package only; dry-run/diff/mapping, draft only |
| Export | current authorized filter scope; selected export revalidates every selected ID |
| Selected export | allowed only for safe report/type registers; never trusts browser-provided fields/scope |
| Success state | concise toast plus persistent result/link where needed; no optimistic claim before commit |

## 18. PWA/offline implementation

Observed repository reality: a global service worker and manifest already exist and are owned by the Shell/Website experience. Request must not change ownership or register another service worker.

Add `Modules/Request/resources/js/request-offline.js` as a Vite input and load it only on Request pages through `@vite` in a Request layout partial/stack. The module owns:

- namespaced IndexedDB database `request-v1` keyed by authenticated user ID + installation scope;
- sanitized read snapshots for catalog, My Requests summary, inbox summary, and detail summary with TTL/version/owner;
- non-sensitive draft field values with pinned schema version, server lock version, timestamp, expiry, and local checksum;
- connectivity/staleness events consumed by Livewire/Blade UI;
- explicit reviewed sync after reconnect and server conflict check;
- clear-on-owner-mismatch, expiry, `401/403`, and explicit “remove local Request data”.

For reliable logout clearing, add a generic Auth Shell logout response hook/middleware that emits a standards-compliant `Clear-Site-Data` header for cache/storage after successful logout, with regression tests for the existing admin/client portal flow. Request also clears on detected logout click when its JS is loaded. If the current browser matrix cannot support the header consistently, the online-only fallback disables Request persistence for that browser rather than retaining another user's data.

Never store attachment binaries, sensitive classifications, raw audit context, decision reasons, tokens, cookies, full authenticated HTML, or exports. Never register Background Sync for business commands. Offline mutation controls are disabled with a clear explanation; reconnect does not auto-submit.

Use Node's built-in test runner for pure IndexedDB policy/serialization logic (with a small in-memory adapter), so no new JS test framework is required. Manual DevTools inspection remains a release gate.

## 19. Import/export and definition package plan

### 19.1 Applicability

- Live request import: excluded.
- Type definition import/export: included.
- Request register, task/decision, audit, and single-request document export: included.
- Generic destructive `replace` import: excluded.

### 19.2 Definition package

Request-owned schema includes package version, source metadata, stable type/group hints, form/policy/presentation/stages/audience mapping placeholders, required mappings, checksum, and optional signature metadata without claiming legal signature. Import flow:

1. upload to bounded private temp storage;
2. validate MIME/extension/size and reject archive traversal/symlinks/compression bombs;
3. parse JSON with depth/count limits and unknown-key policy;
4. verify checksum/package version;
5. dry-run full schema/resolver/template validation;
6. explicitly map users/roles by stable selected ID—never unverified name/email;
7. display diff/warnings;
8. create a new local draft through normal services;
9. audit and clean temp artifact.

It never publishes, creates identities, writes runtime/evidence tables, or executes package content.

### 19.3 Private exports

`RequestExportQuery` applies authorization and field classification before rows reach writers. Synchronous export is capped at 500 rows; larger work is queued up to 100,000 authorized rows. The job stores an immutable filter/field/scope snapshot and revalidates module state. Download always checks current user authorization and expiry.

Formats:

- CSV always, with UTF-8 and formula-neutralization.
- XLSX only through the already installed/proven Shared/Maatwebsite/FastExcel capability after contract tests show bounded streaming and private output.
- single-request PDF through existing DomPDF with remote fetching disabled and sanitized local content.
- JSON definition package; ZIP is optional only after the archive-security corpus passes.

Artifacts use private random paths, checksum, row count, expiry, idempotent logical key, and audited create/download/expiry/retry. No export goes to `storage/app/public`.

## 20. Events, outbox, jobs, notifications, and scheduling

Request-owned versioned event keys:

- `request.type.published.v1`, `request.type.retired.v1`
- `request.submitted.v1`, `request.stage.activated.v1`, `request.stage.activation_failed.v1`
- `request.task.decided.v1`, `request.returned.v1`, `request.resubmitted.v1`
- `request.approved.v1`, `request.rejected.v1`, `request.cancelled.v1`
- `request.task.reassigned.v1`, `request.comment.created.v1`, `request.attachment.created.v1`

Payloads contain only IDs, safe status/type metadata, version, occurred time, and correlation ID; no raw form payload, token, file path, or confidential reason.

Jobs:

- `DispatchRequestOutboxBatch`
- `DeliverRequestNotification`
- `GenerateRequestExport`
- `ExpireRequestArtifacts`
- `PurgeExpiredRequestIdempotencyKeys`
- optional `ScanRequestAttachment` only when a configured scanner exists

All jobs implement bounded attempts/backoff, receive stable IDs, check module state, lock/lease rows safely, and are idempotent. Notification logical keys prevent duplicates. Database/in-app plus email are initial channels. Realtime/web push is absent.

Prefer scheduler callbacks/jobs registered through an existing Shell scheduling extension. Add a console command only if the current scheduler cannot invoke the service safely; if added, it must be namespaced `request:*`, non-interactive for production, bounded, observable, and auto-discovered once.

## 21. Private files, Docker, and runtime storage

Logical directories:

- `request/attachments/{requestPublicId}/{opaqueName}`
- `request/exports/{exportPublicId}/{opaqueName}`
- `request/tmp/{operationPublicId}/{opaqueName}`

Use Laravel Storage so directories are created lazily under the configured private disk. Stream through authorized controllers with `Cache-Control: private, no-store`, safe `Content-Disposition`, and current policy checks. Never expose absolute paths or generate permanent public links.

Docker/release work:

- keep the existing non-root/application ownership pattern;
- confirm shared persistent storage is mounted for web and workers;
- ensure entrypoint creates/chowns general storage; add only a scoped `request` directory creation if lazy creation fails in the target image;
- never use `chmod 777`;
- record capacity, backup, restore, retention, cleanup, and malware-scanner dependencies in the runbook;
- verify workers see the same private disk and release code.

## 22. Seeders and starter templates

Files:

- `Modules/Request/database/seeders/RequestMenuSeeder.php`
- `Modules/Request/database/seeders/RequestTemplateSeeder.php`
- `Modules/Request/database/seeders/DatabaseSeeder.php`
- versioned template JSON files under `Modules/Request/data/templates/`

`RequestMenuSeeder` uses `AdminMenu::withTrashed()->updateOrCreate` by stable slugs, restores/updates only Request-owned nodes, resolves the parent deterministically, and clears menu cache. It must not depend on the current `AdminMenuSeeder` empty-table behavior.

`RequestTemplateSeeder` is opt-in, deterministic, idempotent by stable template key/version, uses the same definition validation service, and creates drafts with unresolved explicit role/user mappings. It never publishes and never uses Faker.

Do not add demo templates to the root production `DatabaseSeeder`. The module seeder may be run explicitly after enablement. Permission synchronization remains owned by `RolesAndPermissionsSeeder`/`ModulePermissionManager`.

## 23. Security, audit, and privacy controls

- Deny by default at route, query, policy, serializer, file, export, notification, event, audit, and local-cache boundaries.
- CSRF for web, Sanctum for API, named rate limits for create/save/submit/decide/comment/upload/download/import/export/retry.
- DTO allowlists; no raw model serialization or transport mass assignment.
- HTML escaped by default; any approved Markdown uses a sanitizer and no raw HTML.
- Schema/resolver/operation/template keys are allowlists, never class names or expressions.
- Attachments: normalized filename, MIME + extension + content signature checks, size/count/checksum, quarantine/scan state, private storage, IDOR tests.
- Audit: append-only safe delta, actor/effective actor, action, entity public ID, reason policy, correlation ID, hashed idempotency key, timestamp, permitted IP/user-agent metadata; no secrets/raw payload.
- Spreadsheet cells neutralize `=`, `+`, `-`, `@`, tabs, and carriage-return formula vectors.
- PDF disables remote resources and unsafe HTML.
- Import parser rejects traversal, symlink, external entity, excessive depth/count/size, and unsupported versions.
- Logs/exceptions expose stable codes, not SQL, stack traces, storage paths, payloads, or identity secrets.
- Module disabled mid-flight causes routes/jobs to fail closed while preserving data.

## 24. Performance and data-shape budgets

Test data shape for performance evidence:

- 30 groups, 200 request types, 5 versions/type;
- 2,000 users, 50 roles, up to 100 resolved candidates/stage;
- 100,000 requests, 150,000 runs, 500,000 tasks;
- 300,000 comments/attachments combined and 2,000,000 audit/outbox rows;
- representative returned/resubmitted and parallel-stage distribution.

Budgets on the documented CI/staging MySQL/PostgreSQL target (record hardware/database/version):

- list pages: at most 15 SQL queries for 25 rows and query count must remain constant at 100 rows;
- detail initial load: at most 25 SQL queries with runs/tasks/comments/audit paginated or bounded;
- no list query scans payload JSON or resolves roles per row;
- catalog/My Requests/inbox/report query plans use documented indexes and avoid full-table scan at the target shape;
- synchronous export <=500 rows; larger export queues; memory remains bounded by chunk/stream processing;
- resolver stops before creating more than configured candidate maximum;
- p95 targets are recorded from the environment before release; do not invent production latency from local SQLite.

Use query logging/count assertions and `EXPLAIN` snapshots on the production-like database. SQLite may run fast unit tests but is not concurrency/locking/index proof.

## 25. Test plan and traceability evidence

### Unit

- enums/value objects/canonical JSON/checksums/money;
- form grammar, validation, visibility, classification/offline policy;
- version validator and registry unknown-key rejection;
- resolvers/dedup/self-removal/cardinality/inactive users;
- transition matrices and every StageEvaluator outcome;
- idempotency fingerprint/scope/result;
- audit/event/export redaction and formula neutralization.

### Feature/integration

- every `RT`, `RF`, `RS`, `AP`, `CO`, `RE`, `AR` case in `10-TEST_AND_ACCEPTANCE.md`;
- web/API/Livewire service-path parity;
- route permission + record policy + query-scope/IDOR matrix;
- private file/package/export security corpus;
- module enabled/disabled/missing dependency/duplicate-registration behavior;
- fresh migration and upgrade from pre-Request `main`;
- User/Role Shell contract behavior and deactivation/membership changes;
- queue/mail/database notification/outbox retries and duplicate deliveries;
- Workflow absence/deferred checks.

### Concurrency

Run `CC-01..06` on the supported production database using at least two independent database connections/processes synchronized by barriers. Sequential test calls are not accepted. Assert one committed business transition, consistent terminal state, unique decisions/runs/logical delivery, and retry-safe responses.

### Frontend/accessibility/offline

- PHPUnit/Livewire component tests for state and authorization;
- Node built-in tests under `tests/Browser/Request/*.test.mjs` for offline serialization, forbidden fields, TTL, owner mismatch, clear, and no mutation queue;
- Vite production build;
- automated accessibility tooling only if already installed/approved; otherwise browser audit plus keyboard/screen-reader manual evidence is mandatory, not silently claimed automated;
- manual viewports: 360x800, 390x844, 768x1024 landscape/portrait as applicable, 1280x800;
- DevTools offline/cache/IndexedDB inspection for `UI-04..07`.

### Architecture tests

Add source-scanning/bootstrap tests that fail on:

- Request manifest dependency whose resolved manifest type is not `shell`;
- `Modules\\Request` imports from any unapproved module namespace;
- domain table names/namespaces/routes/events in Request code;
- `module.json`, nwidart reference, manual duplicate resource registration;
- a Request service worker/manifest registration;
- public Request attachment/export paths;
- Workflow runtime artifacts while ADR-001 is active.

Update `12-TRACEABILITY_MATRIX.md` during implementation so every row links to an exact test/manual/runbook evidence location. A row cannot become `Implemented` based only on code existence.

## 26. Exact planned file inventory

The implementation may split a listed class into smaller same-layer files when necessary, but it may not omit a capability or change ownership without updating this plan. The minimum planned files are:

### Bootstrap/config/routes

- `Modules/Request/config/module.php`
- `Modules/Request/config/{settings,forms,files,exports,notifications,offline}.php`
- `Modules/Request/Providers/RequestServiceProvider.php`
- `Modules/Request/routes/{web,api}.php`
- `Modules/Request/resources/lang/{en,vi}/request.php`

### Domain/application

- models/enums/value objects in sections 11–14;
- commands/data/query objects for every service in sections 12–15;
- `Contracts/{ActorResolver,Clock,CorrelationIdProvider,PrivateRequestFileStore,RequestDefinitionPackage,RequestExportWriter}.php`;
- `Domain/Forms/{FormFieldRegistry,FormSchemaValidator,FormPayloadNormalizer,FormPayloadValidator,DefinitionCanonicalizer}.php`;
- `Domain/Approval/{ActorResolverRegistry,FixedUsersResolver,RoleMembersResolver,FormUserFieldResolver,ApprovalStageActivator,StageOutcomeEvaluator}.php`;
- `Application/Services/{CreateInternalRequest,SaveRequestDraft,SubmitInternalRequest,DecideRequestTask,ResubmitInternalRequest,CancelInternalRequest,ReassignRequestTask,RetryStageActivation}.php`;
- definition/collaboration/audit/outbox/export services named in this plan.

### Transport/UI

- web streaming controllers for attachment/export/package delivery;
- API v1 controllers, Form Requests, Resources, correlation/problem middleware;
- Livewire components/views in section 17;
- `resources/views/layouts/request.blade.php` or a Request partial extending the Admin layout;
- `resources/js/request-offline.js` plus small pure helpers;
- one module stylesheet only if existing tokens/utilities cannot express the design; no duplicated framework bundle.

### Persistence/delivery

- seven migrations in section 10;
- model factories for tests only;
- Request menu/template seeders and template packages;
- events/outbox jobs/notification/export/file adapters from sections 19–22.

### Shell/integration changes

- User/Role contract, DTO, adapter, provider, and tests in section 7;
- `vite.config.js` Request JS input;
- Auth logout clear-data hook and regression tests;
- scheduler/worker/runbook configuration only where current platform requires it;
- no `DatabaseSeeder` demo-data change.

### Tests/docs

- `tests/Unit/Request/*`
- `tests/Feature/Request/{Architecture,Definition,Draft,Submission,Approval,Concurrency,Authorization,Api,Files,Notifications,Exports,Operations,ModuleState}/*`
- `tests/Browser/Request/*.test.mjs`
- `docs/modules/Request/{CREATE_PLAN,12-TRACEABILITY_MATRIX}.md`
- `docs/modules/Request/IMPLEMENTATION_RUNBOOK.md`
- `docs/modules/Request/RELEASE_NOTES.md`

## 27. Vertical implementation slices / merge requests

Each MR is rebased on current `main`, leaves the application migratable and secure, and includes tests for introduced behavior. Request remains default OFF until release enablement.

### MR-01 — Shell contracts and module bootstrap

- User/Role Shell contracts/adapters/providers/tests.
- Request manifest/config/provider/routes/translations skeleton.
- architecture/bootstrap/runtime-state/permission tests.
- no Request business table or UI yet.

Gate: both Shell contracts resolve without domain dependency; Request boots once when enabled and fails safely with missing/disabled/non-Shell dependency.

### MR-02 — Definition persistence and publication

- migrations 1–3; definition/audit/outbox/idempotency models, enums, and factories.
- group/type/draft/version/audience/stage services.
- form/resolver config registries, canonicalization, validation, publish/clone/retire.
- admin group/type/version minimal vertical UI and complete authorization/audit/outbox.

Gate: `RT-01..06`, immutability, checksum, concurrency, permission/IDOR.

### MR-03 — Form rendering and requester drafts

- migration 4 aggregate/payload/run base.
- catalog/audience, dynamic form render/normalize/validate, draft create/save/cancel.
- My Requests and draft detail mobile UI; request number allocator.
- optimistic concurrency/idempotency/audit/outbox from first mutation.

Gate: `RF-01..04`, `RF-06`, number concurrency, bounded query counts.

### MR-04 — Submit and sequential single approval

- migrations 5–6.
- actor resolvers, submit transaction, first activation, inbox, single decision, sequential stages.
- Livewire/API parity for implemented commands.

Gate: `RS-01..04`, `AP-01`, `AP-07`, `AP-09`, concurrency double-decision.

### MR-05 — Parallel and recovery lifecycle

- ALL/ANY evaluation/races.
- return/resubmit/reject/cancel/reassign.
- later-stage `failed_activation` representation and retry.
- complete run/task/timeline views.

Gate: `RS-05..06`, `AP-02..10`, `CC-01..05` on production-like DB.

### MR-06 — Collaboration, private files, audit UI

- migration 7 collaboration/delivery tables (unused delivery capabilities remain internal until later slices).
- comments, private upload/quarantine/download, audit query/timeline.
- mobile/desktop states and full policy/field-scope controls.

Gate: `CO-01..05`, XSS/file corpus, IDOR/private cache headers, storage ownership.

### MR-07 — Outbox and notifications

- activate the already-migrated notification-delivery capability; outbox/audit/idempotency have existed since MR-02.
- transactional outbox dispatch, database/email notifications, failure visibility and idempotent retry.
- safe events/correlation/log redaction.

Gate: notification/outbox duplicate delivery, queue restart, module-disable, `CC-06`.

### MR-08 — Responsive designer and PWA safety

- complete form/stage designer interactions and version diff.
- Request Vite input/IndexedDB safe reads/drafts/connectivity/conflict UI.
- Auth Shell logout clearing hook and regression.
- accessibility and viewport fixes.

Gate: `UI-01..07`, Node tests, Vite build, browser/DevTools evidence; no second SW/manifest/mutation queue.

### MR-09 — Reports, exports, definition package, operations

- report/export/notification tables if not fully landed in MR-07.
- private bounded/queued CSV/XLSX/PDF exports.
- definition JSON/package dry-run/diff/mapping/draft creation.
- operations allowlist/retry, artifact expiry/cleanup, starter template opt-in.

Gate: `RT-07`, `RE-01..05`, archive/formula/PDF/file/IDOR corpus, bounded memory/query evidence.

### MR-10 — Release hardening and enablement evidence

- full suite/performance/concurrency/security/accessibility/regression.
- migration fresh/upgrade/restore evidence.
- menu seeder, permission sync, worker/storage/backup/rollback runbook.
- traceability statuses, release notes, team-chat merge note.

Gate: every v1 traceability row has evidence, zero critical/high security defect, Workflow remains deferred, module still default OFF in Git. Runtime enable only after operator checklist passes.

## 28. Verification commands

Run from repository root. Adapt only if the current repository has an equivalent documented command; record the exact command/output in evidence.

```bash
git status --short --branch
composer install --no-interaction
npm ci
php artisan config:clear
APP_ENV=testing php artisan tinker --execute="app(\\App\\Modules\\ModuleStateRepository::class)->set('Request', true);"
php artisan route:list --name=request
php artisan migrate:fresh --seed --env=testing
php artisan migrate:status --env=testing
php artisan test --testsuite=Unit --filter=Request
php artisan test --testsuite=Feature --filter=Request
php artisan test --filter=UserDirectoryTest
php artisan test --filter=RoleDirectoryTest
vendor/bin/pint --test Modules/Request Modules/User Modules/Role tests
node --test tests/Browser/Request/*.test.mjs
npm run build
rg -n "Modules\\\\(Account|Administrative|Order|Workflow|[A-Za-z]+)" Modules/Request
rg -n "module\\.json|nwidart|serviceWorker\\.register|navigator\\.serviceWorker" Modules/Request
rg -n "storage/app/public|disk\\(['\"]public|public_path" Modules/Request
php artisan test --filter=RequestMigration
php artisan test --filter=RequestArchitecture
php artisan test --filter=RequestConcurrency
php artisan test --filter=RequestAuthorization
php artisan test --filter=RequestOffline
php artisan test --filter=RequestExport
php artisan queue:work --queue=request-outbox,request-notifications,request-exports --once --tries=3
git diff --check
APP_ENV=testing php artisan tinker --execute="app(\\App\\Modules\\ModuleStateRepository::class)->forget('Request');"
git status --short
```

Also verify with the repository's module state command/UI:

1. Request absent/default OFF after fresh checkout;
2. runtime enable makes routes/permissions/resources effective without tracked-file changes;
3. runtime disable removes routes/actions or fails them closed, preserves data, and makes workers stop mutation;
4. missing/disabled/non-Shell dependencies produce actionable bootstrap errors;
5. toggling leaves `git status` clean.

For concurrency/query/performance gates, use the supported production database and record DB version, isolation level, process count, dataset seed, `EXPLAIN`, query counts, memory, and timings. Do not present SQLite or sequential calls as production evidence.

## 29. Deployment, enablement, backup, and rollback

### Pre-deploy

- backup database and private storage; perform sample restore;
- confirm supported PHP/extensions, DB, queue, mail, persistent shared storage, capacity, and optional scanner;
- drain/coordinate workers for schema deployment;
- deploy code while Request remains default/runtime OFF;
- put the application in its normal maintenance/drain mode before changing Request runtime state, because the current root provider registers migrations only for enabled modules.

### Deploy

1. enter maintenance/drain mode and stop ordinary queue consumption;
2. enable Request through a `ModuleStateRepository`-backed operation so the current bootstrap registers its migrations; this runtime state change must not edit Git;
3. run additive migrations with `--force`;
4. verify migration status and table/index/FK shape;
5. sync active Request permissions to Super Admin and run the idempotent Request menu seeder explicitly;
6. configure/start workers for the three Request queues while business traffic remains drained;
7. verify private disk ownership/read-write from web and worker containers;
8. run authenticated maintenance-bypass smoke: catalog, draft, submit, inbox, decision, file, notification, export;
9. inspect audit/outbox/queue/logs and responsive/PWA states;
10. leave maintenance mode only after every enablement check passes.

### Rollback/failure

- Disable Request through runtime state first; do not edit manifest and do not delete data.
- Stop Request queue consumption after in-flight jobs finish/fail closed.
- Roll back application code only to a version that tolerates the additive schema.
- Do not `migrate:rollback` evidence tables in a populated environment. Schema reversal is permitted only in an empty/test environment.
- Restore DB and private storage together to the same checkpoint when recovery requires restore; verify checksums and historical reads.
- Record pending active requests/tasks/export/outbox state before any rollback and after recovery.

## 30. Release notes and team-chat content required at completion

Prepare concise notes covering:

- delivered Request v1 scope and explicit exclusions;
- Shell User/Role contracts and Shell-only dependency guarantee;
- migrations, runtime default OFF, enablement, permission/menu sync;
- queue names/workers, mail/database notification, private storage/backup;
- mobile/tablet/PWA offline-read/draft limitation and logout clearing;
- no live request import, no digital signature, no Workflow/domain integration;
- test/concurrency/security/accessibility/performance evidence and environment;
- known risks and approved follow-ups.

## 31. Risks, stop conditions, and change control

| Risk/trigger | Required action |
|---|---|
| User/Role Shell cannot expose identity/membership without Account/domain coupling | Stop. Propose a Shell-owned identity ADR; never add domain dependency. |
| Current root provider bootstrap behavior differs from this plan | Update bootstrap table/file map and obtain approval if ownership/default-state behavior changes. |
| Shared import/export requires public storage, unbounded reads, or destructive replace | Do not use that path. Keep Request private/bounded; propose Shared hardening separately if useful. |
| Sanctum identity cannot enforce the same admin permission model | Keep affected API mutation route unregistered until an approved auth mapping exists. |
| Global logout cannot reliably clear Request local data | Disable offline persistence; retain online-only operation until Shell lifecycle support is approved. |
| Scanner is required by deployment policy but unavailable | Quarantine uploads and block download, or disable uploads; do not mark unscanned content clean. |
| Required field/resolver/stage behavior is outside approved grammar | Stop affected slice and update requirements/ADR/traceability before code. |
| Need manager/department, domain posting, delegation, SLA, timer, signature, multi-company, offline mutation, or Workflow runtime | Separate analysis and explicit approval; not a Request v1 implementation detail. |
| Migration requires destructive change on populated data | Add expand/migrate/contract plan and backup/restore evidence; no historical migration rewrite. |
| Performance requires projection/new infrastructure/package | Present measurements and a scoped plan; do not silently add infrastructure/dependencies. |
| Existing user changes overlap planned files | Preserve them, show conflict, and obtain direction before overwriting. |

For any material change: stop the affected slice, describe the conflict and smallest safe options, update the authoritative spec/ADR/traceability/this plan, obtain approval, then resume. Ordinary refactors that preserve behavior, ownership, security, and public contracts do not require a new product decision but must remain within the same MR gate.

## 32. Definition of implementation complete

Request v1 is complete only when:

- all MUST behavior and `RT/RF/RS/AP/CC/CO/UI/RE/AR` acceptance cases pass;
- every traceability row points to code plus automated/manual/operational evidence;
- no Request dependency/import/query/table/event/route targets a domain module;
- every mutation is authorized, transactional, idempotent, concurrency-safe, and audited;
- published definitions and historical evidence are immutable/readable;
- files/exports are private, bounded, expiring, reauthorized, and audited;
- mobile create/inbox/decision, tablet designer, accessibility, and offline safety gates pass;
- migrations, queues, mail, storage, backup/restore, runtime enable/disable, and rollback are verified;
- release notes/team-chat handoff is prepared;
- Workflow remains deferred and no overlapping runtime artifact exists.

Implementation is authorized beginning with MR-01 and must follow the ordering, gates, stop conditions, and change control in this document.

## 33. Implementation completion and approved deviation reconciliation

Implementation verification was completed on 2026-08-25.

Execution evidence and operator handoff are recorded in:

- `IMPLEMENTATION_COMPLETION_ADDENDUM.md`
- `RELEASE_EVIDENCE.md`
- `IMPLEMENTATION_RUNBOOK.md`
- `RELEASE_NOTES.md`
- `12-TRACEABILITY_MATRIX.md`

### Final implementation gates

- Gate #1 — Request feature suite: PASS (`84 passed`, `4904 assertions`).
- Gate #2 — User/Role regression, Pint, `git diff --check`, frontend build: PASS.
- Gate #3 — migrations, deployment contract, and operational sanity: PASS.
- Gate #4 — browser/responsive/PWA acceptance: PASS.
- Gate #5 — reporting/export/operations automated and real UI export: PASS.
- Gate #6 — final regression/release hardening: PASS.

### Approved SLA deviation

The original v1 plan deferred SLA/timers. During implementation a constrained, Request-owned task SLA capability was explicitly accepted and verified.

The accepted scope consists of stage SLA configuration, immutable task SLA snapshots, warning/due/grace timestamps, warning/overdue/suspended events, enforcement, notification delivery, email toggles, and timezone-correct presentation.

This does not authorize or introduce generic Workflow/BPMN, graph conditions, delegation, subflows, manager/department resolution, domain posting, or offline business mutation replay. Those capabilities remain deferred.

For the agreed implementation scope and executed release gates, Request v1 is **IMPLEMENTATION COMPLETE**. Production enablement remains subject to the operator checklist in `IMPLEMENTATION_RUNBOOK.md`.
