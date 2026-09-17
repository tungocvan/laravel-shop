# System Closeout — Runtime Tabs & Module Snapshot Diagnosis

- Date: 2026-09-17
- Module: `System`
- Delivery branch: `fix/system-tabs-runtime-contract`
- Base branch: `main`
- Routes validated: `/admin/system`, `/admin/system/database`
- Status: **IMPLEMENTATION COMPLETE — TEST PASS + UI PASS**

## Runtime tab contract

- Fixed `/admin/system` 500 caused by an orphan runtime tab without a `component` key.
- Core System tab configuration remains the source of truth.
- JSON overrides may override existing core tabs but may not silently introduce incomplete runtime tabs.
- `SystemController` now handles missing/invalid component metadata defensively instead of raising `Undefined array key "component"`.
- Removed the stale `themes` override left behind after Sidebar appearance was removed from System Operations.

## Module Snapshot recovery diagnosis

- Replaced the generic `BLOCKED` operator experience with explicit schema incompatibility diagnosis.
- A downloaded Google Drive snapshot is not treated as invalid merely because it was downloaded; Drive-to-local remains a transport step before local validation/restore.
- Restore remains available only for `COMPATIBLE` snapshots; schema safety is not bypassed.
- UI explains that manifest/module/table ownership/checksum validation succeeded before a schema mismatch is reported.
- UI provides remediation guidance to compare branch/commit, migration state, and actual database schema before retrying restore.
- Legacy snapshots only contain an aggregate `schema_fingerprint`; therefore they can prove schema mismatch but cannot identify the exact changed table/column/index. The UI states this limitation rather than guessing.

## Verification

Operator confirmation on 2026-09-17:

```text
TEST PASS
UI PASS
```

Approved targeted verification included:

```text
php artisan test \
tests/Feature/System/SystemTabsRuntimeContractTest.php \
tests/Feature/System/ModuleSnapshotRecoveryDiagnosisContractTest.php
```

The directly impacted System information-architecture boundary was also included in the approved test checkpoint.

Manual UI verification: **PASS** for `/admin/system` and `/admin/system/database`.

## Safety decisions

| Concern | Decision |
|---|---|
| Runtime tab source of truth | Core `system_tabs.php` |
| JSON tab overrides | Existing core IDs only |
| Missing component metadata | Defensive `is_ready=false`; never crash the whole workspace |
| Drive download | Transport only; does not grant restore eligibility |
| Restore eligibility | `COMPATIBLE` only |
| Schema mismatch | Explain cause and remediation; never generic `BLOCKED` only |
| Legacy snapshot diagnosis | Report aggregate mismatch honestly; do not invent table/column differences |
| Full regression | Outside approved scope; System/directly impacted tests only |

## Merge gate

1. **COMPLETE** — `/admin/system` runtime tab crash fixed.
2. **COMPLETE** — stale `themes` override removed.
3. **COMPLETE** — runtime tab override contract hardened.
4. **COMPLETE** — Module Snapshot incompatibility diagnosis made operator-actionable.
5. **COMPLETE** — restore safety boundary preserved.
6. **COMPLETE** — targeted tests PASS.
7. **COMPLETE** — manual UI PASS.
8. **READY** — PR to `main` after final diff review.
