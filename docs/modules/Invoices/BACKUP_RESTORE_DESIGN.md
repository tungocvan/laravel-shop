# Invoices Backup / Restore Design

## Goal

Provide module-scoped disaster recovery for Invoices without restoring unrelated application data, and make module snapshots recoverable after Local/server loss through Google Drive transport.

## Ownership boundary

Snapshot-owned data:
- `invoices`
- `invoice_files` metadata

Operational history such as `invoice_backup_runs` is not restored as business data. Partner master is never included or mutated. Partner candidate state remains outside this recovery contract.

PDF binaries are not embedded in the database snapshot. They remain protected by the existing Local ↔ Google Drive PDF workflow.

## Snapshot contract

Each snapshot contains:

```text
manifest.json
database/invoices.json
database/invoice_files.json
```

The manifest is versioned and carries record counts, schema fingerprint and SHA-256 checksums for the payload files. A completed snapshot is treated as immutable.

Local logical root:

```text
invoices/module-backups/*
```

## Google Drive transport contract

MANUAL snapshots can be packaged into one ZIP artifact and stored under:

```text
Laravel-Backup/Invoices/Module-Backups
```

Canonical artifact name:

```text
Invoices-Module-<snapshot>.zip
```

The ZIP contains only the three allowlisted snapshot files. The ZIP itself is protected by an external SHA-256 value stored in Google Drive `appProperties.sha256`, together with `appProperties.module = Invoices`.

The recovery reader also accepts the earlier compatibility form:

```text
.transport-Invoices-Module-<snapshot>.zip
```

That prefix is normalized when displayed/downloaded. New uploads do not use it.

Drive transport rules:

- create-only by canonical artifact name;
- same-name artifact must have matching checksum;
- Local delete never deletes Drive;
- remote delete is not exposed from this workspace;
- download validates file identity, parent folder, size and SHA-256 before extraction;
- ZIP extraction permits only the snapshot allowlist;
- existing Local snapshot is never overwritten silently.

## Restore readiness

Restore is gated by a readiness check. States:

- `READY`: restore can proceed.
- `WARNING`: restore can proceed after review/acknowledgement.
- `BLOCKED`: restore is prohibited.

Blocking conditions include corrupt/missing manifest or checksum, unsupported snapshot version, incompatible schema, another restore in progress or inability to create the mandatory safety snapshot.

Warnings include newer current records, changed records, unavailable PDF recovery, running invoice/PDF queues and stale readiness results.

## Restore modes

### Safe merge (default)

Insert missing business records, reconcile existing records according to the canonical invoice identity contract, preserve current matching data, and never delete records merely because they are absent from the snapshot.

### Replace (advanced/deferred)

Not part of the current accepted production workflow. Any future destructive replace operation requires a separate authorization/confirmation contract and a mandatory safety snapshot.

## Production / new-server recovery sequence

1. Deploy application source.
2. Configure environment/database.
3. Run migrations.
4. Connect Google Drive.
5. Open `/admin/invoices/backup-restore`.
6. Discover Drive module backups.
7. Download the selected artifact.
8. Verify ZIP SHA-256.
9. Validate the ZIP allowlist and snapshot manifest/payload checksums.
10. Extract into the guarded Local snapshot root.
11. Run Restore Readiness.
12. Produce Impact Preview.
13. Create mandatory Safety Backup.
14. Execute Safe Merge Restore.
15. Verify invoice identity, invoice-file references and Partner boundary.
16. Offer rollback to the Safety Backup when required.
17. Restore PDF binaries separately through PDF ↔ Drive.

## Dashboard operations center

Dashboard hierarchy:
1. KPI: total, sold, purchase, PDF stored and incomplete.
2. Quick actions: invoice list, GDT sync, partner report, PDF ↔ Drive, Backup / Restore.
3. Operational health: GDT, Google Drive stored status, queue guidance and PDF protection.
4. Backup & Recovery.
5. Actionable warnings only.
6. Recent operational activity.

Dashboard rendering does not perform an external Google Drive API call. Remote discovery is explicit from the Backup & Restore workspace.

## Safety rules

- No Invoices restore may mutate Partner master data.
- No restore starts without a successful Safety Backup.
- No restore uses database IDs alone as the business identity of an invoice.
- No destructive restore is the default.
- Readiness must be recalculated after schema changes or when stale.
- Drive artifacts must pass external ZIP checksum verification before becoming Local restore candidates.
- Local snapshot deletion never implies deletion of the Drive recovery copy.

## Acceptance evidence

Real Google Drive smoke on 2026-09-08 demonstrated:

```text
Local MANUAL deleted
→ Drive artifact rediscovered
→ SHA-256 present
→ downloaded and validated
→ Local snapshot reconstructed
→ Restore Readiness READY
→ Impact: insert 0 / existing 2,471 / different 0 / delete 0
→ Partner master: 0 changes
```

This is the accepted module-level disaster-recovery path for Local/server snapshot loss.
