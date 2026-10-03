# Production Docker Debug Skill

## Purpose

Use this skill to diagnose and safely resolve a Laravel route or capability on Production Docker with the smallest useful number of operator commands.

Minimum input:

```text
PROJECT_PATH=/opt/projects/<project>
TARGET_ROUTE=/route/to/debug
```

Optional:

```text
SYMPTOM=...
EXPECTED=...
OCCURRED_AT=...
```

The repository and its current production documentation are the source of truth. Never substitute remembered topology or an older chat for current repository contracts.

## 1. Mandatory document gate

Before giving the first production command, read the current versions of:

1. `docs/PRODUCTION_DEBUG_PROMPT.md`
2. `docs/PRODUCTION_DOCKER_RUNTIME.md`
3. `docs/PRODUCTION_DOCKER_WORKFLOW_GUARDRAILS.md`
4. `docs/PRODUCTION_OPERATIONS_GUIDE.md`
5. `docs/GITHUB_COLLABORATION_WORKFLOW.md`
6. `docs/chuyen_chat.md`

Then locate and read documentation for the module/capability that owns `TARGET_ROUTE`. Follow direct mandatory references from those documents when relevant to the incident.

If documentation conflicts with this skill, the current repository documentation wins.

## 2. Project/runtime resolution

Derive the expected Compose project from the project directory basename:

```text
/opt/projects/tnv -> tnv
/opt/projects/ntd -> ntd
```

Verify it at runtime; do not hard-code project names, container names, ports, domains, IP addresses, or service names from prior incidents.

Keep these layers distinct:

```text
host Git source
Docker image
running container
bind mounts
named volumes
process/OPcache state
```

A host `git pull` does not prove that the process serving production is executing the new code.

## 3. First checkpoint

Production diagnosis is read-only by default. The normal first checkpoint is exactly:

```bash
cd "$PROJECT_PATH" && ./production-debug.sh --docker
```

This repository helper is the canonical baseline. Use manual Docker commands only when the helper is missing, fails, or cannot answer the specific question.

From its output establish the actual Compose project, app service/container, service health, PHP/Laravel runtime, runtime user, working directory, environment, maintenance state and other non-secret baseline information.

If any required service is `restarting`, `created`, `exited`, `dead`, `unhealthy`, or otherwise non-running, treat that state as diagnostic evidence. Do not automatically run `up`, restart, recreate or rebuild before understanding the cause.

## 4. Checkpoint discipline

Work one evidence-producing checkpoint at a time. Format each checkpoint as:

```text
Checkpoint N — <goal>
Commands: 1
Run on: HOST | CONTAINER
Mode: READ-ONLY | MUTATING
Risk: NONE | LOW | MEDIUM | HIGH

<command>
```

Then stop and wait for the operator output.

A checkpoint may combine a few read-only shell expressions into one command only when they answer one diagnostic question and keep the output easy to interpret.

If a command fails, stop, analyze that output, and do not pile on unrelated commands.

After every meaningful result distinguish:

- **FACT** — directly supported by source/log/runtime evidence.
- **DIAGNOSIS** — root cause when evidence is sufficient.
- **HYPOTHESIS** — still unproven.
- **NEXT ACTION** — the single next checkpoint.

Once the root cause is proven, stop collecting redundant evidence.

## 5. Git/source baseline

When source/version can matter, inspect the production host without cleaning it:

```bash
cd "$PROJECT_PATH" && git status -sb && git branch --show-current && git log -5 --oneline
```

Production-only untracked Compose overlays can be intentional. Files such as these must not be treated as trash merely because Git reports them untracked:

```text
compose.queue.yaml
compose.scheduler.yaml
compose.socket.yaml
```

Never use `git clean -fd` or `git reset --hard` as a production diagnosis shortcut.

## 6. Map the target route to real source

Use the canonical Artisan helper rather than guessing a container name:

```bash
./run-docker-artisan.sh "php artisan route:list ..."
```

Map the target through the real implementation:

```text
route
-> middleware
-> controller or Livewire component
-> service/domain logic
-> model/query
-> Blade/JS
-> permission/policy
```

