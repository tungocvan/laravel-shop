# PR Notes — System Database Recovery Workspace

## Summary

Hardens `/admin/system/database` across Module Snapshot schema recovery, Full Database backup management, and Google Drive readiness while separating Module-scoped controls from whole-database controls.

## Key Changes

- Schema Doctor and canonical schema comparison for actionable Module Snapshot incompatibility diagnosis.
- Safe repair planning without blind destructive DDL or compatibility bypasses.
- Unified Full Database Backup Catalog for Local ↔ Google Drive operations.
- Google Drive API readiness and insufficient OAuth-scope recovery guidance.
- Module / Full Database workspace separation and `Quay về Database` navigation.
- Legacy backup/restore route redirect retained.

## Verification

- Approved focused tests: PASS.
- System Module regression: PASS.
- Manual UI acceptance: PASS.
- Full-project regression intentionally not run under the repository's targeted-test policy.

## Review Notes

`TableList.php` has substantial raw diff statistics because earlier UI compaction/formatting is included in the branch. The operator reconciled local pre-sync stashes using semantic PHP-token comparison before discarding them; no local logic remained outside the branch.

Merge requires normal repository checks and explicit operator approval.