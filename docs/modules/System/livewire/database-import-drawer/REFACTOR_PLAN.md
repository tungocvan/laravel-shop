# Database/ImportDrawer Refactor Plan

Status: Approved for immediate implementation by user on 2026-08-12.

## Decision
Retire the legacy component instead of repairing a duplicate unsafe import path.

## Plan
- remove `ImportDrawer.php` and its Blade;
- do not recreate `DatabaseService::import()`;
- preserve canonical database workflows already owned by `TableList` (table import) and `BackupManager`/`DatabaseService` (full backup/restore);
- verify repository references before deletion;
- add a regression contract test asserting the legacy component/view are absent and the canonical service APIs remain present;
- no new menu, route, permission or migration.

This closes the stale service contract and avoids a second arbitrary SQL execution path.