Inspect the current source before proposing a fix. If source evidence already proves the defect, do not demand unnecessary runtime logs.

## 7. Choose the narrow diagnostic branch

Do not run every diagnostic helper. Classify the incident and collect the smallest relevant evidence.

### HTTP 500

Prefer the Laravel exception nearest the reproduced request. Use `./production-debug.sh --logs` when appropriate. Prioritize exception class/message and the first application file/line over a huge vendor stack.

### HTTP 419

Do not assume CSRF, `SESSION_DOMAIN`, or session configuration. Establish whether the action ran and whether the failure belongs to CSRF/session, Livewire snapshot/state/request lifecycle, stale frontend state, or another layer. For simple CRUD mutations, a standard Laravel POST can be safer and simpler than a large Livewire request when the evidence supports that change.

### HTTP 403

Inspect authentication, middleware, permissions, policies and representative authorization behavior. Do not bypass authorization to make the UI pass.

### HTTP 404

Distinguish route/bootstrap/module state, middleware, application 404, reverse-proxy 404 and stale image/container source.

### HTTP 502/504 or degraded service

Focus on proxy -> upstream app -> PHP-FPM -> service/container health and service discovery. Do not start by changing Laravel business code.

### Database

Use:

```bash
./production-debug.sh --database
```

Verify the connection from the correct app runtime, expected schema/migration state and correct project/volume. Do not use destructive migrations to test connectivity.

### Storage/permissions

Use:

```bash
./production-debug.sh --permissions
```

Check writability as the actual PHP-FPM user (normally `www-data`). Root/CLI writability does not prove request-time writability. Never use `chmod 777` as a production fix.

### Queue/scheduler

Start from the Docker baseline, identify the exact worker/service, inspect only its relevant log/state, and use safe Artisan inspection such as `queue:failed` when applicable. Do not restart every worker by default.

### Environment/configuration

The canonical production environment file is `PROJECT_PATH/.env`, but never dump it or expose secrets. Runtime Laravel code should consume environment through `config()`, not direct `env()` outside config files. Effective `config()` in the correct app runtime is the application source of truth.

If the operator intentionally changes production `.env`, use the repository contract:

```bash
./run-updated-env.sh
```

Do not replace it with ad-hoc Compose reconciliation unless the current docs require otherwise.

### Disk/log maintenance

Diagnosis and cleanup are separate. For a read-only report use:

```bash
./production-cleanup.sh --report
```

Only use `--logs` or `--docker` after the report and an explicit maintenance decision. Docker cleanup is host-wide on a shared production host.

## 8. Mutation gate

Before any mutating production command, state and understand:

- whether it runs on host or container;
- the exact project/service affected;
- whether the effect is persistent;
- whether DB or volumes are affected;
- why the evidence justifies the mutation;
- the rollback/recovery boundary.

Do not use these as diagnosis defaults:

```text
php artisan migrate:fresh
php artisan db:wipe
php artisan migrate:rollback
git reset --hard
git clean -fd
docker compose down -v
docker system prune
docker volume prune
chmod 777
random .env edits
random cache clearing
restart/rebuild of the whole stack
```

Never expose `APP_KEY`, database passwords, API keys, tokens or credentials.

## 9. Source-defect workflow

If evidence proves a source defect:

1. Do not hot-edit production source.
2. Inspect current main and collaboration workflow.
3. Create a dedicated fix branch.
4. Apply the smallest durable fix.
5. Add targeted regression coverage when appropriate.
6. Commit logical changes.
7. Use the approved production branch acceptance workflow only when the operator allows it.
8. Refresh only the runtime state actually required.
9. Require real UI/HTTP acceptance.
10. Create/review PR.
11. Merge only with explicit operator/user approval.
12. Return production to the intended base branch and verify the final SHA/runtime.

Do not hide a source defect with a production-only workaround when a durable source fix is appropriate.

## 10. Deployment mode and stale runtime

Before deciding how new source reaches production, determine whether application code is supplied by a bind mount, baked into the image, or another deployment mechanism.

For bind-mounted source, host changes can become visible in the container filesystem without an image rebuild, but Laravel caches/process lifetime/OPcache can still make behavior stale.

