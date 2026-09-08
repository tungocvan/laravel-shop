# Backup / Restore Foundation Checkpoint

Branch: `feat/invoices-dashboard-backup-restore`

Accepted scope:
- Operations-center dashboard hierarchy: KPI, Quick Actions, Operational Health, Backup & Recovery, actionable warnings, recent activity.
- Module-scoped snapshot ownership only; Partner master is never restored by Invoices.
- Restore readiness is mandatory and resolves to `READY`, `WARNING`, or `BLOCKED`.
- Safe Merge is default; Replace is advanced.
- A safety snapshot is mandatory before any restore mutation.
- Post-restore verification and rollback are part of the restore contract.

Foundation implemented in this checkpoint:
- `docs/modules/Invoices/BACKUP_RESTORE_DESIGN.md`
- `InvoiceRestoreReadinessService`
- focused restore-readiness tests

No destructive restore mutation is included in this checkpoint.

Validation:

```bash
./vendor/bin/pint Modules/Invoices/Services/InvoiceRestoreReadinessService.php tests/Feature/InvoicesRestoreReadinessTest.php
php artisan test tests/Feature/InvoicesRestoreReadinessTest.php
```

Next batch: immutable snapshot manifest/history, checksum writer/verifier, canonical invoice identity impact preview, safety snapshot + queued restore orchestration, verification/rollback, then Backup/Restore workspace and Dashboard integration.
