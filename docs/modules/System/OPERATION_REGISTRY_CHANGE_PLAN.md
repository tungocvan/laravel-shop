# System Operation Registry Change Plan

Plan date: 2026-08-12

Status: **Awaiting explicit approval before implementation.**

## Goal

Move approved Artisan and shell-script operation registries out of service constants into developer-owned Module config files, add grouping metadata for the Admin UI, and provide safe demo operations that show how future operations should be registered.

This change must preserve the security model introduced by the `ArtisanList` and `ShScript` refactors: the browser selects only an operation ID; commands, script paths and arguments remain server-owned allowlist data deployed with source code.

## Proposed config files

Create:

- `Modules/System/config/artisan_operations.php`
- `Modules/System/config/script_operations.php`

`SystemOperationService` and `SystemScriptOperationService` will load these config files instead of private `OPERATIONS` constants.

No Admin CRUD editor will be added for these files.

## Group metadata

Each operation supports a developer-owned `group` field so the existing Admin pages can render operations by group.

Initial groups:

- `Thông tin`
- `Cache`
- `Queue`
- `Kiểm tra hệ thống`
- `Demo an toàn`

The group value is display metadata only and never becomes a command/path/argument.

## Initial approved Artisan operations

The initial config should include safe/useful examples:

1. `artisan.list`
   - group: `Thông tin`
   - command: `list`
   - confirmation: false

2. `route.list`
   - group: `Thông tin`
   - command: `route:list`
   - confirmation: false

3. `about`
   - group: `Thông tin`
   - command: `about`
   - confirmation: false

4. `cache.optimize-clear`
   - group: `Cache`
   - command: `optimize:clear`
   - confirmation: true

5. `queue.restart`
   - group: `Queue`
   - command: `queue:restart`
   - confirmation: true

Explicitly do NOT include destructive/demo commands such as `migrate:fresh`, `db:wipe`, `db:seed`, `key:generate`, arbitrary `migrate`, arbitrary command text, or user-controlled arguments.

## Initial approved demo scripts

Create the approved root `app/sh` and two repository-owned read-only diagnostic/demo scripts:

### `app/sh/demo-system-info.sh`

Purpose: demonstrate safe script registration without mutating application/server state.

Expected behavior:

- print a clear demo heading;
- print current UTC date/time;
- print PHP CLI version;
- print current working directory basename or application-path context without dumping environment variables/secrets;
- exit 0.

Registry ID: `demo.system-info`
Group: `Demo an toàn`
Confirmation: false
Timeout: 10 seconds
Arguments: none

### `app/sh/demo-disk-usage.sh`

Purpose: demonstrate a bounded read-only server diagnostic.

Expected behavior:

- print a clear demo heading;
- run `df -h` for the filesystem containing the Laravel application path/current working directory;
- do not enumerate arbitrary paths supplied by the browser;
- exit with the diagnostic command status.

Registry ID: `demo.disk-usage`
Group: `Kiểm tra hệ thống`
Confirmation: false
Timeout: 10 seconds
Arguments: none

These scripts must contain no writes, package/service restart, sudo, credential output, `.env` output, network calls, or browser-controlled arguments.

## Service changes

### SystemOperationService

- load operations from `config('system-artisan-operations')` or an equivalent Module-owned registered config key;
- validate registry shape before execution;
- expose only display-safe fields to Livewire: id, group, label, description, confirmation;
- keep command and arguments private to the service;
- reject missing/unknown/invalid operation definitions before `Artisan::call()`;
- preserve structured logging.

### SystemScriptOperationService

- load operations from Module config;
- validate registry shape;
- expose only display-safe fields;
- preserve canonical `app/sh` path enforcement;
- preserve `/bin/bash` argument-array execution, timeout and 32 KB output bound;
- preserve fixed server-owned arguments;
- preserve structured logging.

## UI changes

Update both Admin Livewire views to group operation cards/selectable entries by the registry `group` field.

The UI must not expose editable command, script path or arguments.

`ArtisanList` remains available at `/admin/system/artisan`.
`ShScript` remains available at `/admin/system/scripts`.
Both remain protected by `system.commands.run`.

## Config registration

Use the Module's existing config/service-provider pattern so both registry files are loaded consistently in normal Laravel runtime and tests. Do not read arbitrary PHP files based on request input.

## Tests

Update focused tests to verify:

- services load registry data from Module config;
- group metadata is exposed but command/script internals are not;
- approved Artisan IDs map to fixed commands;
- dangerous commands are absent;
- demo script IDs map only to fixed repository-owned paths;
- demo scripts exist under `app/sh` and contain no mutation/sudo/env-dump/network behavior;
- path traversal and unknown script IDs remain rejected;
- timeout/output bounds remain enforced;
- Admin UI groups operations;
- browser cannot submit command/path/arguments;
- existing route/menu/permission and production containment tests remain valid.

## Documentation

Update:

- `docs/modules/System/livewire/settings-artisan-list/ANALYSIS.md`
- `docs/modules/System/livewire/settings-sh-script/ANALYSIS.md`

Add a short developer section explaining how to register a new operation safely by editing the corresponding config file and, for scripts, committing the reviewed script under `app/sh`.

## Acceptance criteria

- [ ] Artisan registry lives in Module config, not a service constant.
- [ ] Script registry lives in Module config, not a service constant.
- [ ] Admin UI groups operations by `group`.
- [ ] Safe Artisan demos/examples are visible after deploy.
- [ ] Two read-only demo scripts are committed and visible after deploy.
- [ ] No Admin CRUD for commands/scripts exists.
- [ ] No browser-controlled command/path/argument reaches execution.
- [ ] `system.commands.run` remains enforced.
- [ ] ShScript canonical-path, Process timeout and output bounds remain enforced.
- [ ] Dangerous Artisan commands are not registered.
- [ ] Focused tests pass.

## Approval gate

This changes the approved operation surface and adds executable repository-owned demo scripts. Implementation must not begin until the user explicitly approves this plan.
