# Database/ImportDrawer Refactor Plan

Status: **Implemented / Retired 2026-08-12.**

Decision implemented: legacy `ImportDrawer.php` and Blade removed instead of recreating stale `DatabaseService::import()`. Canonical table import remains in `TableList`; full backup/restore remains in `BackupManager` + `DatabaseService` via `importBackupFile()`, `importTableFromFile()` and `restoreFromFile()`. Regression contract test added. No migration, route, permission or Admin Menu item added.
