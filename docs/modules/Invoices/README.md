# Invoices Module

## Module Overview

`Invoices` is the electronic-invoice domain module for GDT integration, sold/purchase invoice synchronization, Excel import/export, local PDF management, partner reporting and module-scoped recovery.

Current status: the main invoice ingestion/PDF/Drive/reporting scope is merged; `feat/invoices-dashboard-backup-restore` adds the Operations Dashboard and module Backup/Restore workflow pending final PR closeout.

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

Do not use legacy `module.json` as the architectural source of truth.

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

Sensitive Livewire actions enforce server-side permission checks. UI actions are also hidden/disabled when the admin lacks the corresponding capability.

## Invoice Identity / Import Safety

Current application identity remains:

```text
lookup_code + invoice_number + issued_date + tax_code
```

Import duplicate detection is date-aware and does not overwrite an existing invoice in `skip_duplicate` mode.

A database unique constraint remains deferred until identity is validated against production data.

## Invoice List / Import / Export

Canonical import/export service:

```text
Modules/Invoices/Services/InvoiceImportExportService.php
```

It extends:

```text
Modules/Shared/Services/ImportExport/BaseImportExportService.php
```

The list workspace supports bounded pagination, searchable partner/MST filters, year/month/date filters, safe sort whitelists, page selection and explicit select-all-filtered behavior. Import/export supports XLSX/CSV and selected/all-filtered export without unbounded `All` pagination.

## PDF Storage and Metadata

PDFs are stored under:

```text
storage/app/invoices/pdf/{YYYY}/{MM}/{sold|purchase}/
```

Legacy files under `storage/app/hoadon_temp` remain readable for compatibility.

Metadata table:

```text
invoice_files
```

Tracked fields include provider, status, path, size, last error and downloaded time. PDF status is represented through values such as:

```text
available
missing
error
```

The invoice record is never deleted when its PDF is deleted.

## Google Drive PDF Protection

PDF binaries are protected through the existing Local ↔ Google Drive workflow under the configured `Laravel-Backup` root. Module DB snapshots intentionally do not embed PDF binaries.

The invoice list exposes the PDF ↔ Drive workspace for upload/restore comparison by invoice period. Same-name files are not blindly overwritten when sizes differ.

## Partner Reporting

Canonical route:

```text
/admin/invoices/reports/partners
```

The report supports filters, searchable partner/MST selectors, safe sorting, selected/all-filtered export and sold/purchase VAT-inclusive totals. The report does not present accounting differences as profit.

Partner master is not owned by Invoices. Invoice-derived counterparty candidates are handed to the Partner-owned staging/review boundary; Invoices does not directly mutate Partner master during ingestion.

## Operations Dashboard

Canonical route:

```text
/admin/invoices/dashboard
admin.invoices.dashboard
```

Architecture:

