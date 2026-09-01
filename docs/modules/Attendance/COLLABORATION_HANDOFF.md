# Attendance — Collaboration Handoff

## Current status

- `docs/modules/Attendance/REQUIREMENTS.md`: approved and merged through PR #127.
- `docs/modules/Attendance/CREATE_PLAN.md`: explicitly approved by the user on 2026-09-01 and merged through PR #128.
- MR-0 documentation gate: complete.
- MR-1 implementation authorization: approved by the user.
- MR-1 branch: `feat/attendance-module-bootstrap`.

## MR-1 — Module skeleton + manifest + bootstrap/runtime contract

Current MR-1 implementation scope:

- create the minimal Attendance module skeleton required by repository bootstrap;
- add `Modules/Attendance/config/module.php`;
- add Release-1 configuration defaults in `Modules/Attendance/config/attendance.php`;
- declare Attendance as a `domain` module;
- keep source/default runtime state disabled;
- declare direct dependency on canonical `Account`;
- declare approved Admin/domain and web/ClientPortal capability names;
- verify module discovery/default state/runtime override/dependency enforcement with focused tests;
- rely exclusively on the root `Modules/ModuleServiceProvider.php` and canonical module catalog/state infrastructure.

Explicitly not in MR-1:

- database migrations or Attendance persistence;
- models/enums/domain services;
- check-in/check-out implementation;
- geofence calculation implementation;
- adjustment/audit implementation;
- Admin routes/UI/Livewire workspaces;
- ClientPortal Attendance adapter;
- Attendance export;
- GPS retention job.

## Bootstrap decisions

- No Attendance-specific service provider in MR-1.
- No `module.json` or nwidart registry.
- No API route file.
- No web route file until an actual runtime surface exists.
- No migration directory/files until MR-2.
- No console command.
- `tables` remains empty in the manifest until Attendance owns schema in MR-2.
- Runtime state is resolved by `ModuleStateRepository` / `ModuleStateResolver`; the manifest is never mutated for runtime toggles.

## Verification gate

Focused verification for MR-1:

```bash
php artisan test tests/Feature/Attendance/AttendanceModuleBootstrapTest.php
php artisan test tests/Feature/System/ModuleCatalogRegistryTest.php tests/Feature/System/ModuleStateResolverTest.php tests/Feature/System/ModuleGraphValidatorTest.php tests/Feature/System/ModuleBootstrapRuntimeStateTest.php
```

Also run Pint on changed PHP files before PR. No full-project regression is required by default for this isolated bootstrap slice.

Expected assertions include:

- Attendance manifest matches approved contract;
- Attendance is discovered as `domain`;
- Attendance is disabled by default;
- runtime state may override the default;
- enabled Attendance requires enabled Account;
- approved configuration defaults are explicit;
- no MR-2+ implementation leaks into this branch.

## Manual acceptance

MR-1 has no Attendance business UI. Manual verification is limited to module/runtime administration if needed; Admin/PWA UI acceptance begins in later MRs.

## Canonical sources

- `docs/modules/Attendance/REQUIREMENTS.md`
- `docs/modules/Attendance/CREATE_PLAN.md`
- `.codex/tasks/create-module.md`
- `.codex/standards/MODULE_STANDARD.md`
- `.codex/standards/ADMIN_UI_STANDARD.md`
- `Modules/ModuleServiceProvider.php`
- `app/Modules/ModuleCatalog.php`
- `app/Modules/ModuleStateRepository.php`
- `app/Modules/ModuleStateResolver.php`
- `app/Modules/ModuleGraphValidator.php`

## Next gate

Do not start MR-2 persistence work until MR-1 tests/verification pass, MR-1 is reviewed and merged, and the next phase is explicitly authorized according to the collaboration workflow.
