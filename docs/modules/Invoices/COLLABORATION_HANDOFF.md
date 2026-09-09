# Invoices Collaboration Handoff

## Current Status

- Module: `Invoices`
- Operations Dashboard + Backup/Restore + Google Drive disaster recovery: **MERGED** through PR #172.
- Current follow-up branch: `fix/invoices-gdt-queue-token-guard`.
- Current scope: **GDT queue token guard + default queue execution + database-import confirmation UX**.
- User-reported focused tests: **PASS**.
- User-reported Invoices module regression: **PASS**.
- User-reported `/admin/invoices/hoadon` UI acceptance: **PASS**.
- Merge authorization: **NOT YET GIVEN**.

## GDT Sync Safety Contract

Canonical sync route remains `/admin/invoices/hoadon`.

- `Xử lý qua queue` is selected by default.
- Before `ProcessGdtInvoicesJob` is dispatched, the action checks that the GDT token is available.
- If the token is missing/expired, no queue job is dispatched; the user is redirected to the existing GDT authentication/token page.
- The sync page exposes GDT readiness and a direct action to connect/re-authenticate when unavailable.
- Synchronous execution remains available when the operator explicitly clears the queue checkbox.

The guard intentionally runs before queue dispatch so a known-invalid GDT session is not placed into the worker queue.

## Database Import Contract

`Đồng bộ vào CSDL` remains incremental:

- existing invoice identities are skipped rather than duplicated;
- missing invoices are inserted;
- current invoice records are not deleted;
- Partner master is not directly created or updated;
- import logging continues to report total/imported/skipped/error counts.

Before `importSelectedFile` executes, the UI requires explicit confirmation explaining that the operation adds missing invoices, skips duplicates and does not directly mutate Partner master.

## UI / UX Acceptance

User-reported manual acceptance on 2026-09-09:

```text
/admin/invoices/hoadon                         PASS
Queue selected by default                     PASS
GDT token guard / reconnect flow              PASS
Đồng bộ vào CSDL confirmation modal           PASS
Incremental database-import presentation      PASS
```

Admin presentation continues to follow `.codex/standards/ADMIN_UI_STANDARD.md`.

## Automated Validation Recorded

User reported both requested gates PASS on 2026-09-09:

```text
Focused: tests/Feature/InvoicesGdtQueueTokenGuardTest.php   PASS
Regression: tests/Feature/Invoices*.php                     PASS
```

Full-project regression is not required for this module-scoped follow-up.

## Compatibility / Boundaries

This follow-up does not:

- rename canonical `/admin/invoices/*` routes;
- change invoice permissions;
- change invoice business identity semantics;
- change Partner master ownership;
- alter module Backup/Restore or Google Drive disaster-recovery snapshot contracts;
- force Google Drive connectivity for GDT-only synchronization;
- remove synchronous GDT synchronization.

## Final Closeout Gate

Before merge:

1. compare the branch against current `main` and resolve any base drift if present;
2. create/review the PR against `main`;
3. preserve the recorded CLI + UI PASS evidence;
4. merge only after explicit user authorization under `docs/GITHUB_COLLABORATION_WORKFLOW.md`.
