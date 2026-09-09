# Invoices Collaboration Handoff

## Current Status — GDT Smart Sync PWA

- Module: `Invoices`
- Active branch: `feat/clientportal-invoices-gdt-smart-sync`.
- Current scope: **secure GDT Smart Sync + canonical database persistence + Excel export**.
- User-reported focused tests: **14 passed (115 assertions)**.
- User-reported PWA/UI/runtime acceptance: **PASS**.
- Latest style-only correction: `24129a52ef532e916ed282811ae7a8553c18a1c7`.
- Final post-fix Pint confirmation: **PENDING**.
- Merge authorization: **NOT YET GIVEN**.

## Smart Sync Data Contract

Canonical Invoices ownership remains in `Modules/Invoices`.

The Smart Sync processing order for live GDT data is now:

```text
GDT API
  -> map canonical invoice payload
  -> transactional database persistence
  -> Excel export
  -> queue/PWA status log
```

Database persistence uses the canonical `invoices` model/schema and occurs before the Excel artifact is considered complete.

Identity strategy is intentionally idempotent without introducing a new schema migration in this delivery:

- primary identity when available: `invoice_type + lookup_code`;
- fallback identity: `invoice_type + symbol + invoice_number + tax_code + issued_date`;
- if a safe identity cannot be established, persistence fails instead of inserting an ambiguous duplicate;
- existing matching records are filled and classified as updated only when values changed;
- unchanged matches are counted but not rewritten;
- new identities are created;
- the entire persistence batch runs in a database transaction.

Runtime log reports:

```text
[DB] Đồng bộ hoàn tất: tạo mới X · cập nhật Y · không đổi Z.
```

## Smart Date Contract

PWA readiness derives from the canonical database fields:

```text
issued_date
invoice_type = purchase | sold
```

For each direction, the latest current-year invoice date becomes the suggested start date. The system deliberately does **not** add one day, allowing a later synchronization to recheck the latest date and capture invoices that appeared later on the same day.

If no current-year invoice exists for a direction, the suggested start is January 1 of the current year. Suggested end is today.

## GDT Security Contract

- GDT username/password/token remain server-side only.
- CAPTCHA challenge key (`ckey`) remains in server session.
- PWA submits only the human-entered CAPTCHA value.
- No access token or credential is exposed through Blade, JavaScript, localStorage, IndexedDB or PWA cache.
- Queue dispatch is blocked when the server-side GDT token is unavailable.

## Local / Drive / GDT Boundary

Database remains authoritative for Smart Date readiness. Local or Google Drive files must not determine the next synchronization start date.

The established queue/file fallback remains:

```text
Database readiness -> Local artifact check -> Google Drive artifact check -> GDT
```

Google Drive configuration, backup/restore and destructive disaster-recovery operations remain Admin/Invoices-only and are not moved into ClientPortal.

## Validation Evidence

Recorded focused automated gate:

```text
Tests: 14 passed (115 assertions)
Duration: 2.52s
```

Manual PWA/UI/runtime acceptance reported by user: **PASS**.

The last Pint check exposed only one formatting issue in `GdtInvoiceService.php` (`unary_operator_spaces`, `not_operator_with_successor_space`, `blank_line_before_statement`). Those style-only issues were corrected in commit `24129a52ef532e916ed282811ae7a8553c18a1c7`; a post-fix Pint confirmation remains the final CLI closeout gate.

## Previous GDT Queue Safety Delivery

Operations Dashboard + Backup/Restore + Google Drive disaster recovery was merged through PR #172. The previous GDT queue safety follow-up established:

- queue selected by default in Admin;
- GDT token guard before job dispatch;
- reconnect action when token is missing/expired;
- incremental database-import UX for selected files;
- no direct Partner master mutation from invoice import.

Those boundaries remain compatible with the Smart Sync delivery.

## Compatibility / Boundaries

This delivery does not:

- rename canonical `/admin/invoices/*` routes;
- change invoice permissions;
- move invoice model/schema ownership to ClientPortal;
- change Partner master ownership;
- expose GDT secrets to the browser;
- alter Backup/Restore disaster-recovery contracts;
- require a destructive database operation;
- introduce a schema migration solely for Smart Sync identity.

## Final Closeout Gate

Before merge:

1. confirm Pint PASS on the changed PHP scope after commit `24129a52ef532e916ed282811ae7a8553c18a1c7`;
2. preserve the recorded focused test + UI/runtime PASS evidence;
3. compare branch against current `main` and resolve base drift if needed;
4. create/review the PR against `main`;
5. merge only after explicit user authorization under `docs/GITHUB_COLLABORATION_WORKFLOW.md`.
