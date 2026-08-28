# ClientPortal Module — Collaboration Handoff

- Last updated: 2026-08-28
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Stable `main` checkpoint after MR-4: `b8ace3f913c2bfab846ee28ee70db2fda625858c`
- Completed MR: **MR-4 — Muasamcong reference migration**
- Pull request: **#63 — MERGED**
- Feature branch: `feat/clientportal-muasamcong-reference-migration`
- Feature head before merge: `aeaf070231ac28a225e03935b807b479989ce730`
- MR-4 status: **CLOSED / ACCEPTED**
- Next planned MR: **NOT DETERMINED**

## Stable architecture after MR-4

ClientPortal remains an authenticated Client/WebApp platform that can host multiple applications without placing Module-specific business logic in Portal core.

Core rule:

> Không được thêm logic đặc thù Module vào ClientPortal core.

Applications integrate through manifests/contracts, permissions and adapters rather than application-specific conditions in the shared Portal shell/home.

The shared application contract is normalized by `Modules/ClientPortal/Services/ApplicationRegistry.php`. MR-4 adds application-neutral `shell_extensions`, allowing application presentation to attach route-scoped head, overlay and script concerns without named application checks in the shared shell.

The stable shared resolvers remain:

```text
Modules/ClientPortal/Services/PortalAccessResolver.php
Modules/ClientPortal/Services/PortalContextResolver.php
Modules/ClientPortal/Services/PortalNavigationResolver.php
```

MR-2 adaptive navigation and MR-3 0/1/N Portal Home behavior remain preserved.

## MR-4 delivered scope

MR-4 migrated Muasamcong-specific presentation ownership out of the shared ClientPortal App Shell while preserving Muasamcong domain behavior.

Shared ClientPortal core now provides only application-neutral extension points. The shared layout no longer owns Muasamcong-specific route checks, `sync_request_id`, queue-status UI/polling, or Price List presentation details.

Muasamcong presentation remains under its application layer and manifest contract, including:

```text
Modules/ClientPortal/Applications/Muasamcong/manifest.php
Modules/ClientPortal/resources/views/applications/muasamcong/partials/
```

The application owns Price List workspace polish, file-availability presentation, sync queue-status UI/polling and related route-scoped behavior.

No Request domain behavior, Muasamcong domain authorization, database schema, Module enablement or production role assignments were changed.

## Price List availability and recovery

MR-4 hardened Price List actions so logical completion is not confused with physical file availability.

```text
completed + file_available=true  -> Excel-dependent actions available
completed + file_available=false -> "Thiếu file" / recreate guidance
queued/processing                -> status polling
failed                           -> failure/error state
```

PDF conversion is exposed only when the Excel source file is actually available.

The PDF action has distinct visual states:

```text
Before conversion -> "Tạo PDF" / convert icon
After conversion  -> "Tải PDF" / PDF document icon
```

Manual acceptance confirmed Excel generation/download and PDF conversion/download work after the storage permission issue was corrected.

## Project-wide storage permission contract

MR-4 identified a deployment/runtime class of issue that can affect any Module using `storage/app`.

Observed root cause:

```text
storage/app/client-portal             -> root:root 0700
storage/app/client-portal/price-lists -> root:root 0700
PHP-FPM workers                       -> www-data:www-data
```

A queue/root process could create and see files while PHP-FPM could not traverse the parent directories, causing web `Storage::exists()` checks to fail and downloads to return 404 even though the file existed.

The permanent project-level contract is now documented and enforced through:

```text
config/filesystems.php
Dockerfile
docker/entrypoint.sh
docs/STORAGE_PERMISSIONS.md
tests/Feature/System/StoragePermissionsContractTest.php
```

Operational rules:

```text
private storage/app directories -> group-readable/writable/traversable
private storage/app files       -> group-readable/writable
Docker runtime                  -> normalize existing storage ownership/modes on entry
queue and PHP-FPM               -> must share a compatible filesystem group
```

