# Settings/EnvManager Livewire Refactor Plan

Plan date: 2026-08-12

Scope: `Modules/System/Livewire/Settings/EnvManager.php`, its Blade UI, environment snapshot service boundary, integration into the existing ENV settings page, focused tests, and verification of the existing `/admin/system/settings/env` Admin Menu entry.

Status: **Awaiting explicit approval before implementation.**

## 1. Goal

Keep the useful environment-snapshot capability, but replace the current project-root `.env.<suffix>` copy behavior with a secure, private, audited snapshot workflow.

Primary goals:

- enforce `system.env.update` at the snapshot action boundary;
- remove arbitrary snapshot suffix input;
- stop creating plaintext secret copies in the project root;
- store timestamped snapshots under private ignored storage with restrictive permissions;
- serialize snapshot creation and apply bounded retention;
- remove dead/incomplete tab logic from the Livewire component;
- mount the component intentionally in the existing ENV management page;
- preserve the existing ENV route/menu architecture without creating a duplicate Admin Menu entry.

This is a security refactor of an existing feature, not a general-purpose file backup manager.

## 2. Confirmed Current State

`EnvManager` currently exposes:

```php
exportEnv(string $envType, EnvManagerService $service)
```

and passes caller-controlled `$envType` directly to:

```php
EnvManagerService::exportToEnvironment($suffix)
```

which writes to:

```text
base_path(".env.{$suffix}")
```

The current Blade only sends `production` and `local`, but a public Livewire method cannot rely on the Blade as its authorization/input boundary.

The component also contains an unfinished `getTabsDefinition()` method; `render()` passes its null result to a Blade that does not use it.

## 3. Important Integration Finding

The ENV page currently contains:

```blade
{{-- @livewire('system.settings.env-manager') --}}
```

so `EnvManager` is presently not mounted in the active UI.

This refactor will intentionally re-enable it as a small snapshot toolbar in the existing ENV page header, after its security model has been corrected.

No standalone route is required.

## 4. Route and Admin Menu

The component belongs to the existing page:

```text
GET /admin/system/settings/env
name: admin.system.settings.env
middleware: auth:admin + permission:system.env.view,admin
```

The canonical Admin Menu already is:

```text
Công cụ Hệ thống
└── Quản lý ENV
    URL: /admin/system/settings/env
    Can: system.env.view
```

Do not create another route or Admin Menu item for snapshots.

Page/menu visibility remains `system.env.view`.
Snapshot mutation requires `system.env.update`.

## 5. Authorization

`EnvManager` will use `AuthorizesSystemActions`.

Every snapshot operation must enforce:

```text
system.env.update
```

inside the Livewire action before service execution.

View-only users may see snapshot status/explanation but all snapshot buttons must be disabled/hidden as appropriate, while server-side authorization remains authoritative.

## 6. Replace Arbitrary Suffix With Fixed Operation IDs

The browser must not send a filename, path or free-form suffix.

Use a fixed server-owned registry/allowlist such as:

```text
production → label "Production"
local      → label "Local"
```

Livewire may send only the operation ID (`production` or `local`).

The service must reject every other value before filesystem access.

Do not allow:

- `../` traversal;
- dots/slashes in user-controlled suffixes;
- arbitrary environment names;
- arbitrary destination paths;
- custom filenames from browser state.

## 7. Snapshot Storage Policy

Do **not** write new snapshots to project root as `.env.production` / `.env.local`.

Use a private runtime directory, proposed:

```text
storage/app/private/backups/env-snapshots/
```

This location is already covered by repository ignore policy via `/storage/app/private/` and is not web-accessible through the public storage symlink.

Snapshot filenames should be server-generated and timestamped, for example:

```text
env-production-20260812_131500.env
env-local-20260812_131505.env
```

No secret value should appear in filename, log message or UI.

## 8. Permissions

Snapshot directory:

```text
0700
```

Snapshot file:

```text
0600
```

Implementation must verify write success and best-effort enforce permissions after creation.

A failed permission/write operation must not report success.

Do not expose the absolute filesystem path to browser notifications.

## 9. New Environment Snapshot Service

Create a focused service, proposed:

`Modules/System/Services/Env/EnvSnapshotService.php`

Responsibilities:

### Registry

Own fixed snapshot definitions (`production`, `local`).

