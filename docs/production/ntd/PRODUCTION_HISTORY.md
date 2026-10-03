# NTD Production History

Project key: `ntd`

This file is a production handoff/history for `/opt/projects/ntd`. Canonical production docs remain authoritative when they conflict with this history.

## Stable production contract

- Host project path: `/opt/projects/ntd`.
- Compose project: `ntd`, derived from the project directory basename.
- Application working directory in the app container: `/var/www/html`.
- Public production domain: `nguyenthidinh.edu.vn`.
- Web binding last verified: `127.0.0.1:8086 -> container 8080`.
- NTD must not take the port/runtime belonging to another Compose project on the shared host.
- Core services observed for NTD: `app`, `web`, `db`, `redis`, `queue`, `queue-admission-documents`, `queue-request`, `scheduler`, `socket`.
- MariaDB is the production database family; Redis is present for applicable runtime concerns.
- PHP-FPM production has previously used OPcache with `opcache.validate_timestamps=0/Off`. A host/source update can therefore be visible on disk while PHP-FPM still executes stale bytecode.
- Prefer the repository helpers `./production-debug.sh`, `./run-docker-artisan.sh`, and `./run-updated-env.sh` according to the canonical production docs.
- Do not install host PHP/dev dependencies merely to debug the Docker application.
- Production-only untracked overlays observed and intentionally preserved:
  - `compose.queue.yaml`
  - `compose.scheduler.yaml`
  - `compose.socket.yaml`
- Never use `git clean` to remove these overlays.
- Do not use `docker compose down` as a routine production operation.
- For a source-only PHP/Blade change on the established bind-mounted runtime, an app/PHP-FPM refresh may be required because of OPcache. Verify deployment mode and current runtime before applying it.
- Do not recreate/restart `web` merely for PHP/Blade source changes when the app runtime is the affected layer.

## Verify at every incident start

These facts are volatile and must be checked rather than trusted from history:

- required service health/state;
- current Git branch and HEAD;
- `origin/main` alignment;
- host source versus running container/image/bind mount;
- current port bindings if the incident involves proxy/networking;
- effective Laravel config when configuration is relevant;
- current DB/migration state when schema is relevant;
- current runtime ownership/writability when storage is relevant.

The preferred first check is:

```bash
cd /opt/projects/ntd && ./production-debug.sh --docker
```

If that baseline is healthy, move quickly to the target route/source and incident-specific evidence instead of rediscovering the whole Docker topology.

## Admission production history

### Public storage / logo / favicon

A prior Admission incident showed that saving branding data could succeed while the public asset failed to render because runtime/public-storage traversal permissions were wrong. Durable fixes were merged before the later Admission UX work.

Operational lesson:

- distinguish database/save success from public asset delivery;
- check the actual generated public URL and file path;
- verify the `public/storage` path/symlink and traversal/read permissions as the web/PHP runtime user;
- do not use `chmod 777`;
- if source already contains the fix, verify source/runtime/OPcache before rewriting the feature.

### Shared Admission branding

Admission header/shared branding was updated so the configured logo is used consistently. If one surface shows the favicon/logo while another shows a broken image, compare the generated URLs and shared presentation source before assuming upload itself failed.

### Legacy application date

Production record(s) were found with legacy `ngay_lam_don` format `d/m/Y` such as `21/07/2026`. Direct generic Carbon parsing caused a 500. The durable source fix normalizes supported `Y-m-d` and `d/m/Y` formats before populating the form.

### Admission edit wizard

Edit-mode wizard navigation was moved to client-side Alpine state so switching steps does not require unnecessary Livewire server round trips. Save still validates all business steps server-side, and validation failure can move the client UI to the failing step.

### DVHC actions

A previous DVHC 419 incident was addressed by replacing simple row/bulk mutations with standard Laravel POST flows where appropriate instead of carrying unnecessary Livewire request state. A new 419 must still be reproduced and mapped to the exact current action; do not automatically assume it is the same cause.

### Campus fields and public search

Admission campus name/address fields were added to the application flow and public search presentation. If public search is missing branding/images while text data is current, verify asset URL/storage/runtime state separately from application-data rendering.

## Git history landmarks

Historical landmarks are navigation aids, not a substitute for checking current `main`.

- PR #243: durable public storage permission work.
- PR #244: follow-up/race correction for public storage runtime.
- PR #245: shared Admission branding/header logo work.
- PR #246: Admission campus configuration, registration validation and DVHC row-save changes; historical merge SHA prefix `cb987fa1`.
- PR #252: Admission edit/DVHC/search UX fixes; merged main commit `4e7c46ac11269034a166754bdcfca3f141f559dd`.

## Current incident handoff — 2026-10-03

Reported surfaces:

- `/admin/admission/dvhc`: filter/action reports HTTP 419; user also wants the large Import/Export area refactored into a professional collapsed/toggle section.
- `/admin/admission/settings`: website logo preview is broken again while favicon is visible; verify whether this is storage/public URL/stale runtime/source alignment before rewriting upload logic.
- `/admission/search`: images/logo are not rendering; this may share the branding/storage/runtime cause but remains a hypothesis until URLs/runtime evidence confirms it.

Baseline captured on 2026-10-03:

- Compose project resolved correctly as `ntd`.
- `app`, `web`, `db`, `redis`, queues, scheduler and socket were all running healthy.
- app runtime reported PHP 8.3.35 and Laravel 12.67.0, environment `production`, debug OFF, maintenance OFF.
- web binding was `127.0.0.1:8086->8080`.

Therefore the next useful step for this incident is source/runtime alignment and route-specific evidence, not a Docker rebuild or broad service restart.
