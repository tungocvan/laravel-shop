# Invoices Module

## Module Overview

`Invoices` is the electronic-invoice domain module for GDT integration, sold/purchase invoice synchronization, Excel import/export, local PDF management, partner reporting and module-scoped recovery.

Current status: the main invoice ingestion/PDF/Drive/reporting scope is merged; `feat/invoices-dashboard-backup-restore` adds the Operations Dashboard and module Backup/Restore workflow with Google Drive disaster-recovery transport.

## Registration

Canonical registration:

```text
Modules/ModuleServiceProvider.php
Modules/Invoices/config/module.php
```

Module type: `domain`.

Owned tables:

```text
invoices
invoice_files
invoice_backup_runs
```

## Main Routes

Admin prefix:

```text
/admin/invoices
```

Important routes:

```text
admin.invoices.index              -> invoices-list (preserved redirect)
admin.invoices.dashboard          -> invoices-list
admin.invoices.create-token       -> invoices-configure
admin.invoices.hoadon             -> invoices-create
admin.invoices.hoadon-list        -> invoices-list
admin.invoices.reports.partners   -> invoices-list
admin.invoices.backup-restore     -> invoices-configure
admin.invoices.download-invoice   -> invoices-download
admin.invoices.download           -> invoices-download
```

Legacy `/invoices/*` aliases are retained for bookmark compatibility.

## Permissions

Declared capabilities:

```text
invoices-list
invoices-create
invoices-export
invoices-download
invoices-configure
```

Sensitive Livewire actions enforce server-side permission checks.

## Invoice Identity / Import Safety

Current application identity remains:

```text
lookup_code + invoice_number + issued_date + tax_code
```

Import duplicate detection is date-aware and does not overwrite an existing invoice in `skip_duplicate` mode. A database unique constraint remains deferred until identity is validated against production data.

## PDF Storage and Google Drive Protection

PDFs are stored under:

```text
storage/app/invoices/pdf/{YYYY}/{MM}/{sold|purchase}/
```

Metadata table:

```text
invoice_files
```

PDF binaries are protected separately through the existing Local ↔ Google Drive workflow. Module DB snapshots intentionally do not embed PDF binaries.

## Partner Reporting Boundary

Partner master is not owned by Invoices. Invoice-derived counterparty candidates are handed to the Partner-owned staging/review boundary. Backup/restore never mutates Partner master.

## Operations Dashboard

Canonical route:

```text
/admin/invoices/dashboard
admin.invoices.dashboard
```

The Dashboard is an operations center with:

```text
Operational KPIs
Quick Actions
Operational Health
Backup & Recovery
Recent Activity
```

Primary KPI wording:

```text
Tổng hóa đơn
Hóa đơn bán ra
Hóa đơn mua vào
PDF đã lưu
PDF chưa hoàn tất
```

Dashboard rendering does not call the external Google Drive API. It reads stored connection metadata and links to the System Google Drive connect flow when disconnected.

## Module Backup & Restore

Canonical route:

```text
/admin/invoices/backup-restore
admin.invoices.backup-restore
```

Permission:

```text
invoices-configure
```

Module snapshots contain only:

```text
manifest.json
database/invoices.json
database/invoice_files.json
```

They exclude:

```text
Partner master
Partner candidate state
PDF binary files
system/global database state
```

Local logical root:

```text
invoices/module-backups/*
```

Google Drive protection root:

```text
Laravel-Backup/Invoices/Module-Backups
```

MANUAL snapshots can be packaged as one ZIP artifact and protected on Google Drive. The archive carries an external SHA-256 integrity value in Drive `appProperties`, while the internal manifest continues to protect the individual payload files.

Canonical new artifact name:

```text
Invoices-Module-<snapshot>.zip
```

The recovery reader also accepts the earlier compatibility form:

```text
.transport-Invoices-Module-<snapshot>.zip
```

Deleting a MANUAL snapshot locally never deletes the Google Drive copy. Remote delete is intentionally not exposed from this workspace.

## Production / New-Server Recovery

Recommended recovery flow:

