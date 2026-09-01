# Attendance — Collaboration Handoff

## Current status

- `docs/modules/Attendance/REQUIREMENTS.md`: approved and merged through PR #127.
- `docs/modules/Attendance/CREATE_PLAN.md`: explicitly approved by the user on 2026-09-01 and merged through PR #128.
- MR-0 documentation gate: complete.
- MR-1 implementation authorization: approved by the user.
- MR-1 branch: `feat/attendance-module-bootstrap`.
- MR-1 implementation and focused verification: complete; ready for PR review.

## MR-1 — Module skeleton + manifest + bootstrap/runtime contract

Implemented MR-1 scope:

- created the minimal Attendance module skeleton required by repository bootstrap;
- added `Modules/Attendance/config/module.php`;
- added Release-1 configuration defaults in `Modules/Attendance/config/attendance.php`;
- declared Attendance as a `domain` module;
- kept source/default runtime state disabled;
- declared direct dependency on canonical `Account`;
- declared approved Admin/domain and web/ClientPortal capability names;
- verified module discovery/default state/runtime override/dependency enforcement with focused tests;
- relied exclusively on the root `Modules/ModuleServiceProvider.php` and canonical module catalog/state infrastructure.

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

## Verification results

Formatting / repository checks:

- Pint on changed Attendance PHP files: PASS; no resulting working-tree changes.
- `git diff --check`: PASS.
- working tree after verification: clean.

Attendance focused test:

```text
php artisan test tests/Feature/Attendance/AttendanceModuleBootstrapTest.php
Tests: 5 passed (34 assertions)
Duration: 0.50s
```

Directly impacted System/module-runtime tests:

```text
php artisan test \
  tests/Feature/System/ModuleCatalogRegistryTest.php \
  tests/Feature/System/ModuleStateResolverTest.php \
  tests/Feature/System/ModuleGraphValidatorTest.php \
  tests/Feature/System/ModuleBootstrapRuntimeStateTest.php
Tests: 13 passed (52 assertions)
Duration: 0.84s
```

No full-project regression was run because MR-1 is an isolated bootstrap/runtime slice and does not change shared runtime implementation.

Verified contract:

- Attendance manifest matches approved contract;
- Attendance is discovered as `domain`;
- Attendance is disabled by default;
- runtime state may override the default;
- enabled Attendance requires enabled Account;
- approved configuration defaults are explicit;
- no MR-2+ implementation is present in the branch.

## Manual acceptance

MR-1 has no Attendance business UI. Admin/PWA UI acceptance begins in later MRs.

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

MR-1 is ready for pull-request review and merge.

Do not start MR-2 persistence work until MR-1 is merged and the next phase is explicitly authorized according to the collaboration workflow.
