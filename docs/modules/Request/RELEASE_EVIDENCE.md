# Request v1 — Release Evidence

Date: 2026-08-25  
Purpose: execution evidence companion for `CREATE_PLAN.md` and `12-TRACEABILITY_MATRIX.md`.

## Gate evidence

### Gate #1 — Request feature suite

Command:

```bash
php artisan test tests/Feature/Request --stop-on-failure
```

Final result:

```text
Tests: 84 passed (4904 assertions)
```

Architecture defects found during this gate were corrected before the final pass, including removal of direct `App\Models\User` usage from Request-owned runtime code and relocation of identity-aware demo seeders outside `Modules/Request`.

### Gate #2 — cross-module/style/build

Verified green:

```bash
php artisan test tests/Feature/User tests/Feature/Role --stop-on-failure
vendor/bin/pint --test Modules/Request tests/Feature/Request database/seeders/RequestDemoSeeder.php database/seeders/RequestE2EDemoSeeder.php
git diff --check
npm run build
```

A stale User mail test was corrected to assert the current `UserMessageMail::mailMessage` property. The final User/Role regression passed.

### Gate #3 — migration/deployment/operations

Migration status, Request migration tests, operational deployment contract tests, SLA enforcement sanity, queue inspection, and application runtime checks passed in the agreed environment.

### Gate #4 — browser/responsive/PWA

Manual browser acceptance passed for the requester/employee flow, approver flow, Admin Designer, responsive UI, and PWA/offline safety. The PWA continues to reuse Shell ownership and does not replay business mutations offline.

### Gate #5 — reports/export/operations

Automated evidence:

```text
Request Export/Operations targeted suite:
12 passed (53 assertions)

report|export|operation filtered gate:
17 passed (89 assertions)
```

Covered behaviors include scoped authorization, private export storage, field allowlists, no silent truncation, formula neutralization, CSV idempotency/expiry, private PDF generation, bounded operations, retry allowlisting/idempotency, and starter-template opt-in behavior.

Runtime evidence:

- `request_failed_jobs` query returned an empty set.
- a real export was initiated from the Request Reports UI and downloaded successfully.
- historical failed queue rows observed separately belonged to `Modules\Invoices\Jobs\ProcessGdtInvoicesJob`, outside Request scope.

### Gate #6 — release hardening

The final Request regression, User/Role regression, formatting, diff check, frontend build, migration status, queue inspection and application sanity checks were reported green.

## Notification E2E evidence

Verified scenarios included:

- approved request email delivery;
- rejected request email delivery;
- returned request email delivery;
- SLA warning email delivery;
- database-only assignment when `email_on_assignment = 0`;
- database-only decision when `email_on_decision = 0`;
- database-only SLA warning when `email_on_sla_warning = 0`.

Delivery records for successful email scenarios reached `status=delivered`, `attempt_count=1`, `last_error_code=null` after the mail rendering issue was corrected.

## SLA evidence

The local SLA helper was used only to place real active tasks into deterministic warning/overdue/suspend time windows. Enforcement produced Request-owned outbox events and delivery rows. UTC database timestamps were compared with application-timezone UI timestamps during verification.

Implemented command paths:

```bash
php artisan request:sla-demo REQ-... warning
php artisan request:sla-enforce
```

`request:sla-demo` is explicitly guarded from production use.

## Release caveats / evidence boundaries

The evidence above records the verification actually performed in this implementation session. It must not be reinterpreted as a synthetic large-scale production performance benchmark unless such benchmark output is separately captured. Production deployment still requires the operator checks in `IMPLEMENTATION_RUNBOOK.md`.

The SLA capability is an approved deviation from the original v1 exclusion and is documented in `IMPLEMENTATION_COMPLETION_ADDENDUM.md` and `RELEASE_NOTES.md`. Generic Workflow remains deferred.