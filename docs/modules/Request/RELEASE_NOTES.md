# Request v1 — Release Notes

Date: 2026-08-25  
Release status: **IMPLEMENTATION VERIFIED**

## Delivered scope

Request v1 delivers an internal request and approval product with:

- versioned request groups/types/forms and publish/retire lifecycle;
- requester catalog, drafts, submit, tracking, comments, attachments, cancellation and resubmission flows;
- sequential and parallel approval stages with safe candidate resolution, self-approval denial, reject/return/reassign and activation recovery;
- append-only audit, transactional outbox and idempotent database/email notification delivery;
- private bounded CSV/PDF/export orchestration and definition-package support;
- operations allowlist/retry behavior;
- responsive employee/approver/admin surfaces and Shell-owned PWA/offline-safe draft/read behavior;
- bounded task-level SLA warning/due/grace handling as an approved implementation deviation.

## Architecture

Request remains a domain module that consumes only approved Shell dependencies. User and Role access is through stable Shell contracts/DTOs; Request does not import canonical application User models or Spatie identity implementation directly.

Identity-aware developer/E2E seeders live under root `database/seeders`, outside the Request domain boundary. Production `DatabaseSeeder` is not populated with Request demo data.

## Security and privacy

Request attachments and exports remain private and authorization-scoped. Export fields are server allowlisted, spreadsheet formula vectors are neutralized, PDF remote resources are disabled, and export artifacts expire. Operations retries are allowlisted and idempotent.

PWA behavior reuses the global Shell manifest/service worker. No offline replay is provided for submit, approval decisions, comments, uploads, exports, retries, or other business mutations.

## Notifications

Database and email notifications were verified end-to-end for approval, rejection, return, and SLA warning scenarios. Per-stage email toggles were also verified: with assignment/decision/SLA email disabled, the database delivery remains while the email delivery is omitted.

## SLA deviation

The original Request v1 planning document deferred SLA/timers. During implementation, a constrained Request-owned task SLA feature was explicitly accepted and verified. It includes stage SLA configuration, task snapshots/timestamps, warning/overdue/suspended events and enforcement, notification delivery, and timezone-correct UI display.

This does not add generic Workflow/BPMN runtime, graph conditions, delegation, subflows, manager/department resolution, cross-domain posting, or offline business mutation.

## Verification evidence

Final agreed gates passed:

- Request suite: 84 tests / 4904 assertions;
- User/Role cross-module regression;
- Pint and `git diff --check`;
- production frontend build;
- migration/deployment/operations checks;
- responsive/browser/PWA acceptance;
- Request Export/Operations automated suites;
- real export download from the Reports UI;
- final regression/release-hardening command set.

No Request-owned failed queue job was present at the runtime export checkpoint. Existing historical failed jobs observed then belonged to the Invoices module and are not part of this release.

## Deployment

Request remains default OFF in tracked configuration and should be enabled through runtime module state. Production deployment must follow `IMPLEMENTATION_RUNBOOK.md`: backup/restore readiness, enablement, additive migrations, permission/menu synchronization, worker/scheduler readiness, private storage checks, authenticated smoke, and rollback preparation.

## Deferred / follow-up

Still deferred unless separately approved:

- manager/department resolver;
- generic Workflow/BPMN/graph runtime;
- delegation/subflow/quorum features beyond the implemented Request stage modes;
- digital/legal signatures;
- multi-company behavior;
- business-domain posting/integration;
- offline mutation queue;
- realtime/web push.

UX follow-up: `/admin/requests` currently behaves as a Request hub/acceptance dashboard while Reports and Operations are exposed through admin subroutes. Improving discoverability/navigation is a post-release UX architecture task and does not change the verified backend capability.