Expose only display-safe metadata to Livewire if needed.

### Create snapshot

1. validate operation ID against fixed registry;
2. verify the source `.env` exists and is a regular file;
3. acquire an application-level lock, e.g. `system:env-snapshot:create`;
4. ensure private destination directory exists with restrictive mode;
5. generate timestamped filename server-side;
6. copy/write `.env` atomically enough for the small configuration file;
7. verify destination exists and non-zero/expected byte count where practical;
8. chmod file to `0600`;
9. apply retention policy;
10. log safe metadata;
11. return only safe result metadata (operation ID/label/timestamp), never snapshot contents/path.

The service must not accept arbitrary destination or suffix values.

## 10. Retention Policy

Avoid unlimited plaintext secret proliferation.

Initial policy proposed:

```text
Keep latest 5 snapshots per environment type.
```

After a successful new snapshot:

- list only files matching the service-owned filename pattern;
- sort newest-first;
- delete files exceeding the retention count;
- never delete unrelated files in the directory.

Retention cleanup failure should be logged. It should not silently broaden the deletion scope.

If cleanup failure occurs after a valid snapshot was already written, report snapshot success with server-side warning rather than deleting the new valid snapshot solely because cleanup failed.

## 11. Existing `EnvManagerService::exportToEnvironment()`

The old method writes `.env.<suffix>` into project root and has no allowlist.

Preferred implementation direction:

- stop using it from `EnvManager`;
- move snapshot responsibility into `EnvSnapshotService`;
- keep the old method temporarily only if repository-wide reference checking cannot prove it unused;
- if left in place, mark/document it as legacy and do not expose it through Livewire;
- do not delete it in this component-level task unless repository evidence confirms no other consumer.

The canonical `.env` writer remains `EnvManagerService::update()` and is not changed by this snapshot refactor.

## 12. Livewire Responsibility

Keep in Livewire:

- fixed operation selection/button action;
- authorization;
- `canUpdate` UI capability state;
- service delegation;
- safe notification state.

Remove from Livewire:

- `activeTab` (unrelated to snapshot responsibility);
- incomplete `getTabsDefinition()`;
- direct filename/suffix semantics;
- filesystem details.

Proposed action:

```php
public function createSnapshot(string $operation, EnvSnapshotService $service): void
```

The operation remains allowlisted again inside the service.

## 13. Blade / UX

Refactor the current minimal Blade into a compact toolbar/card suitable for the ENV page header.

Requirements:

- title such as `Snapshot cấu hình ENV`;
- clear note that snapshots contain secrets and are stored privately;
- two fixed buttons: Production / Local;
- loading + disabled state on both buttons;
- `wire:confirm` before snapshot creation;
- no filename/path input;
- no snapshot content preview/download in this task;
- read-only notice/disabled state for users without `system.env.update`;
- safe success message including only logical environment label and creation time if useful.

Do not add restore/download/delete capabilities in this refactor. Those are separate privileged workflows requiring their own plan.

## 14. ENV Page Integration

After hardening, uncomment/replace the currently commented mount in:

`Modules/System/resources/views/pages/settings/env.blade.php`

with the canonical Livewire mount for `system.settings.env-manager`.

Mount it in the header/action area so snapshot actions apply to the whole ENV page rather than any one tab.

The page remains protected by `system.env.view`; the component independently enforces `system.env.update` for writes.

## 15. Concurrency

Use a short application-level lock around snapshot creation:

```text
system:env-snapshot:create
```

This prevents two admins from racing on filename/retention operations.

Timestamp generation should still include enough precision/collision protection so a retry cannot overwrite an existing snapshot.

Never overwrite an existing snapshot filename silently.

## 16. Logging / Secret Handling

Safe log metadata may include:

- actor/admin ID;
- operation `env.snapshot.create`;
- snapshot type (`production` / `local`);
- success/failure;
- source byte length;
- created timestamp;
- retention deletion count;
- exception class on failure.

Never log:

- `.env` contents;
- secret values;
- snapshot contents;
- absolute destination path if avoidable;
- request/session payload;
- raw exception message in browser notifications.

## 17. Error Handling

Browser receives generic messages for:

- source `.env` missing;
- storage directory unavailable;
- copy/write failure;
- operation already in progress;
- unsupported operation.

Technical exception details go to server logs only.

