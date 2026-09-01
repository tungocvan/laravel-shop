# Attendance — Collaboration Handoff

## Current status

- `docs/modules/Attendance/REQUIREMENTS.md`: approved and merged through PR #127.
- `docs/modules/Attendance/CREATE_PLAN.md`: explicitly approved by the user on 2026-09-01.
- MR-0 scope is documentation only.
- Attendance application code has not started.
- No Attendance migration, route, model, service, Livewire component, ClientPortal adapter, or runtime-state mutation has been created.

## Approved next phase

MR-1 — Module skeleton + manifest + bootstrap/runtime contract.

Approved MR-1 scope from `CREATE_PLAN.md`:

- create the Attendance folder skeleton required for bootstrap;
- create `Modules/Attendance/config/module.php` and Attendance config required by the approved plan;
- verify discovery through `Modules/ModuleServiceProvider.php`;
- add bootstrap/runtime-state/default-state/dependency tests;
- keep `default_enabled = false`;
- declare canonical dependency on `Account`;
- no database migrations or Attendance domain persistence yet;
- no check-in/check-out business implementation yet;
- no Admin business UI yet;
- no ClientPortal Attendance adapter yet.

## Branch gate

MR-1 must start from current `main` only after this approved `CREATE_PLAN.md` is merged into `main`.

Do not reuse `docs/attendance-create-plan` as the implementation branch.

Suggested implementation branch after MR-0 merge:

`feat/attendance-module-bootstrap`

## Verification direction for MR-1

Focused verification should cover:

- Attendance module discovery;
- manifest type = `domain`;
- manifest default disabled state;
- dependency = `Account`;
- runtime override ON/OFF behavior;
- effective dependency behavior;
- runtime toggle does not mutate tracked Attendance manifest;
- no accidental Attendance runtime surface while effectively disabled.

Do not run unrelated full-project regression by default; use Attendance-focused and directly impacted System/module-runtime tests according to `docs/GITHUB_COLLABORATION_WORKFLOW.md`.

## Canonical sources

- `docs/modules/Attendance/REQUIREMENTS.md`
- `docs/modules/Attendance/CREATE_PLAN.md`
- `.codex/tasks/create-module.md`
- `.codex/standards/MODULE_STANDARD.md`
- `.codex/standards/ADMIN_UI_STANDARD.md`
- `Modules/ModuleServiceProvider.php`
- `app/Modules/ModuleStateRepository.php`
- `app/Modules/ModuleStateResolver.php`

## Next action

Merge MR-0 documentation into `main`, then create MR-1 implementation branch from refreshed `main` and implement only the approved bootstrap/runtime slice.