```text
Deploy source
→ configure environment/database
→ php artisan migrate
→ connect Google Drive
→ open Backup & Restore
→ Tìm backup trên Drive
→ Tải về & kiểm tra
→ verify ZIP SHA-256
→ validate snapshot manifest/checksums
→ Restore Readiness
→ Impact Preview
→ Safety Backup
→ Safe Merge Restore
→ Post-Restore Verification
```

PDF binaries are restored separately through PDF ↔ Drive.

## Restore Contract

Restore states:

```text
READY
WARNING
BLOCKED
```

Safe Merge is the default:

- insert snapshot invoices missing from current data;
- preserve current matching invoices;
- remap and restore missing `invoice_files` metadata;
- never delete current-only invoices;
- never mutate Partner master.

A Safety Backup is mandatory before restore. If it cannot be created, mutation does not start.

`SAFETY-BEFORE-RESTORE` snapshots are protected from normal deletion. Exact rollback creates another Safety Backup before restoring the selected Invoices-owned state.

## Real Google Drive DR Validation

User-validated flow on 2026-09-08:

```text
Backup artifact created on Drive               PASS
Local MANUAL snapshot deleted                  PASS
Drive artifact rediscovered                    PASS
SHA-256 metadata present                       PASS
Tải về & kiểm tra                              PASS
Snapshot restored to Local                     PASS
Restore Readiness                              READY
Impact insert                                  0
Impact existing                                2,471
Impact different                               0
Impact delete                                  0
Partner master                                 0 thay đổi
```

This proves the key module-level disaster-recovery path where the Local snapshot is gone but the Google Drive artifact can still reconstruct a valid restore candidate.

## Important Classes

```text
Modules/Invoices/Http/Controllers/InvoicesController.php
Modules/Invoices/Http/Controllers/InvoicesDashboardController.php
Modules/Invoices/Livewire/HoadonList.php
Modules/Invoices/Livewire/ModuleBackupRestore.php
Modules/Invoices/Livewire/PartnerReport.php
Modules/Invoices/Services/GoogleDriveInvoiceModuleBackupService.php
Modules/Invoices/Services/GoogleDriveInvoicePdfSyncService.php
Modules/Invoices/Services/InvoiceDashboardService.php
Modules/Invoices/Services/InvoiceImportExportService.php
Modules/Invoices/Services/InvoiceModuleSnapshotService.php
Modules/Invoices/Services/InvoiceRestoreReadinessService.php
Modules/Invoices/Services/InvoiceRestoreImpactService.php
Modules/Invoices/Services/InvoiceModuleRestoreService.php
Modules/Invoices/Services/InvoiceRestoreVerificationService.php
```

## Verification Strategy

Use module-scoped regression rather than full-project regression unless a shared/core failure proves wider impact.

Latest recorded final gate:

```text
Focused Dashboard/Backup-Restore tests: 20 passed / 142 assertions
Invoices regression:                   55 passed / 303 assertions
```

Manual smoke routes:

```text
/admin/invoices/dashboard
/admin/invoices/backup-restore
/admin/invoices/hoadon
/admin/invoices/hoadon-list
/admin/invoices/reports/partners
```

## Remaining Deferred Work

1. Validate invoice identity against production records before adding a database unique constraint.
2. Add scheduled retention/automatic module snapshot upload only if separately approved.
3. Add advanced Replace restore mode only with a separate destructive-operation contract.
4. Keep Partner candidate recovery outside Invoices until an explicit cross-module recovery contract exists.
5. Add persisted global queue registry only if operational need justifies it.
6. ClientPortal/PWA presentation remains a separate scope.

## Related Documentation

```text
docs/modules/Invoices/ANALYSIS.md
docs/modules/Invoices/INFORMATION.md
docs/modules/Invoices/REFACTOR_PLAN.md
docs/modules/Invoices/IMPORT_EXPORT_PLAN.md
docs/modules/Invoices/BACKUP_RESTORE_DESIGN.md
docs/modules/Invoices/BACKUP_RESTORE_FOUNDATION.md
docs/modules/Invoices/COLLABORATION_HANDOFF.md
.codex/standards/MODULE_STANDARD.md
.codex/standards/ADMIN_UI_STANDARD.md
```
