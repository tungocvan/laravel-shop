# System Database Recovery Workspace Closeout

## Status

- Date: 2026-09-17
- Branch: `fix/system-module-schema-doctor`
- Base: `main`
- Route: `/admin/system/database`
- Acceptance: **TEST PASS + UI PASS**

## Scope Delivered

This phase hardens the System Database Manager around three recovery boundaries: Module Snapshot schema compatibility, Full Database Backup management, and Google Drive readiness.

### Module Snapshot / Schema Doctor

- Replaces generic schema incompatibility feedback with explicit Schema Doctor diagnosis.
- Canonicalizes equivalent schema metadata so semantically identical index / foreign-key definitions do not create false incompatibility.
- Produces repair planning information without blindly applying DDL.
- Keeps Restore Module blocked until the current schema is actually compatible.
- Preserves safety snapshot / rollback boundaries for recovery operations.

### Full Database Backup Workspace

- Provides a unified Backup Catalog across Local and Google Drive.
- Distinguishes `LOCAL ONLY`, `DRIVE ONLY`, and `LOCAL + DRIVE` states.
- Supports create, upload, download-to-local, restore and explicit deletion operations according to existing permissions.
- Reuses the existing safe full-database restore service rather than introducing a second restore engine.

### Google Drive Readiness

- Distinguishes stored OAuth connection state from usable Drive API readiness.
- Diagnoses insufficient OAuth scope / permission failures and presents reauthorization guidance.
- Gates Drive upload operations on API readiness.
- Retains least-privilege Drive authorization instead of broadening scope solely to bypass a permission error.

### Database Workspace UI Boundary

- `/admin/system/database` without a selected Module is the Full Database workspace.
- Selecting a Module switches to Module-scoped recovery.
- Module mode hides whole-database `Full Backup`, `Restore Database`, the duplicate Module selector, and the Full Database catalog.
- Module mode provides `Quay về Database` back to `/admin/system/database`.
- Legacy `/admin/system/database/backup-restore` remains redirected to the unified workspace.

## Safety Decisions

- Never bypass Module Snapshot schema compatibility checks.
- Never auto-apply destructive schema changes merely to match a historical fingerprint.
- Full database restore retains safety-backup and rollback behavior.
- Local and Drive copies remain independently deletable; no destructive filesystem-style synchronization is introduced.
- Drive API operations require readiness, not only presence of a refresh token.
- Runtime lock artifacts under `storage/framework/` are not source-controlled.

## Verification

The operator confirmed approved focused tests **PASS**, System Module regression **PASS**, and manual UI acceptance **PASS** on 2026-09-17.

The final UI acceptance specifically confirms that Module mode no longer exposes Full Backup / Restore Database or the Full Database catalog and that the return navigation resolves to `/admin/system/database`.

A full-project regression is outside the approved scope.

## Merge Gate

**READY FOR PR REVIEW INTO `main`.**

Merge remains gated on normal repository PR checks and explicit operator approval.