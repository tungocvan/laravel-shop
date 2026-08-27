# Request Module — Collaboration Handoff

- Last updated: 2026-08-27
- Repository: `tungocvan/laravel-shop`
- Base branch: `main`
- Working branch: `fix/request-production-demo-seeder`
- Pull request: **PENDING — not created**
- Merge commit: **NOT AVAILABLE**
- Main checkpoint inspected: `fd4198ceb25cbad769d096c2befa20e972eaa3db`
- Implementation checkpoint before this handoff refresh: `cd5c27be`

## Checkpoint status

- Request ClientPortal MR-3 through MR-5: **COMPLETED**
- Production Docker/readiness work already present on `main`: **COMPLETED for source integration**
- Current production demo-seeder correction: **IN PROGRESS — implementation and focused local gates PASS; PR pending**
- Request production runtime state: **outside this PR**
- Production demo-data execution: **separate explicit operation; not performed by this branch**
- Next Request application MR/phase: **NOT DETERMINED**

## Current objective

Correct the Request E2E demo-seeder production guard and document the repeatable production test flow.

The current branch is intentionally narrow:

1. allow `Database\\Seeders\\RequestE2EDemoSeeder` to run on `APP_ENV=production` only when `REQUEST_ENV=true`;
2. keep production blocked by default when `REQUEST_ENV` is absent or false;
3. add `docs/modules/Request/DEMO_SEEDER_RUNBOOK.md` so production demo testing does not depend on ad-hoc chat commands;
4. do not change the Request workflow/domain, routes, permissions, Module runtime-state mechanism, Docker architecture or unrelated seeders.

## Branch comparison

GitHub comparison against `main` at handoff refresh:

```text
base: fd4198ceb25cbad769d096c2befa20e972eaa3db
head implementation checkpoint: cd5c27be
status: ahead
behind_by: 0
```

Before this handoff-only commit, the application/documentation scope consisted of exactly:

```text
database/seeders/RequestE2EDemoSeeder.php
docs/modules/Request/DEMO_SEEDER_RUNBOOK.md
```

This handoff file is the third expected PR file and exists solely to satisfy the collaboration gate before PR creation.

## Production demo-seeder safety boundary

The effective contract for `RequestE2EDemoSeeder` is:

```text
APP_ENV=production + REQUEST_ENV missing/false
    -> E2E demo seeder is rejected

APP_ENV=production + REQUEST_ENV=true
    -> E2E demo seeder may run
```

The production opt-in is deliberately the existing `REQUEST_ENV` flag. No second demo-enablement flag is introduced for the E2E flow.

This does **not** mean:

- demo data runs automatically during deploy/container startup;
- changing `APP_ENV` is required;
- other Module demo seeders are unlocked;
- Request runtime Module enable/disable is bypassed;
- permission assignment is bypassed;
- production demo seeding is authorized merely because this PR is merged.

## Standard production demo-test flow

The detailed commands and verification procedure live in:

```text
docs/modules/Request/DEMO_SEEDER_RUNBOOK.md
```

The operational sequence is:

1. identify the correct Compose project/containers before running commands;
2. verify the Request Module and effective Request configuration inside that exact application container;
3. prepare prerequisite test users/roles/permissions when the target database does not already contain suitable actors;
4. set `REQUEST_ENV=true` intentionally for the demo-test window;
5. refresh Laravel configuration/cache when required by the production deployment mode;
6. run `Database\\Seeders\\RequestE2EDemoSeeder` explicitly with `--force`;
7. verify generated Request data, routes and the intended Admin/ClientPortal UI flows;
8. verify requester/approver permission behavior rather than treating seeded rows alone as acceptance;
9. return `REQUEST_ENV=false` after the test window unless there is an explicit reason to retain the opt-in;
10. capture production evidence without committing the real `.env` or production data.

## Docker / production context retained

Production Docker work must continue to follow the repository production guardrails and the root Docker/Compose architecture.

Relevant retained rules include:

- variables used by production must be represented in `.env.docker.example` with safe/documented defaults where appropriate;
- the real production `.env` is runtime configuration and must not be committed or baked into image layers;
- changing host `.env` is not assumed to change an already cached Laravel configuration automatically;
- commands must target the correct Compose project (for the current production investigation this was the `tnv` stack, not another stack sharing the same compose file);
- Module runtime state, migrations/schema readiness, permissions, queues/scheduler and private storage remain separate production concerns and must be verified independently;
- Docker image/container freshness must be verified when source/runtime behavior differs unexpectedly; a stale image/container must not be diagnosed as an application-code defect without checking the deployed source.

## Module enable/disable and permission boundary

This branch does not alter the canonical Request Module enable/disable mechanism.

Production acceptance must continue to distinguish:

```text
source manifest/default state
runtime Module state
effective cached Laravel configuration
registered routes
schema/migration readiness
Role/Spatie permission tables and permission synchronization
admin/web guard assignments
```

A route existing in `route:list` does not by itself prove that a user has the required permission, and a runtime state file being true does not by itself prove that every long-running worker or cached process has observed the new effective state.

The Request E2E seeder change must not be used to work around missing Role tables, permission synchronization, requester/approver assignments or Module lifecycle problems.

## Verification evidence

Local owner verification reported on `fix/request-production-demo-seeder`:

```text
## fix/request-production-demo-seeder...origin/fix/request-production-demo-seeder
No syntax errors detected in database/seeders/RequestE2EDemoSeeder.php
```

The user also ran:

```bash
php -l database/seeders/RequestE2EDemoSeeder.php
git diff --check main...HEAD
```

`php -l` passed and `git diff --check` produced no error output.

GitHub comparison confirmed the branch was `ahead` of `main` and `behind_by: 0` before this handoff refresh.

These are focused/local and repository comparison gates. They are **not** GitHub Actions CI and they are **not** evidence that production demo seeding has already been executed successfully with this branch.

## PR gate status

- [x] Correct working branch confirmed locally.
- [x] Branch tracks `origin/fix/request-production-demo-seeder`.
- [x] PHP syntax check for changed seeder PASS.
- [x] `git diff --check main...HEAD` PASS.
- [x] GitHub comparison shows branch not behind `main` before handoff refresh.
- [x] Production safety boundary documented.
- [x] Demo production runbook added.
- [x] `COLLABORATION_HANDOFF.md` refreshed on the PR branch.
- [ ] Pull request created and actual PR number recorded.
- [ ] PR diff/review completed.
- [ ] Handoff refreshed with actual PR metadata before merge.
- [ ] Merge explicitly authorized by repository owner.

## Deferred / not claimed by this branch

This branch does not claim to solve or execute:

- general production deployment;
- automatic Module enablement;
- production database migration outside the documented lifecycle;
- user/role provisioning for every production site;
- permission profile assignment for every production actor;
- cleanup/removal of previously seeded demo data;
- unrelated Request feature work;
- unrelated Module seeders.

If production demo execution exposes a new defect, capture the exact failing command, effective configuration and target Compose project before expanding this PR scope.

## Next authorized step

1. Create a PR from `fix/request-production-demo-seeder` into `main`.
2. Review the PR diff and confirm it contains only the E2E guard, demo runbook and this handoff refresh.
3. Record the actual PR number/state in this handoff before merge.
4. Do **not** merge until the user explicitly approves merge.
5. Do **not** run production demo seeding merely as a consequence of PR creation/merge; production execution remains a separate explicit test operation.
6. Do not name or begin another Request application MR/phase without a documented requirement and explicit authorization.