```text
InvoicesDashboardController
    -> InvoiceDashboardService
        -> InvoiceDashboardData
            -> Blade
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

PDF KPI rules:

```text
PDF đã lưu      = invoice_files.status = available
PDF lỗi         = invoice_files.status = error
PDF chưa hoàn tất = total invoices - available - error, plus explicit errors in the detail copy
```

Operational Health exposes only actionable or bounded states:

- GDT account/session state;
- Google Drive stored connection metadata;
- queue guidance at the relevant workspace instead of pretending to expose a global queue truth;
- PDF stored/missing/error counts.

Dashboard rendering does not call the external Google Drive API. It reads the existing stored `GoogleDriveConnectionService::status()` metadata and links to the System Google Drive connect flow when disconnected.

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

Module snapshots contain:

```text
invoices
invoice_files metadata
manifest.json
SHA-256 payload checksums
```

They exclude:

```text
Partner master
Partner candidate state
PDF binary files
system/global database state
```

The workspace supports:

```text
Backup ngay
Lịch sử snapshot
Kiểm tra khả năng khôi phục
READY / WARNING / BLOCKED
Impact Preview
Safety Backup + Merge Restore
Post-Restore Verification
MANUAL snapshot delete
Safety Backup rollback
```

## Restore Contract

Safe Merge is the default restore mode:

- insert snapshot invoices missing from current data;
- preserve current matching invoices rather than overwriting newer records;
- remap and restore missing `invoice_files` metadata;
- never delete current-only invoices;
- never mutate Partner master.

A Safety Backup is mandatory before restore. If the Safety Backup cannot be created, mutation does not start.

`SAFETY-BEFORE-RESTORE` snapshots are protected from normal deletion.

Exact Safety Rollback restores the Invoices-owned `invoices + invoice_files` state represented by the selected Safety snapshot, creates another Safety Backup before rollback, and runs post-rollback verification. Partner master remains outside the rollback boundary.

## GDT Synchronization Safety

GDT synchronization validates pagination completeness. A partial page sequence must not silently produce a successful-looking Excel file.

The sync flow compares received rows with the GDT reported total and treats incomplete pagination as a failure instead of silently exporting partial data.

`TransactionID`/lookup values are resolved by field meaning rather than relying on one fixed array index.

## Configuration

GDT environment keys:

```text
GDT_API_BASE_URL
GDT_API_USERNAME
GDT_API_PASSWORD
GDT_API_VERIFY_SSL
GDT_API_TIMEOUT
GDT_TOKEN_TTL
GDT_TOKEN_CACHE_KEY
```

Optional MeInvoice fallback:

```text
MEINVOICE_API_TOKEN
```

Writable runtime directories include:

```text
storage/app/gdt
storage/app/invoices/pdf
storage/app/invoices/archives
storage/app/hoadon_temp
```

Module snapshots use Laravel's configured local disk under the logical path:

```text
invoices/module-backups/*
```

Ensure the PHP/queue runtime user has write permission to `storage` and `bootstrap/cache`.

## Important Classes

```text
Modules/Invoices/Http/Controllers/InvoicesController.php
Modules/Invoices/Http/Controllers/InvoicesDashboardController.php
Modules/Invoices/Livewire/HoadonList.php
Modules/Invoices/Livewire/ModuleBackupRestore.php
Modules/Invoices/Livewire/PartnerReport.php
Modules/Invoices/Services/GdtInvoiceService.php
Modules/Invoices/Services/GdtPdfService.php
Modules/Invoices/Services/GoogleDriveInvoicePdfSyncService.php
Modules/Invoices/Services/InvoiceDashboardService.php
Modules/Invoices/Services/InvoiceImportExportService.php
Modules/Invoices/Services/InvoiceFileService.php
Modules/Invoices/Services/InvoiceModuleSnapshotService.php
Modules/Invoices/Services/InvoiceRestoreReadinessService.php
Modules/Invoices/Services/InvoiceRestoreImpactService.php
Modules/Invoices/Services/InvoiceModuleRestoreService.php
Modules/Invoices/Services/InvoiceRestoreVerificationService.php
Modules/Invoices/Models/Invoices.php
Modules/Invoices/Models/InvoiceFile.php
```

## Verification Strategy

Use module-scoped regression rather than full-project regression unless a shared/core failure proves wider impact.

Current final gate for the Dashboard/Backup-Restore branch:

```bash
php artisan test \
  tests/Feature/InvoicesDashboardTest.php \
  tests/Feature/InvoicesBackupRestoreWorkspaceTest.php \
  tests/Feature/InvoicesModuleRestoreTest.php \
  tests/Feature/InvoicesSnapshotLifecycleTest.php

php artisan test tests/Feature/Invoices*.php
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
2. Replace any remaining runtime secret mutation with production-grade settings/secrets storage.
3. Add scheduled retention/Drive upload for module DB snapshots only if separately approved.
4. Add advanced Replace restore mode only with a separate destructive-operation contract.
5. Keep Partner candidate recovery outside Invoices until an explicit cross-module recovery contract exists.
6. Add persisted global queue registry only if operational need justifies it.
7. ClientPortal/PWA presentation remains a separate scope.

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