The deployment guide covers local development, VPS without Docker, Docker/Compose, `namei -l` diagnosis, queue/web user mismatch and recovery. `chmod 777` is explicitly not the normal solution.

## PWA and error-recovery hardening

MR-4 also corrected stale installed-PWA behavior by keeping authenticated navigation network-first/no-store, disabling reliance on HTTP cache for service-worker updates and bumping the shell cache version. No broad caching of authenticated business responses was introduced.

ClientPortal 403/404 recovery now prefers a valid same-origin browser Back action and falls back to `/my-apps` for ClientPortal context instead of pushing the user to website root `/`.

## Verification evidence

Automated coverage added/updated includes:

```text
tests/Feature/ClientApps/ClientPortalMuasamcongReferenceMigrationTest.php
tests/Feature/ClientApps/ClientPwaFoundationTest.php
tests/Feature/ClientApps/ClientPortalFileAvailabilityAndErrorRecoveryTest.php
tests/Feature/System/StoragePermissionsContractTest.php
```

Owner-reported verification during MR-4:

```text
ClientPortalMuasamcongReferenceMigrationTest: PASS
ClientPwaFoundationTest: PASS
ClientPortalFileAvailabilityAndErrorRecoveryTest: PASS
StoragePermissionsContractTest: PASS
ClientApps regression: PASS
```

Recorded full ClientApps checkpoint during MR-4:

```text
php artisan test tests/Feature/ClientApps
PASS — 83 tests, 555 assertions
```

Additional focused assertions were added after that full checkpoint and were separately reported PASS. Do not infer a newer full-suite assertion count unless a fresh full-suite output is supplied.

## Manual UI acceptance

Owner-verified MR-4 behaviors:

```text
Muasamcong Price List source/profile/products: PASS
Excel generation/download: PASS
PDF conversion/download: PASS
Pre-convert vs post-convert PDF icons: PASS
Missing-file recovery state: PASS
PWA stale-data corrective behavior: PASS
ClientPortal 403/404 recovery: PASS
```

The final manual UI acceptance was explicitly reported PASS before PR creation and merge.

## Merge closeout

MR-4 was merged through PR #63 after explicit owner approval.

```text
Feature head before merge:
aeaf070231ac28a225e03935b807b479989ce730

Merge commit / stable code checkpoint:
b8ace3f913c2bfab846ee28ee70db2fda625858c
```

PR #63 was verified open, non-draft and mergeable immediately before merge. The exact expected feature head SHA was used as the merge guard. GitHub had no registered CI status checks for that head; the merge relied on the owner-reported automated and manual acceptance evidence recorded above.

MR-4 is therefore **CLOSED / ACCEPTED**. Do not continue implementation on `feat/clientportal-muasamcong-reference-migration`.

## Roadmap checkpoint

```text
MR-1 — Portal Architecture Foundation: MERGED / CLOSED
MR-2 — Adaptive Navigation: MERGED / CLOSED
MR-3 — Dynamic Portal Home: MERGED / CLOSED
MR-4 — Muasamcong reference migration: MERGED / CLOSED
Next MR / phase: NOT DETERMINED
```

## Next-step boundary

There is currently no authorized MR-5 or next ClientPortal implementation phase.

Before creating another branch or changing code:

1. Start from current `main` and read this handoff plus `docs/GITHUB_COLLABORATION_WORKFLOW.md` and `.codex/standards/CLIENT_APP_UI_STANDARD.md` where applicable.
2. Inspect the current source relevant to the new requested goal.
3. Define a narrow scope, ownership boundary, tests and manual acceptance criteria.
4. Present the proposal to the owner.
5. Create a new branch and implement only after explicit owner approval.

Do not infer that the next phase should modify Request, Muasamcong, organization/department routing, shared components, production enablement or runtime configuration without an explicit new requirement and approval.