For image-baked source, a host pull alone is insufficient.

Evaluate cache layers separately:

```text
config cache
route cache
view cache
application cache
permission cache
OPcache
process lifetime
```

Do not run `optimize:clear` automatically.

If behavior contradicts verified source, investigate compiled/process state. In particular, `opcache.validate_timestamps=Off` can leave PHP-FPM executing old bytecode. Do not blindly restart or signal PHP-FPM: first identify the correct app service/process and deployment mode, then use the smallest reload/restart allowed by the current production docs.

## 11. Database/migration gate

Run production migrations only when the current code actually contains a required migration and it has been reviewed for the target production state.

Before applying, understand connection, expected tables, migration records, partial-schema risk, backward compatibility and recovery boundary.

Do not run `migrate` after every pull by habit. Do not use MariaDB initialization variables as a way to mutate credentials/schema of an existing named DB volume. Do not disable `ONLY_FULL_GROUP_BY` to hide an SQL defect.

## 12. Test strategy

Production images may be built with Composer `--no-dev`. If `php artisan test` is unavailable, do not install development dependencies into production merely to run PHPUnit.

Production verification may use safe syntax/runtime checks, Artisan inspection, route smoke, HTTP/UI acceptance and fresh-log verification. Automated PHPUnit/regression suites belong in the appropriate development/CI runtime under the repository workflow.

Remember that production MariaDB/MySQL-compatible behavior and test SQLite behavior can differ. Identify the actual driver/runtime before attributing a failure to source.

## 13. Livewire/performance branch

When the symptom is UI slowness, do not assume Docker is the cause. Inspect Livewire request count, public/snapshot state, payload size, render lifecycle, query behavior and server round trips.

UI-only state such as tabs, accordions and wizard navigation should not require server round trips when no server validation/data mutation is needed; client-side Alpine state can be appropriate. Business mutations remain server-side.

## 14. Acceptance gate

A route merely opening is not enough to declare production PASS. Evaluate every applicable gate:

- correct repository/branch/checkpoint;
- correct Compose project/runtime;
- host/image/container source alignment;
- required services healthy;
- effective config correct;
- DB/migration readiness;
- module dependencies;
- permission infrastructure and representative authorization;
- storage ownership for runtime user;
- Redis/cache/session when relevant;
- queue/scheduler lifecycle when relevant;
- route/bootstrap;
- HTTP/UI smoke;
- no new important exception in logs;
- production-only overlays preserved;
- rollback boundary understood.

Do not force unrelated gates; explicitly decide applicability.

## 15. Closeout

After an approved deploy/merge, use the current repository docs and finish with an applicable baseline such as:

```bash
git status -sb
docker compose -p "$(basename "$PWD")" ps --all
./production-debug.sh --docker
```

Confirm the intended base branch/SHA, healthy required runtime, target route/UI PASS, no new important exception, no forgotten temporary workaround and intact production overlays.

## 16. Core decision order

Use this mental model:

```text
Observe
-> identify affected layer
-> collect minimal evidence
-> diagnose root cause
-> apply smallest safe change
-> verify health
-> record/closeout
```

Never use:

```text
error
-> restart everything
-> clear everything
-> rebuild everything
-> delete things
-> hope
```

When local/test passes but production fails, investigate in this order unless direct evidence points elsewhere:

```text
1. correct Compose project/container
2. actual image/source
3. effective config() and cache state
4. service lifecycle/restart requirements
5. storage ownership/runtime user
6. DB/migration state
7. Redis/cache/session/queue
8. module runtime state/dependencies
9. permission infrastructure
10. route/bootstrap/reverse proxy
11. application code defect
```

## 17. Invocation contract

A new chat can invoke the workflow with only:

```text
Áp dụng production Docker debug skill.

PROJECT_PATH=/opt/projects/ntd
TARGET_ROUTE=/admin/admission/dvhc
```

Optionally add a symptom:

```text
SYMPTOM=Xóa báo 419
```

The first response must perform the document gate and then start with the read-only canonical baseline. Do not ask the operator to restate Compose topology, container names, PHP/Laravel versions, branch or module when those can be safely discovered.
