# NTD — Production Docker debug profile

> Snapshot: **2026-10-15 06:09:22 UTC** (13:09:22 Asia/Ho_Chi_Minh). Source: operator output of `production-debug-profile.sh --project-dir /opt/projects/ntd` from testing worktree at commit `dad2529a3`. Historical evidence only. **HTTP/UI health not verified.**

## Project and Git

| Property | Observed |
| --- | --- |
| Production host path | `/opt/projects/ntd` |
| Compose project | `ntd` |
| Git branch | `main` |
| Host Git HEAD | `ea5867ae5` |
| Tracked changes | `0` (untracked files not included) |
| Docker | `29.3.0` |
| Compose | `v5.1.1` |
| App working directory | `/var/www/html` |
| PHP CLI | `8.3.35` |
| Laravel | `12.67.0` |
| CLI OPcache validate timestamps | `0` (not evidence of FPM settings) |

## Observed services

| Service | State | Health | Image ID prefix |
| --- | --- | --- | --- |
| web | running | healthy | `sha256:02d0eb74c1db` |
| app | running | healthy | `sha256:b6bb72d0032b` |
| scheduler | running | healthy | `sha256:ac5d7cc2e67c` |
| queue | running | healthy | `sha256:ac70d774ff5b` |
| queue-request | running | healthy | `sha256:ea81d2753018` |
| queue-admission-documents | running | healthy | `sha256:16870dd0b4a5` |
| socket | running | healthy | `sha256:13f0119e8bea` |
| redis | running | healthy | `sha256:e7723ff73d96` |
| db | running | healthy | `sha256:d9f7eb263729` |

App container ID prefix at snapshot: `06566d3a2dd6`. Docker configured user was empty; **PHP-FPM worker identity remains unverified**.

## App mounts (type and destination only)

- `volume -> /var/www/html/storage`
- `bind -> /var/www/html/.env`
- `bind -> /var/www/html/Modules`

Mount source paths intentionally omitted. `Modules` is bind-mounted; **do not infer** that the remaining app source matches host Git or image build commit.

## Compose overlay filenames

- `compose.queue.yaml`
- `compose.scheduler.yaml`
- `compose.socket.yaml`
- `compose.yaml`

## Comparison with TNV snapshot

- NTD host Git HEAD `ea5867ae5` differs from TNV `26e789fb5`.
- Both snapshots showed 9 services running/healthy, but most service image ID prefixes differ.
- Both app containers expose the same three mount destinations and types.
- **Do not assume** the sites run identical source or have identical behavior.

## Fast incident workflow

1. Read `docs/PRODUCTION_DEBUG_PROMPT.md`, Docker guardrails and this profile.
2. Revalidate after Git pull, rebuild, container recreation, overlay changes, PHP-FPM reload, or config/environment changes.
3. Start with exact failing URL, HTTP symptom and timestamp. Inspect narrowly scoped logs without exposing secrets.
4. Distinguish host Git, bind-mounted `Modules`, image files and running container code before diagnosing a code/deployment mismatch.
5. Use GitHub branch/PR and tests; do not mutate production Docker/database/runtime without operator approval.

## Unverified / follow-up

- HTTP/UI behavior, proxy routing, queue processing, database connectivity, migrations and logs.
- Effective code revision inside running container, PHP-FPM OPcache and worker identity.

Unknown does not mean PASS or FAIL.