No raw `$e->getMessage()` should be dispatched to Livewire/browser.

## 18. Git / Exposure Safety

Current `.gitignore` explicitly ignores `.env.production` but not `.env.local`.

Moving all new snapshots to `storage/app/private/backups/env-snapshots/` removes reliance on individual root `.env.*` ignore entries and prevents newly generated Local snapshots from appearing as root untracked secret files.

Do not add generated snapshots to Git.

Existing `.env.production` / `.env.local` files, if present on a deployed machine, are operational cleanup concerns and should not be automatically deleted by this component refactor.

## 19. Tests

Create focused test file:

`tests/Feature/System/SystemEnvSnapshotTest.php`

Coverage:

1. ENV route requires `system.env.view`;
2. canonical `Quản lý ENV` menu remains `/admin/system/settings/env` + `system.env.view`;
3. EnvManager uses `AuthorizesSystemActions`;
4. snapshot action enforces `system.env.update`;
5. only `production` and `local` operation IDs are accepted;
6. traversal/arbitrary operation values are rejected before filesystem access;
7. snapshots are written under private storage, not project root;
8. filename is server-generated/timestamped;
9. snapshot file mode policy is `0600` where platform supports it;
10. destination directory mode policy is `0700` where platform supports it;
11. source `.env` missing returns controlled failure;
12. snapshot creation uses application lock;
13. an existing generated filename is not silently overwritten;
14. retention keeps at most five matching snapshots per type;
15. retention never deletes unrelated files;
16. Livewire no longer has `getTabsDefinition()` / unrelated `activeTab` state;
17. browser messages do not expose raw exceptions or snapshot contents/path;
18. ENV page actively mounts `system.settings.env-manager`;
19. Blade has confirmation/loading/read-only UX and no free-form filename/path input;
20. no new route/menu/permission is introduced.

Filesystem tests must use temporary controlled paths/service seams and must not copy the developer's real `.env` into test artifacts outside the temporary test area.

## 20. Files Expected to Change

Application:

- `Modules/System/Livewire/Settings/EnvManager.php`
- `Modules/System/resources/views/livewire/settings/env-manager.blade.php`
- `Modules/System/resources/views/pages/settings/env.blade.php`
- `Modules/System/Services/Env/EnvSnapshotService.php` (new)

Potentially documented but not necessarily changed:

- `Modules/System/Services/Env/EnvManagerService.php` (`exportToEnvironment()` becomes legacy/unreferenced by this component)
- `Modules/Admin/data/menus.json` (already canonical; verify only)

Tests:

- `tests/Feature/System/SystemEnvSnapshotTest.php` (new)

Documentation:

- `docs/modules/System/livewire/settings-env-manager/ANALYSIS.md`
- `docs/modules/System/livewire/settings-env-manager/REFACTOR_PLAN.md`

No database migration is planned.
No new route is planned.
No new Admin Menu entry is planned.
No new permission is planned.
No restore/download/delete feature is planned.

## 21. Existing Installation Admin Menu

No targeted database menu update should be required if the prior DatabaseConfig normalization was applied.

Expected canonical entry remains:

```text
name: Quản lý ENV
url: /admin/system/settings/env
can: system.env.view
is_active: true
```

Do not reset or reseed unrelated menu rows.

## 22. Acceptance Criteria

- [ ] EnvManager is intentionally mounted in the existing ENV page;
- [ ] no duplicate route/Admin Menu item is created;
- [ ] ENV page visibility remains `system.env.view`;
- [ ] snapshot creation enforces `system.env.update`;
- [ ] browser can select only fixed `production`/`local` operations;
- [ ] no arbitrary suffix/path reaches filesystem operations;
- [ ] new snapshots are stored only in private storage, not project root;
- [ ] snapshot directory/file permissions are restrictive;
- [ ] snapshots are timestamped and never silently overwritten;
- [ ] retention prevents unlimited secret copies;
- [ ] cleanup scope is limited to service-owned filename patterns;
- [ ] incomplete tab logic is removed;
- [ ] raw exception/secret/path details are not returned to browser;
- [ ] no restore/download/delete functionality is introduced;
- [ ] focused tests pass;
- [ ] no destructive menu/env/database reset occurs.

## 23. Approval Gate

Per `.codex/tasks/refactor-livewire.md`, implementation must not begin until the user explicitly approves this plan.
