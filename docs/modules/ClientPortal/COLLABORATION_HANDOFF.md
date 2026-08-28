# ClientPortal Module — Collaboration Handoff

- Last updated: 2026-08-28
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Stable `main` checkpoint before MR-4: `67e92ebfdc2599efae47318f3740e78dea98481f`
- Current MR: **MR-4 — Muasamcong reference migration**
- Feature branch: `feat/clientportal-muasamcong-reference-migration`
- MR-4 status: **IMPLEMENTED / AUTOMATED TESTS PASS / MANUAL UI ACCEPTED / PR APPROVAL REQUIRED**
- Pull request: **NOT YET CREATED**

## Stable architecture entering MR-4

ClientPortal is an authenticated Client/WebApp platform that hosts multiple applications without putting Module-specific business logic in Portal core.

Core rule:

> Không được thêm logic đặc thù Module vào ClientPortal core.

Applications integrate through manifests/contracts, permissions and adapters rather than application-specific conditions in the shared Portal shell/home.

The shared application contract is normalized by `Modules/ClientPortal/Services/ApplicationRegistry.php`. MR-4 extends that normalized contract with application-neutral `shell_extensions` so application presentation can attach route-scoped head, overlay and script concerns without named application checks in the shared shell.

The stable shared resolvers remain:

```text
Modules/ClientPortal/Services/PortalAccessResolver.php
Modules/ClientPortal/Services/PortalContextResolver.php
Modules/ClientPortal/Services/PortalNavigationResolver.php
```

## MR-4 approved scope

Approved contract:

```text
MR-4 — Muasamcong Reference Migration

Shared ClientPortal core:
- application-neutral extension points only
- no Muasamcong route/queue/price-list conditions

Muasamcong application layer:
- owns price-list polish
- owns sync queue-status UI/polling

No domain behavior changes.
No Request changes.
No DB/runtime/production enablement changes.
No speculative shared component library.
```

Corrective fixes discovered during manual acceptance were included only where required to make the migrated behavior reliable:

- stale PWA navigation/service-worker hardening;
- file-availability-aware Price List actions;
- ClientPortal-friendly 403/404 recovery;
- storage permission contract for files created by web/queue processes;
- distinct pre-convert and post-convert PDF action icons.

## MR-4 delivered architecture

### 1. Shared App Shell is application-neutral

`Modules/ClientPortal/resources/views/layouts/application.blade.php` no longer owns Muasamcong-specific route checks, `sync_request_id`, drug-pricing polling, queue-status text, or Price List polish includes.

The shell resolves normalized `shell_extensions` from the current application and provides generic extension points. Adding another application-specific presentation extension does not require adding a named Module condition to ClientPortal core.

### 2. Muasamcong owns its presentation extensions

`Modules/ClientPortal/Applications/Muasamcong/manifest.php` declares route-scoped shell extensions for:

- Price List workspace polish;
- Drug Pricing sync queue-status overlay/polling.

Muasamcong-specific presentation remains under:

```text
Modules/ClientPortal/resources/views/applications/muasamcong/partials/
```

including the Price List polish, file-availability behavior and sync queue-status UI.

### 3. Domain behavior remains authoritative

MR-4 does not move Muasamcong domain behavior into ClientPortal core. Existing controllers, jobs, permission checks, status routes and domain data remain authoritative.

The Price List backend continues to verify actual Excel/PDF file existence before download, PDF conversion, sharing or email delivery.

## Price List availability corrective fix

Manual acceptance found a case where the database record was `completed` while the web process could not read the generated Excel file. Rendering actions from `status === completed` alone therefore produced misleading UI and a 404 download.

The presentation layer now checks the existing Price List status contract and distinguishes logical completion from physical file availability:

```text
completed + file_available=true  -> Excel-dependent actions available
completed + file_available=false -> "Thiếu file" state / recreate action
queued/processing                -> keep polling
failed                           -> show failure state/error
```

PDF conversion is only exposed when the Excel file is actually available. The PDF action also uses different visual states:

```text
Before conversion -> "Tạo PDF" / convert icon
After conversion  -> "Tải PDF" / PDF document icon
```

## Storage permission root cause and permanent contract

The owner reproduced a newly generated Excel record with:

```text
status      = completed
file_path   = client-portal/price-lists/1/bang-gia-20260828-125103.xlsx
Storage::disk('local')->exists(...) = true from CLI/root
```

Filesystem inspection then showed:

```text
storage/app/client-portal  -> root:root 0700
storage/app/client-portal/price-lists -> root:root 0700
PHP-FPM workers            -> www-data:www-data
```

Root/queue could therefore create and see the file while PHP-FPM could not traverse the parent directories. This caused web `Storage::exists()` to report false and downloads to return 404.

After normalizing group/modes, the same existing Excel file downloaded successfully; a new end-to-end Price List flow also passed.

