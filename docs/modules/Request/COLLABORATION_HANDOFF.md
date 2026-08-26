# Request Module — Collaboration Handoff

- Last updated: 2026-08-26
- Repository: `tungocvan/laravel-shop`
- Base branch: `main`
- Finalized checkpoint: Request ClientPortal MR-3 through MR-5
- Documentation closeout PR: `#44 docs: close Request MR-5 handoff and add PR gates`
- Documentation closeout merge commit: `66b6f7ddb1fdfae55b6535479d9d81d77574b156`
- Main checkpoint verified: `66b6f7ddb1fdfae55b6535479d9d81d77574b156`
- MR-5 source checkpoint: `2d361411dc05ebd791ae3e4bccfc7c846b54bc9c`

## Checkpoint status

- MR-5 implementation and merge: **COMPLETED**
- Post-merge local acceptance on `main`: **PASS**
- Documentation handoff closeout through PR `#44`: **COMPLETED**
- Post-merge handoff verification on `main`: **PASS**
- Request production enablement: **NOT AUTHORIZED**
- Next MR/phase: **NOT DETERMINED**
- MR-6: **NOT DEFINED** by current source or documentation

## Current objective

The Request ClientPortal MR-3 through MR-5 checkpoint is closed for collaboration handoff. This file records the durable state verified on `main` after documentation closeout PR `#44`; it is not an active delivery plan. No Request implementation, production activation or next MR/phase is authorized by this handoff.

## Integrated checkpoint

| Delivery | Pull request | Result | Merge commit |
|---|---|---|---|
| MR-3 — ClientPortal requester flow | `#41` | Merged into `main` | `57144d685bad9b2e6e10bbfd1a390492cbabce4e` |
| MR-4 — Web Guard role and permission administration | `#42` | Merged into `main` | `fd3f2a3bc87875fb3b05b1cbbc5a2abd0d55edd5` |
| MR-5 — ClientPortal Request approver PWA | `#43` | Merged into `main` | `815aae1065cc58744be15abf5817446175fad944` |

PR `#43` head tree was merged without source-tree drift: the MR-5 head checkpoint is `2d361411dc05ebd791ae3e4bccfc7c846b54bc9c`, and `main` after merge is `815aae1065cc58744be15abf5817446175fad944`.

## Delivered through MR-5

### ClientPortal requester delivery

- Request is exposed as a ClientPortal application only while its source Module is enabled.
- Requester routes use guard `web`, ClientPortal application/feature permissions and `UseRequestAuthorizationGuard:web`.
- Catalog, draft creation, own-request list, detail, comments, attachments, submit, resubmit and cancellation reuse the existing Request domain services and policies.
- The Admin Request requester flow remains on the existing Admin routes and views.

### Web Guard administration

- Client application permissions and Request operational permissions are managed for guard `web`.
- Requester and approver permission profiles are available through `ApplicationPermissionService`.
- Admin role/user management preserves the guard boundary and does not replace or synchronize away guard `admin` assignments.
- No separate ClientPortal Request permission seeder was introduced.

### ClientPortal approver delivery

- Approver Inbox, processed history and detail routes are available through the Request ClientPortal adapter.
- Approve, return and reject decisions reuse `DecideRequestTask`; ClientPortal does not own a second approval workflow.
- The decision surface is channel-aware and uses the active `web` actor.
- Approver visibility remains limited by participant/assignee policy, actionable task state and the self-approval prohibition.
- ClientPortal status/action text is Vietnamese.
- The PWA detail layout is responsive and includes a mobile sticky decision surface.
- Request discussion/comments are shared between requester and approver through the existing Request collaboration domain.

## Architecture and safety decisions

- `ClientPortal` owns presentation, routes and application/feature access for the Client/PWA channel.
- `Request` owns workflow, queries, policies, application services, audit, comments and attachments.
- ClientPortal may consume Request services, but Request does not depend on ClientPortal.
- Guard `admin` and guard `web` permissions remain isolated.
- A ClientPortal feature permission only opens a UI surface; it does not replace the Request operational permission required by policy.
- Request decisions continue to enforce assignee, participant, task-state, optimistic-lock and self-approval invariants.
- Private attachment storage and authorization boundaries remain owned by Request.

