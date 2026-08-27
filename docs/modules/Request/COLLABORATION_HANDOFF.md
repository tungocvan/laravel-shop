# Request Module — Collaboration Handoff

- Last updated: 2026-08-27
- Repository: `tungocvan/laravel-shop`
- Base branch: `main`
- Finalized checkpoint: Request ClientPortal MR-3 through MR-5
- Documentation closeout PR: `#44 docs: close Request MR-5 handoff and add PR gates`
- Documentation closeout merge commit: `66b6f7ddb1fdfae55b6535479d9d81d77574b156`
- Main checkpoint before production-readiness preparation: `d89333d20fcf3666cf84acb86743810e3b8dd9f8`
- Active preparation branch: `chore/request-production-readiness`

## Checkpoint status

- MR-5 implementation and merge: **COMPLETED**
- Post-merge local acceptance on `main`: **PASS**
- Documentation handoff closeout through PR `#44`: **COMPLETED**
- Request production-readiness preparation: **IMPLEMENTED ON FEATURE BRANCH / PR PENDING**
- Request production enablement: **NOT AUTHORIZED**
- Production mutation/demo seeding: **NOT EXECUTED**
- Next application MR/phase: **NOT DETERMINED**

## Current objective

The currently authorized work is limited to preparing Request for a later, separately approved Docker production enablement. The preparation branch does not enable Request and does not mutate production.

The approved preparation scope is:

- keep the existing root `Dockerfile` and `compose.yaml` architecture;
- add Request production variables to `.env.docker.example`;
- prevent the real `.env` from being copied into image layers while continuing to mount the host `.env` at runtime;
- normalize private Request/runtime-state storage ownership and permissions under Docker;
- allow Request-only demo seeding on `APP_ENV=production` through an explicit `REQUEST_ENV=true` gate;
- document runtime cache refresh, permission, worker/scheduler, smoke and rollback expectations.

## Integrated checkpoint through MR-5

| Delivery | Pull request | Result | Merge commit |
|---|---|---|---|
| MR-3 — ClientPortal requester flow | `#41` | Merged into `main` | `57144d685bad9b2e6e10bbfd1a390492cbabce4e` |
| MR-4 — Web Guard role and permission administration | `#42` | Merged into `main` | `fd3f2a3bc87875fb3b05b1cbbc5a2abd0d55edd5` |
| MR-5 — ClientPortal Request approver PWA | `#43` | Merged into `main` | `815aae1065cc58744be15abf5817446175fad944` |

## Production-readiness preparation implemented

### Docker/env contract

- Request continues to use the root `Dockerfile` and `compose.yaml`; no Request-specific image/compose stack is introduced.
- `.env.docker.example` now documents `REQUEST_ENV`, starter-template actor/approver IDs, private file storage settings and Request worker tuning.
- `.env` is excluded by `.dockerignore`; production values are still prepared from `.env.docker.example` into the host `.env` and mounted by Compose at runtime.

### Private storage/runtime Module state

The Docker image/entrypoint prepares and normalizes:

```text
storage/app/system
storage/app/request
storage/app/request/attachments
```

These private directories use `www-data:www-data` and mode `2770`; existing runtime-state files directly under `storage/app/system` are normalized to `0660` at container startup when the entrypoint runs as root. The preparation intentionally does not chmod all of `storage/app` to `2770`, preserving the shared `storage/app/public` contract.

### Request demo seeding gate

Production does not need to change `APP_ENV` to seed Request demo data.

```text
REQUEST_ENV=false -> DatabaseSeeder does not call RequestDemoSeeder
REQUEST_ENV=true  -> DatabaseSeeder calls RequestDemoSeeder
```

`RequestDemoSeeder` is scoped to Request and currently aggregates `RequestStarterTemplateSeeder`. It does not unlock demo seeders belonging to other Modules. Production demo seeding remains an explicit operation; it is not added to Docker entrypoint or `deploy.sh`.

When starter/demo templates are required, valid and distinct values are still required for:

```text
REQUEST_STARTER_TEMPLATE_ACTOR_ID
REQUEST_STARTER_TEMPLATE_APPROVER_ID
```

### Runtime cache boundary

Production uses Laravel optimize/config/route caches. After changing runtime Module state, the enablement procedure must rebuild application caches so the new Module set is reflected consistently. The runbook records an explicit `optimize:clear` then `optimize` refresh after runtime enable/disable and before readiness/demo-seed verification.

## Verification evidence for preparation branch

Local owner acceptance on `chore/request-production-readiness`:

```text
Request regression:
142 tests passed (5922 assertions)

git status --short:
(empty)
```

The regression includes the new Request demo-seeder gate tests and existing Request operations coverage.

GitHub comparison before this handoff update showed the branch ahead of `main` with no behind commits. The preparation changes were limited to Docker/env/storage readiness, Request demo-seeder/config integration, tests and Request operations documentation.

This is local/manual acceptance evidence; it must not be represented as GitHub Actions CI.

## Architecture and safety decisions retained

- `ClientPortal` owns presentation, routes and application/feature access for the Client/PWA channel.
- `Request` owns workflow, queries, policies, application services, audit, comments and attachments.
- Guard `admin` and guard `web` permissions remain isolated.
- Private attachment storage and authorization boundaries remain owned by Request.
- Request remains `default_enabled=false`; preparation does not change `Modules/Request/config/module.php` to enable it.
- Runtime Module state remains the canonical enable/disable mechanism.
- Do not use `chmod 777` for Request/runtime-state storage.
- Do not automatically run production demo seeders during container startup/deploy.

## Production enablement boundary

Production enablement remains **NOT AUTHORIZED** by this handoff or by the preparation PR.

A separately approved production operation must verify and order at least:

1. database backup and migration/schema readiness;
2. runtime Module enable through the canonical Module state mechanism;
3. Laravel cache refresh after runtime state change;
4. Role permission synchronization and guard `web` requester/approver assignments;
5. private attachment/runtime-state storage ownership and persistence;
6. `queue-request`, Redis and scheduler health;
7. optional Request demo seeding only when `REQUEST_ENV=true` and valid actor/approver IDs are configured;
8. Admin, requester, approver and async smoke tests;
9. runtime-disable/code/data rollback path;
10. post-operation Git cleanliness and evidence capture.

No production enable, migration, seed or runtime-state mutation has been executed as part of the preparation branch.

## Known remaining readiness issue

The production migration ordering still requires an explicit preflight/enablement procedure. Request migrations are registered through the enabled Module lifecycle, while Request is disabled by default. The preparation PR does not silently solve this by enabling Request or by changing production state. Migration/schema readiness must be verified before the separately approved enablement operation.

The Request release runbook also contains historical readiness/migration command references that must be verified against executable source before they are relied upon on production. Do not assume a documented Artisan command exists without command inventory/source confirmation.

## Next authorized step

1. Open a PR from `chore/request-production-readiness` into `main` for review.
2. Do not merge the PR until separately approved.
3. Do not enable Request or run production demo seeders as part of PR review/merge.
4. After merge, perform production preflight only under a separate explicit authorization.
5. Do not name or begin another Request application MR/phase without a source/documented requirement and explicit authorization.