MR-4 now establishes a project-level storage contract rather than a ClientPortal-only workaround:

```text
private storage/app directories -> group-readable/writable/traversable
private storage/app files       -> group-readable/writable
Docker runtime                  -> normalize storage ownership/modes on entry
queue and PHP-FPM               -> must share a compatible filesystem group
```

Implementation/docs:

```text
config/filesystems.php
docker/entrypoint.sh
Dockerfile
docs/STORAGE_PERMISSIONS.md
tests/Feature/System/StoragePermissionsContractTest.php
```

The operational guide covers local development, VPS without Docker, Docker/Compose, `namei -l` diagnosis, queue/web user mismatch and recovery. It explicitly avoids `chmod 777` as the normal solution.

## PWA corrective fix

During acceptance the website rendered current Price List data while the installed PWA could present stale behavior. The service worker itself did not intentionally cache authenticated navigation, but an installed worker/cache could remain stale.

The PWA contract was hardened so authenticated navigation is network-first/no-store, worker registration does not rely on the HTTP cache, and the shell cache version was bumped so old cache entries are discarded.

No broad authenticated business-response caching was introduced.

## 403/404 recovery

ClientPortal users should not be pushed to the website root `/` after an application error.

MR-4 provides recovery behavior for 403/404 pages:

```text
preferred action -> browser "Quay lại" when a valid same-origin history exists
fallback         -> /my-apps for ClientPortal context
```

This keeps PWA/ClientPortal navigation inside the client application experience.

## Automated verification

Focused coverage added/updated includes:

```text
tests/Feature/ClientApps/ClientPortalMuasamcongReferenceMigrationTest.php
tests/Feature/ClientApps/ClientPwaFoundationTest.php
tests/Feature/ClientApps/ClientPortalFileAvailabilityAndErrorRecoveryTest.php
tests/Feature/System/StoragePermissionsContractTest.php
```

Verification evidence reported by the owner during MR-4:

```text
ClientPortalMuasamcongReferenceMigrationTest: PASS
ClientPwaFoundationTest: PASS
ClientPortalFileAvailabilityAndErrorRecoveryTest: PASS
StoragePermissionsContractTest: PASS
ClientApps regression: PASS
```

Earlier full ClientApps checkpoint during MR-4:

```text
php artisan test tests/Feature/ClientApps
PASS — 83 tests, 555 assertions
```

Additional focused tests and corrective assertions were added after that checkpoint and were also reported PASS. The final owner report confirms the requested automated checks pass; do not infer a newer assertion count unless a fresh full-suite output is supplied.

## Manual UI acceptance for MR-4

Owner-verified behaviors:

```text
Muasamcong Price List source/profile/products: PASS
- synced product data visible in PWA after PWA cache hardening
- export profile and product selection available

Excel generation/download: PASS
- new Price List generated
- root filesystem permission mismatch diagnosed
- storage permission repaired
- existing generated Excel downloaded successfully

PDF conversion/download: PASS
- PDF action hidden until Excel is actually available
- conversion flow completes
- PDF download works
- pre-convert and post-convert PDF icons are visually distinct

File-missing behavior: PASS
- missing physical file no longer presents misleading download/convert actions
- UI presents a recoverable missing-file state

ClientPortal error recovery: PASS
- 403/404 recovery no longer forces the user to website root
- back/fallback behavior targets the ClientPortal experience
```

The owner explicitly reported the final UI flow as PASS.

## Preserved behavior and boundaries

MR-4 preserves:

- MR-2 adaptive navigation;
- MR-3 0/1/N Portal Home behavior;
- manifest-driven application availability and permissions;
- Request application behavior;
- Muasamcong domain authorization and data ownership;
- existing Price List routes/status contracts;
- no database/schema/seeder changes;
- no Module enable/disable changes;
- no production feature activation changes.

## Current GitHub checkpoint

```text
Base: main @ 67e92ebfdc2599efae47318f3740e78dea98481f
Head branch: feat/clientportal-muasamcong-reference-migration
PR: not yet created
MR-4 acceptance: automated PASS + manual UI PASS
```

Before PR creation, verify the current branch is still ahead of and not behind `main`, inspect the final changed-file set, and confirm no unrelated change entered the branch.

## Roadmap checkpoint

```text
MR-1 — Portal Architecture Foundation: MERGED / CLOSED
MR-2 — Adaptive Navigation: MERGED / CLOSED
MR-3 — Dynamic Portal Home: MERGED / CLOSED
MR-4 — Muasamcong reference migration: IMPLEMENTED / ACCEPTED / PR APPROVAL REQUIRED
```

## Next-step boundary

MR-4 implementation and manual acceptance are complete. The next action is **PR preparation/creation only after explicit owner approval**.

Do not merge MR-4 without a separate explicit merge approval after the PR state, exact head, changed files and mergeability have been checked.
