# Request v1 — Implementation Completion Addendum

Status: **IMPLEMENTATION VERIFIED**  
Date: 2026-08-25  
Applies to: `docs/modules/Request/CREATE_PLAN.md`  
Reference implementation branch: `fix/request-e2e-submit-demo`

## 1. Purpose

This addendum records the completion state of the approved Request v1 implementation plan without rewriting the original planning history. The original `CREATE_PLAN.md` remains the authoritative implementation plan; this document records execution evidence, the approved SLA deviation, and the final release-gate outcome.

## 2. Completion summary

The Request v1 implementation completed MR-01 through MR-10 and passed the agreed runtime verification sequence:

| Gate | Scope | Result |
|---|---|---|
| Gate #1 | Full Request feature suite | PASS — 84 tests, 4904 assertions |
| Gate #2 | User/Role regression, Pint, `git diff --check`, frontend build | PASS |
| Gate #3 | Migration, deployment contract, operational sanity | PASS |
| Gate #4 | Browser/responsive/PWA acceptance | PASS |
| Gate #5 | Reporting/export/operations automated checks and real UI export | PASS |
| Gate #6 | Final regression and release-hardening command set | PASS |

The Request feature suite was re-run after architecture and seeder-boundary corrections. Cross-module User/Role tests, formatting, build, migration and operations checks were also green at the final checkpoint.

## 3. Architecture reconciliation

The Shell-only dependency invariant remains authoritative. During release hardening, identity-aware demo seeders were moved out of `Modules/Request` into root `database/seeders`, and Request UI code that referenced `App\Models\User` directly was changed to consume `Modules\User\Contracts\UserDirectory`.

Production `DatabaseSeeder` remains unaffected by Request demo data. Request starter-template behavior remains opt-in and draft-only.

## 4. Approved SLA deviation

The original plan explicitly deferred timers/SLA. During implementation, a bounded Request-owned task SLA capability was added and accepted through iterative E2E verification.

Implemented SLA scope:

- stage-level `sla_minutes`, `warning_minutes_before`, `grace_minutes`, and `timeout_action` configuration;
- immutable task SLA snapshot metadata and UTC timestamps (`warning_at`, `due_at`, `grace_expires_at`, `overdue_at`, `suspended_at`);
- warning, overdue, and suspended audit/outbox events;
- `request:sla-enforce` enforcement command plus non-production `request:sla-demo` test helper;
- database/email SLA warning delivery;
- per-stage `email_on_sla_warning` toggle;
- UI display in the application timezone while persistence remains UTC.

This deviation does **not** introduce BPMN/graph workflow, conditional routing, delegation, subflows, manager/department resolution, domain posting, offline mutation replay, or a Workflow module dependency. Those remain deferred.

## 5. Notification E2E evidence

The notification pipeline was exercised for:

- approval result email;
- rejection email;
- return email;
- SLA warning email;
- database-only behavior when assignment/decision/SLA email toggles are OFF.

Observed delivery rows reached `delivered` with cleared error codes for the verified scenarios. The OFF-path checks produced database delivery without email delivery, as designed.

## 6. Export and operations evidence

Automated Request Export/Operations verification passed:

- private scoped export query;
- queue threshold without silent truncation;
- server-side field allowlist;
- private non-public storage;
- spreadsheet formula neutralization;
- CSV idempotency and expiry;
- private PDF with remote content disabled;
- bounded operations query;
- retry allowlist;
- idempotent failed-export retry;
- starter template opt-in/draft-only behavior.

A real export was also downloaded successfully through the Request reports UI. No Request-owned failed queue jobs were present at the runtime checkpoint. Historical failed jobs observed at that time belonged to the Invoices module and are outside Request release scope.

## 7. UI/PWA evidence

Browser verification covered the requester/employee flow, approver flow, Admin Designer, responsive behavior, and PWA/offline safety. The Request PWA implementation continues to reuse the Shell-owned manifest/service worker and does not introduce offline business-command replay.

A navigation UX follow-up remains: `/admin/requests` currently acts as a Request hub/acceptance dashboard while Reports and Operations are available through their admin routes. This is a discoverability/productization improvement, not a blocker for the verified Request functionality.

## 8. Release status

For the agreed implementation and verification gates, Request v1 is considered **implementation complete**.

Before production enablement, operators must still follow `IMPLEMENTATION_RUNBOOK.md`, including backup/restore readiness, runtime module enablement, migrations, permission sync, workers, private storage validation, smoke checks, and rollback preparation.

The original deferred capabilities remain deferred unless a separate approved ADR/change plan re-enters them.