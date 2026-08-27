# Request Module — Collaboration Handoff

- Last updated: 2026-08-27
- Repository: `tungocvan/laravel-shop`
- Base branch: `main`
- Closeout branch: `docs/request-pr53-handoff-closeout`
- Completed source PR: `#53 fix(request): allow production E2E demo seeding with REQUEST_ENV`
- Completed source PR state: **MERGED**
- Source PR merge commit: `9d4bd2869f5604ffa1a0760528cd297af370a3bb`
- Source PR head checkpoint: `db9f62cfd94427f1523f2fc47bf2011d6b356702`
- Main checkpoint after PR #53 merge: `9d4bd2869f5604ffa1a0760528cd297af370a3bb`

## Checkpoint status

- Request ClientPortal MR-3 through MR-5: **COMPLETED**
- Production Docker/readiness source integration: **COMPLETED**
- PR #53 production E2E demo-seeder correction: **COMPLETED / MERGED**
- Request production runtime state: **outside this source checkpoint**
- Production demo-data execution: **NOT EXECUTED by PR #53; separate explicit production operation**
- Next Request application MR/phase: **NOT DETERMINED**

## What PR #53 delivered

PR #53 intentionally changed only the production demo-test path for Request and its documentation:

1. `Database\\Seeders\\RequestE2EDemoSeeder` may run on `APP_ENV=production` only when `REQUEST_ENV=true`;
2. production remains blocked by default when `REQUEST_ENV` is absent or false;
3. `docs/modules/Request/DEMO_SEEDER_RUNBOOK.md` documents the repeatable local/production demo-test procedure;
4. the change does not modify Request workflow/domain rules, routes, permission model, Module runtime-state mechanism, Docker architecture or demo seeders from other Modules.

The merged production guard is:

```text
APP_ENV=production + REQUEST_ENV missing/false
    -> RequestE2EDemoSeeder rejected

APP_ENV=production + REQUEST_ENV=true
    -> RequestE2EDemoSeeder allowed to run explicitly
```

`REQUEST_ENV` remains the single explicit opt-in for Request demo seeding. No second E2E enablement flag was added.

## Standard production demo-test flow

The canonical command/runbook is:

```text
docs/modules/Request/DEMO_SEEDER_RUNBOOK.md
```

For Docker production, the workflow must follow the applicable production guardrails and at minimum:

1. identify the exact Compose project and target containers before any command;
2. update production source to the intended `main` checkpoint;
3. determine whether the changed file is image-baked or bind-mounted and rebuild/recreate the affected services when required;
4. verify inside the exact application container that Request is enabled and `request.settings.demo_seeders_enabled=true`;
5. keep `APP_ENV=production` and set `REQUEST_ENV=true` only for the intentional demo-test window;
6. run `Database\\Seeders\\RequestE2EDemoSeeder` explicitly with `--force`;
7. verify created users, roles/permissions, Request definitions, starter templates and E2E lifecycle data;
8. verify Request routes plus representative Admin/ClientPortal UI and authorization behavior;
9. return `REQUEST_ENV=false` after the test window unless there is an explicit reason to retain the opt-in;
10. do not assume disabling `REQUEST_ENV` deletes existing demo data.

For the production site discussed during this checkpoint, the correct Compose project was identified as `tnv`; generic `docker compose ...` commands previously targeted a second `laravel-app` stack and produced misleading diagnostics. Future production work must re-verify the project name rather than relying on historical assumptions.

## Production safety boundary

Merge of PR #53 does **not** itself authorize or perform:

- production demo seeding;
- Request Module enable/disable mutation;
- database migration outside the canonical Module lifecycle;
- permission bypass;
- automatic seeding during Docker build, entrypoint or deploy;
- cleanup/deletion of demo data;
- changes to other Module seeders.

Request production acceptance must continue to verify applicable layers independently:

```text
container/image/source freshness
effective ENV/config
runtime Module state
database/migration readiness
Role/Spatie permission infrastructure
admin/web guard assignments
registered routes
HTTP/UI behavior
queue-request / scheduler / Redis
private storage ownership and persistence
```

A runtime Module state of `true` is not sufficient acceptance by itself, and a route appearing in `route:list` does not prove the intended actor has authorization.

## Verification evidence for PR #53

Owner local verification on `fix/request-production-demo-seeder` recorded:

```text
## fix/request-production-demo-seeder...origin/fix/request-production-demo-seeder
No syntax errors detected in database/seeders/RequestE2EDemoSeeder.php
```

Commands recorded as PASS:

```bash
php -l database/seeders/RequestE2EDemoSeeder.php
git diff --check main...HEAD
```

GitHub verification after merge:

```text
PR: #53
state: closed
merged: true
head: db9f62cfd94427f1523f2fc47bf2011d6b356702
merge commit: 9d4bd2869f5604ffa1a0760528cd297af370a3bb
changed files: 3
```

The merged files were:

```text
database/seeders/RequestE2EDemoSeeder.php
docs/modules/Request/DEMO_SEEDER_RUNBOOK.md
docs/modules/Request/COLLABORATION_HANDOFF.md
```

This evidence is repository/local verification, not GitHub Actions CI and not evidence that the production E2E seeder has already been executed successfully.

## Closeout scope

This docs-only closeout exists because the handoff committed before PR #53 creation still contained `Pull request: PENDING — not created`. PR #53 is already merged, so this closeout updates the historical checkpoint only.

This closeout does not modify application source and does not create a new Request feature phase.

## Next authorized step

After this handoff closeout is merged, the next work may be the separately authorized production demo-test operation using `docs/modules/Request/DEMO_SEEDER_RUNBOOK.md`.

Do not automatically name or begin a new Request application MR/phase. If production execution reveals a defect, capture the exact failing command, correct Compose project, effective configuration and deployed source checkpoint before proposing a new corrective branch.
