# Settings/EnvManager Livewire Refactor Plan

Plan date: 2026-08-12

Status: **Implemented — pending local focused test verification.**

## Implemented scope

The approved refactor has been implemented with these boundaries:

- `EnvManager` now uses `AuthorizesSystemActions`;
- snapshot mutation requires `system.env.update`;
- browser-facing operations are fixed to `production` and `local`;
- new `EnvSnapshotService` owns snapshot filesystem behavior;
- snapshots are written under `storage/app/private/backups/env-snapshots/`, not project root;
- directory/file modes are best-effort `0700` / `0600`;
- creation uses application lock `system:env-snapshot:create`;
- filenames are timestamped and collision-resistant;
- retention keeps latest five matching snapshots per environment type;
- retention does not target unrelated files;
- browser messages do not include snapshot contents, absolute paths or raw exception messages;
- dead `activeTab` / `getTabsDefinition()` logic was removed from the component;
- the hardened component is mounted in the existing ENV page header;
- both buttons have confirmation/loading/disabled/read-only UX;
- no new route, Admin Menu item or permission was introduced;
- existing `Quản lý ENV` remains `/admin/system/settings/env` + `system.env.view`;
- restore/download/delete/listing workflows remain out of scope;
- legacy `EnvManagerService::exportToEnvironment()` is no longer used by this Livewire component but remains for compatibility.

## Files changed

- `Modules/System/Services/Env/EnvSnapshotService.php` (new)
- `Modules/System/Livewire/Settings/EnvManager.php`
- `Modules/System/resources/views/livewire/settings/env-manager.blade.php`
- `Modules/System/resources/views/pages/settings/env.blade.php`
- `tests/Feature/System/SystemEnvSnapshotTest.php` (new)
- `docs/modules/System/livewire/settings-env-manager/ANALYSIS.md`
- this plan

## Local verification

Run after pulling/merging:

```bash
php artisan optimize:clear
php artisan permission:cache-reset
php artisan test tests/Feature/System/SystemEnvSnapshotTest.php
```

Also smoke-test `/admin/system/settings/env` with:

1. an account with `system.env.view` only — toolbar visible/read-only, snapshot actions unavailable;
2. an account with `system.env.update` — Production/Local snapshot actions confirm and complete;
3. verify generated files are under private storage and no new `.env.production` / `.env.local` is generated in project root.

## Acceptance status

- [x] EnvManager intentionally mounted in existing ENV page;
- [x] no duplicate route/Admin Menu item created;
- [x] ENV page visibility remains `system.env.view`;
- [x] snapshot creation enforces `system.env.update`;
- [x] browser can select only fixed Production/Local operation IDs;
- [x] arbitrary operation is rejected again in service before filesystem work;
- [x] snapshots moved to private storage;
- [x] restrictive permission policy implemented;
- [x] timestamped/collision-resistant filenames implemented;
- [x] retention implemented with service-owned pattern scope;
- [x] incomplete tab logic removed;
- [x] raw exception/secret/path details excluded from browser notifications;
- [x] restore/download/delete intentionally not introduced;
- [x] focused test file added;
- [ ] focused test passes in target Laravel runtime (user verification required).
