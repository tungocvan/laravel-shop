# Request v1 — Implementation & Release Runbook

Status: **READY FOR OPERATOR USE**  
Date: 2026-08-25

## 1. Release principles

Request remains a repository-native domain module with Shell-only direct dependencies. The module should stay default OFF in Git and be enabled through the existing runtime module-state mechanism during deployment.

Do not edit historical migrations on a populated environment. Do not expose Request attachments or exports through the public disk. Do not run Request demo seeders as part of production `DatabaseSeeder`.

## 2. Pre-deploy checklist

- Confirm working tree and deployed commit are known.
- Back up database and Request private storage; ensure the restore procedure is known.
- Confirm PHP/extensions, database connectivity, queue connection, scheduler, mail, and private storage are healthy.
- Confirm Request queues are configured as `request-outbox`, `request-notifications`, and `request-exports`.
- Confirm Request private disk is configured and is not `public`.
- Confirm `storage/app/request/attachments` exists and is writable by the deployed PHP-FPM user (normally `www-data`). A CLI command run as `root` must not leave `storage` owned only by `root`; never repair this with `chmod 777`.
- Put the application into the normal maintenance/drain workflow before module enablement/migrations if required by the platform bootstrap.

## 3. Runtime enablement

Enable Request through the existing module-state command/UI/repository. Do not change the tracked module manifest merely to enable the deployed environment.

After enablement:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan migrate:status
php artisan db:seed --class="Database\\Seeders\\RolesAndPermissionsSeeder" --force
php artisan request:release-readiness
```

The readiness command should report PASS for module state, migrations, permissions, Super Admin Request permissions, private storage, and queue contract.

If the repository uses an explicit Request menu seeder in the target deployment, run it idempotently after permissions are synchronized.

## 4. Workers and scheduler

Production workers must consume the Request queues according to the deployment architecture. Verify the dedicated production worker/scheduler configuration rather than relying on a developer PM2 process.

Representative one-shot diagnostics:

```bash
php artisan queue:work --queue=request-outbox --once --tries=3
php artisan queue:work --queue=request-notifications --once --tries=3
php artisan queue:work --queue=request-exports --once --tries=3
php artisan queue:failed
```

Request SLA enforcement must be invoked through the deployed scheduler/approved operational path:

```bash
php artisan request:sla-enforce
```

`request:sla-demo` is non-production test tooling and must not be used as a production operation.

## 5. Post-deploy smoke

Run authenticated smoke checks with appropriate roles:

1. employee opens eligible catalog;
2. creates and saves a draft;
3. submits the request;
4. approver sees the active task;
5. approve/reject/return action persists correctly;
6. requester sees the resulting status/history;
7. private attachment upload/download works with authorization;
8. database/email notification reaches the intended recipient when enabled;
9. email toggle OFF produces database-only delivery;
10. report page loads and an export downloads successfully;
11. Request audit/outbox/delivery records show no unexpected error state;
12. responsive/PWA states remain usable on the agreed browser/device set.

## 6. SLA operational check

For a real active task, verify persisted SLA timestamps are UTC and application UI renders them in the configured display timezone. `request:sla-enforce` must be idempotent across repeated scheduler runs: already-emitted warning/overdue/suspended milestones must not duplicate business transitions.

Check outbox/notification delivery after SLA warning enforcement. Email delivery is controlled by the published stage snapshot/toggle and must not bypass the database notification path.

## 7. Export operational check

Verify Request export artifacts are private, opaque, expiring, and reauthorized on download. For queued exports, ensure the `request-exports` worker is active. A failed export may only be retried through the allowlisted Request operation path; arbitrary job/command execution is not allowed.

## 8. Rollback / incident response

If Request deployment is unhealthy:

1. disable Request through runtime module state;
2. stop/drain Request-specific queue consumption;
3. preserve Request database and private-storage evidence;
4. record active request/task/export/outbox state;
5. roll application code back only to a version compatible with the additive schema;
6. do not destructively roll back populated evidence tables;
7. restore database and private storage from the same checkpoint only when full restore is required;
8. verify checksums/history after recovery.

## 9. Verification command set used during implementation

The final implementation verification included:

```bash
php artisan test tests/Feature/Request
php artisan test tests/Feature/User tests/Feature/Role
vendor/bin/pint --test Modules/Request tests/Feature/Request database/seeders/RequestDemoSeeder.php database/seeders/RequestE2EDemoSeeder.php
git diff --check
npm run build
php artisan migrate:status
php artisan queue:failed
php artisan about
```

Additional targeted tests covered Request architecture, migrations, operational deployment contracts, exports, operations, notification E2E behavior, and SLA warning delivery.

## 10. Non-production E2E fixtures

Root demo seeders are developer/E2E infrastructure and intentionally live outside `Modules/Request` because they may prepare canonical application users/roles. They are not part of the production `DatabaseSeeder` path.

For the fastest repeatable local UI pass, rebuild only the Request-owned tables and seed the complete E2E matrix with:

```bash
php artisan request:e2e-reset --rebuild
```

The command is blocked in production. It removes Request database rows and exact Request attachment/export storage paths, preserves unrelated module data, then recreates Request accounts/roles, the published DEMO definition, starter templates, and scenarios for draft, pending, warning, overdue, suspended, approved, rejected, returned, cancelled, failed activation, failed outbox and failed export UI states. The normal pending scenario also contains two participant comments and one real private attachment so the collaboration/detail UI can be checked without additional setup. When the command is run as `root` against the local disk, it normalizes only `storage/app/request` back to the configured PHP-FPM owner/group (`REQUEST_FILES_LOCAL_OWNER` / `REQUEST_FILES_LOCAL_GROUP`, default `www-data`) with group-writable, setgid directories; this prevents root-owned DEMO fixtures from breaking browser uploads.

Never document or commit real passwords in this runbook. Local E2E credentials are environment/test-fixture concerns only.