## Verification evidence

### MR-5 evidence recorded before merge

PR `#43` recorded PASS for:

```bash
php artisan test tests/Feature/ClientApps/RequestClientApplicationTest.php
php artisan test tests/Feature/Request/Collaboration/RequestCollaborationTest.php
php artisan test tests/Feature/Request
```

The recorded manual UI smoke covered Inbox, Detail, decision actions, Vietnamese runtime text, mobile/responsive behavior and shared discussion/comments.

The repository has no `.github/workflows` directory at this checkpoint. Therefore this evidence is local/manual acceptance recorded on the PR; it must not be described as GitHub Actions CI.

### Post-merge acceptance on local `main`

The repository owner synchronized local `main` to `815aae10` and confirmed:

```text
Focused ClientPortal + collaboration:
20 tests passed (136 assertions)

Request regression:
140 tests passed (5900 assertions)

git status --short:
(empty)
```

Post-merge acceptance result: **PASS**.

### Documentation closeout verification

- PR `#44` merged on 2026-08-26.
- Merge commit: `66b6f7ddb1fdfae55b6535479d9d81d77574b156`.
- GitHub comparison confirmed `main` is identical to the merge commit.
- The checkpoint comparison from `815aae10` to `66b6f7dd` changed only `docs/GITHUB_COLLABORATION_WORKFLOW.md` and this handoff.
- Local `main` was fast-forwarded to `66b6f7dd`; `git status --short` remained empty.
- GitHub reported no status checks for the merge commit; this is not GitHub Actions CI evidence.

Documentation closeout verification result: **PASS**.

## Remaining work classification

| Category | Status | Remaining work |
|---|---|---|
| Post-merge acceptance | **PASS** | No application regression remains from MR-5 acceptance. |
| Documentation closeout | **COMPLETED** | PR `#44` is merged and `main` is verified. Only optional branch cleanup remains, subject to explicit authorization. |
| Production enablement | **NOT AUTHORIZED** | Perform a separately approved deployment/readiness process before enabling Request. |
| Next MR/phase | **NOT DETERMINED** | Current source and docs do not define MR-6. Do not infer one from deferred work or documentation drift. |

## Production enablement boundary

`Modules/Request/config/module.php` currently declares:

```php
'enabled' => false,
'default_enabled' => false,
```

Merging MR-3, MR-4, MR-5 or this documentation closeout does not enable Request in any environment.

A separately approved production enablement must verify at least:

- runtime Module state through the canonical Module state repository/admin mechanism
- dependency readiness and migrations
- Role permission synchronization and guard `web` requester/approver assignments
- private attachment storage, persistence and ownership
- Request queues/workers, scheduler and operational readiness
- production smoke, rollback path and post-operation Git cleanliness

Permission changes use the existing Role lifecycle:

```bash
php artisan db:seed --class='Modules\Role\database\seeders\RolesAndPermissionsSeeder'
```

Do not create a separate ClientPortal Request permission seeder.

## Known documentation/operations follow-up

The following items are not automatically MR-6 and must be scoped separately before modification:

- reconcile historical Request acceptance/release documents that still describe an earlier checkpoint
- update the ClientPortal README application inventory/conventions to reflect the current Request adapter where useful
- reconcile Request runbook permission-sync commands with the command/seeder that exists in source
- delete merged MR-3/MR-4/MR-5 and documentation-closeout branches only when cleanup is explicitly authorized

## Next authorized step

1. No new Request implementation MR is authorized by default.
2. Clean up merged feature/documentation branches only after explicit user authorization.
3. Treat production enablement as a separate approval with the readiness gates above.
4. Scope historical documentation/runbook maintenance separately before modifying it.
5. Do not name or begin another Request MR/phase without a source/documented requirement and explicit authorization.
