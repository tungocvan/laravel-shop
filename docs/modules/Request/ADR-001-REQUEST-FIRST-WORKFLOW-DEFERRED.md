# ADR-001: Implement Request First and Defer Workflow

Status: Accepted  
Date: 2026-08-24  
Decision owners: Product and architecture approval recorded in the Request analysis  
Applies to: `Modules/Request`, `Modules/Workflow`, `docs/modules/Request`, `docs/modules/Workflow`

## Context

The existing Workflow v4 analysis describes both a general workflow engine and an internal request product. The requested first delivery is now narrower: a Base Request-like module that provides configurable internal forms and approvals, with sequential stages and parallel ALL/ANY approval.

Implementing both specifications concurrently would create duplicate ownership of request types, forms, request instances, tasks, decisions, comments, attachments, audit data, routes, permissions, and UI. Extracting a generic Approval module before a second proven consumer would also freeze an abstraction before its boundary is known.

The repository currently has Shell Modules for application/admin composition, authentication, users, roles, and shared capabilities. It has no canonical Shell-owned organization hierarchy or manager resolver. The approved architecture rule allows the new module to depend only on Shell Modules.

## Decision

1. Implement `Request` as the first and only owner of the internal request-and-approval domain.
2. Keep Request v1 bounded to versioned request types/forms and an ordered approval-stage pipeline.
3. Permit only direct dependencies on `Admin`, `Auth`, `User`, `Role`, and `Shared`, provided their manifests remain `type = shell`.
4. Implement fixed-user, role-member, and approved form-user-field resolution. Keep manager/department resolution as an unimplemented extension contract until a canonical Shell-owned organization contract is approved.
5. Defer the broader `Workflow` module. Preserve its v4 documents as future analysis, but do not run `/create-module Workflow`, create Workflow tables/routes, or implement overlapping runtime ownership while this ADR is active.
6. Do not extract a shared Approval module in v1. Reconsider extraction only after at least two implemented consumers demonstrate a stable common contract and a separate ADR is approved.
7. Keep Workflow and Request storage isolated. A future Workflow implementation must not read/write Request tables, and Request must not read/write Workflow tables. Any later relationship requires explicit public contracts/events and a migration/ownership ADR.

## Scope split

| Capability | Request v1 | Future Workflow |
|---|---|---|
| Internal request catalog and dynamic forms | Owner | Must not duplicate |
| Ordered approval stages | Owner | May consume/replace only after a migration ADR |
| Parallel approval | `all` and `any` within a stage | General split/join graph if later approved |
| Actor resolution | User, Role, form user field | Extended registry and organization resolvers |
| Conditions and graph | Not included | Candidate future scope |
| Timers, SLA, escalation, delegation | Not included | Candidate future scope |
| Subflows/BPMN-like topology | Not included | Candidate future scope |
| External business-domain orchestration | Not included | Requires new architecture approval |
| Digital signature/PKI | Not included | Separate security/legal decision |

## Consequences

### Positive

- One clear aggregate owner and one set of request tables, permissions, routes, and screens.
- Smaller implementation and security surface while preserving the main employee/approver value.
- Faster mobile-first delivery with deterministic testable approval semantics.
- No premature graph runtime or shared abstraction.
- Historical Workflow analysis remains available without directing Codex to implement overlapping systems.

### Costs and limitations

- Request v1 cannot model arbitrary branches, conditions, timers, subflows, delegation, or cross-domain automation.
- Manager/department approval cannot be delivered until a canonical organization contract exists.
- If a future Workflow engine replaces or orchestrates Request approval, data migration and compatibility need a separate project.

## Guardrails

- CI must reject domain-module dependencies from `Modules/Request`.
- Request code, migrations, configuration, route names, events, queues, storage paths, and permissions use the `request` boundary.
- Workflow code must remain absent/disabled while deferred.
- Do not copy broad Workflow features into Request without updating `REQUIREMENTS.md`, this ADR or a superseding ADR, traceability, and acceptance tests.
- No shared database writes or polymorphic `module/model/table` reference is permitted as a shortcut.
- No `/create-module Workflow` execution is authorized by the retained Workflow documents.

## Reconsideration criteria

Create a superseding ADR only when at least one of these is true:

- Request v1 is in production and multiple concrete request types require graph capabilities that cannot be expressed safely as ordered stages.
- A second domain has implemented approval needs and evidence supports extracting a stable shared contract.
- A canonical Shell-owned organization/manager contract is available.
- Product explicitly approves Workflow ownership, coexistence, or migration and funds the required data/security/operations work.

The superseding ADR must choose one ownership model: Request remains owner and Workflow orchestrates through public contracts, Workflow replaces Request approval through a versioned migration, or a separately owned Approval shell is extracted. It must define compatibility, cutover, rollback, and historical-read behavior.

## Alternatives rejected

### Implement Workflow v4 first

Rejected because it materially exceeds the approved first product and duplicates Request ownership.

### Implement Request as a thin UI over Workflow

Rejected for v1 because it forces the entire Workflow engine to exist before the bounded Request product is usable.

### Extract Approval as a shared Shell Module now

Rejected as premature abstraction without two proven consumers. Shell status also carries broad boot and dependency implications in this repository.

### Let Request depend on organization/domain modules

Rejected by the approved Shell-only dependency invariant and the absence of a canonical organization source.

## References

- `docs/modules/Request/REQUIREMENTS.md`
- `docs/modules/Request/00-REQUEST_MASTER_SPEC_V1.md`
- `docs/modules/Workflow/README.md`
- `docs/modules/Workflow/REQUIREMENTS.md`
- Repository module/bootstrap standards under `.codex/`
