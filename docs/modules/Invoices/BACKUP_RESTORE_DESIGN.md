# Invoices Backup / Restore Design

## Goal

Provide module-scoped disaster recovery for Invoices without restoring unrelated application data.

## Ownership boundary

Snapshot-owned data:
- `invoices`
- `invoice_files` metadata

Operational history such as `invoice_backup_runs` is not restored as business data. Partner master data is never included or mutated by an Invoices restore. Partner candidate data remains Partner-owned and is excluded unless a future explicit cross-module contract is approved.

PDF binaries are not embedded in the database snapshot. They remain protected by the existing Local ↔ Google Drive PDF workflow; snapshot metadata is used to assess recoverability.

## Snapshot contract

Each snapshot contains a versioned manifest, exported module data, record counts, schema fingerprint and SHA-256 checksums. A snapshot is immutable after completion.

Restore is gated by a readiness check. The restore action remains disabled until the selected snapshot has a current readiness result.

Readiness states:
- `READY`: restore can proceed.
- `WARNING`: restore can proceed only after explicit acknowledgement.
- `BLOCKED`: restore is prohibited.

Blocking conditions include corrupt/missing manifest or checksum, unsupported snapshot version, incompatible schema, another restore in progress, insufficient temporary storage, or inability to create the mandatory safety snapshot.

Warnings include newer current records, changed records, unavailable Google Drive PDF recovery, running invoice/PDF queues, and stale readiness results.

## Restore modes

### Safe merge (default)

Insert missing business records, reconcile existing records according to the canonical invoice identity contract, preserve newer unrelated data, and never delete records merely because they are absent from the snapshot.

### Replace (advanced)

Available only to authorized administrators after a second acknowledgement. A safety snapshot is mandatory before mutation.

## Restore sequence

1. Select immutable snapshot.
2. Validate manifest and checksums.
3. Validate snapshot/schema compatibility.
4. Produce impact preview.
5. Resolve `READY`, `WARNING`, or `BLOCKED`.
6. Create safety snapshot of current Invoices state.
7. Restore inside controlled database transactions/chunks.
8. Verify counts, invoice identity, invoice-file references and Partner boundary.
9. Record restore result and verification evidence.
10. Offer rollback to the safety snapshot if post-restore verification fails.

## Dashboard operations center

Dashboard hierarchy:
1. KPI: total, sold, purchase, PDF available, PDF missing.
2. Quick actions: invoice list, GDT sync, partner report, PDF ↔ Drive, Backup / Restore.
3. Operational health: GDT, queue, Google Drive, PDF protection.
4. Backup & Recovery: latest snapshot, next schedule, restore readiness and actions.
5. Actionable warnings only.
6. Recent operational activity.

Backup health and restore readiness are independent signals. A successful backup does not imply that restore is currently safe.

## Safety rules

- No Invoices restore may mutate Partner master data.
- No restore starts without a successful safety snapshot.
- No restore uses database IDs alone as the business identity of an invoice.
- No destructive replace operation is the default.
- Readiness must be recalculated after schema changes or when its validity window expires.
