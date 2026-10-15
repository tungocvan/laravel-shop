# TNV — Production Docker debug profile

> Snapshot: **2026-10-15 06:02:37 UTC** (13:02:37 Asia/Ho_Chi_Minh). Source: operator output of `production-debug.sh --profile --project-dir /opt/projects/tnv` at commit `fda4351b0` of the testing branch. This is historical runtime evidence, **not** a claim about current runtime. No HTTP/UI verification has been performed.

## Project and source

| Property | Observed |
| --- | --- |
| Production host path | `/opt/projects/tnv` |
| Git branch | `main` |
| Host Git HEAD | `26e789fb5` |
| Tracked changes | `0` (untracked files were not checked by this snapshot) |
| Docker Compose project | `tnv` |
| Application working directory | `/var/www/html` |
| Docker | `29.3.0` |
| Compose | `v5.1.1` |
| PHP CLI | `8.3.35` |
| Laravel | `12.67.0` |
| CLI OPcache validate timestamps | `0` (not proof of FPM OPcache setting) |

## Observed services

| Service | State | Health | Image ID prefix |
| --- | --- | --- | --- |
| web | running | healthy | `sha256:c6bb1dbcd678` |
| scheduler | running | healthy | `sha256:f37a0eea3ea8` |
| app | running | healthy | `sha256:247a6da0b6c3` |
| queue | running | healthy | `sha256:b5129683da1b` |
| queue-admission-documents | running | healthy | `sha256:bdb1a4bdc5b4` |
| queue-request | running | healthy | `sha256:4701e4c60779` |
| socket | running | healthy | `sha256:960c6905e10c` |
| redis | running | healthy | `sha256:e7723ff73d96` |
| db | running | healthy | `sha256:d9f7eb263729` |

App container ID prefix at snapshot: `190b840be2c1`. Docker configured user was empty; **do not infer PHP-FPM worker identity** from this value.

## App mounts (observed)

- `bind -> /var/www/html/.env`
- `bind -> /var/www/html/Modules`
- `volume -> /var/www/html/storage`

The first profile output joined these mounts with literal `\\n` due to a rendering bug; the three mount entries above were normalized from that output. Source host paths were intentionally omitted.

**Important:** `Modules` is bind-mounted, but the origin of the remaining application files is not yet verified. Host Git HEAD alone does not prove the running image revision. Do not assume PHP-FPM OPcache settings from CLI PHP. Container health is not HTTP/UI acceptance.

## Compose overlay filenames

- `compose.yaml`
- `compose.queue.yaml`
- `compose.scheduler.yaml`
- `compose.socket.yaml`

Preserve all production overlays and .env. Never dump full Compose config or secrets into a profile.

## Fast incident workflow

1. Read `docs/PRODUCTION_DEBUG_PROMPT.md` and production guardrails, then this file.
2. Check whether a deployment, image rebuild, container recreation, overlay change, Git pull, PHP-FPM reload or configuration change occurred since **2026-10-15 06:02:37 UTC**. If yes, regenerate profile.
3. For a Laravel/Livewire route failure, ask for the route, HTTP symptom and time, then inspect narrowly scoped application logs. Avoid repeating full Docker inventory if this snapshot remains valid.
4. Before proposing code changes, determine whether the affected file lives under the bind-mounted `Modules` path or elsewhere, and confirm the effective code in the relevant runtime container.
5. Fix source on GitHub branch/PR, test, then deploy only per production docs and operator approval. Verify HTTP/UI, new logs and service health.

## Unverified / follow-up

- HTTP/UI status, nginx/proxy mapping, public URL routing.
- Effective Git/source revision inside image and container.
- Actual bind source directories (omitted for safety).
- PHP-FPM OPcache and worker user.
- Database connectivity, queue/scheduler functionality, migration status and log exceptions.

These are **unknown**, not failures and not PASS.
