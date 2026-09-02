# System Collaboration Handoff

## Current Status — Architecture Boundaries Refactor

- Module: `System`
- Mode: Refactor Module
- Delivery branch: `refactor/system-architecture-boundaries`
- Closeout branch: `docs/system-architecture-boundaries-closeout`
- Architecture contract: `docs/modules/System/MODULE.md`
- Pull request: #141 — **MERGED**
- Main merge checkpoint: `124f640f1bd5d6efba25da4fa1160e4728f7cc77`
- Status: **COMPLETE — MERGED TO MAIN**
- UI smoke: **PASS** on 2026-09-02

This refactor establishes the System ownership contract, removes Admin menu ownership from System settings, hardens Admin post-login landing behavior, and removes runtime dependence on Website for Admin/root fallback and ClientPortal PWA manifest delivery.

## Delivered Scope

- Added and finalized `docs/modules/System/MODULE.md` as the canonical System architecture contract.
- Removed the `Quản lý Menu` tab and `admin.header.menu-manager` integration from `/admin/system/settings`; Admin remains the canonical menu owner.
- Retained the `Đăng nhập & Điều hướng` settings tab.
- Set the default Admin post-login landing to `admin.dashboard` (`/admin`).
- Keep public root `/` optional: it is selectable only while a named parameterless GET root route is actually registered.
- When the selected landing disappears, becomes invalid, or Website is disabled, Admin login falls back to `/admin`.
- Added application-level root fallback so `/` redirects to `/admin` when no module owns the root route.
- Hardened 404 rendering so disabling Website does not cause `No hint path defined for [Website]`.
- Updated Auth admin-login entry to honor the configured System landing contract.
- Removed ClientPortal's hard dependency on `website.manifest`; ClientPortal now owns `/my-apps/manifest.webmanifest` through `client.apps.manifest`.
- Clarified that `Setting` + `SettingsService` are System's canonical runtime access boundary for `settings`, while physical migration/schema provenance remains unproven and must not be overclaimed.

## Boundary Decisions

| Concern | Decision |
|---|---|
| Global Admin menu | Admin owns it; System no longer embeds menu management |
| Admin login landing | System owns the preference/validation service |
| Default landing | `admin.dashboard` / `/admin` |
| Public root `/` | Optional integration target; not owned by System |
| Website dependency | Optional only; Admin login/root safety must not require Website |
| `/login`, `/admin/login` | Canonical Auth-owned login routes |
| ClientPortal PWA manifest | ClientPortal owns `client.apps.manifest`; no Website route dependency |
| `settings` schema provenance | Runtime access owned by System; physical migration provenance deferred pending proof |

## Verification Completed

Historical System regression before the final corrective landing patches:

```text
System Feature regression    PASS — 184 tests, 1062 assertions
```

This result predates the final Website-off/Auth/ClientPortal corrective changes and is retained only as historical evidence.

Focused corrective integration gate after runtime fixes:

```text
AdminLoginRedirectSettingTest      PASS
AdminLandingBoundaryTest           PASS
SystemSettingFormTest              PASS
AuthGuardSeparationTest            PASS
ClientPortalPwaBoundaryTest        PASS

17 passed, 1 skipped, 102 assertions
```

The single skip is expected when Website is disabled and the optional root `home` route is not registered.

Additional cross-module regression completed before the final formatter/test-only corrections:

```text
AuthGuardSeparationTest + ClientPortalPwaBoundaryTest
PASS — 7 tests, 47 assertions
```

Route inspection completed:

```text
/admin/system/*                    PASS — 12 routes present
/my-apps/*                         PASS — 10 routes present
client.apps.manifest               PASS — /my-apps/manifest.webmanifest
```

Frontend production build completed:

```text
Vite 7.3.6                         PASS — 34 modules transformed, 3.47s
```

Final formatter gate on the corrective slice: **PASS**.

Final System Feature regression after all runtime, formatter and test-contract corrections:

```text
System Feature regression          PASS — 187 passed, 1 skipped, 1072 assertions
Duration                           10.13s
```

Final Git worktree before PR merge: **CLEAN** and branch synchronized with `origin/refactor/system-architecture-boundaries`.

Manual UI smoke with Website OFF: **PASS**.

Verified behavior:

- `/` safely falls back to `/admin` when Website/root is unavailable;
- unauthenticated `/admin` proceeds to `/admin/login`;
- Admin login defaults/falls back to `/admin`;
- `/my-apps` renders without `website.manifest`;
- `/my-apps/manifest.webmanifest` is ClientPortal-owned;
- `/admin/system/settings` exposes `Đăng nhập & Điều hướng`;
- `/admin/system/settings` no longer exposes `Quản lý Menu`;
- no Website view-hint failure is required for the Admin/root fallback path.

A full-project regression remains outside the approved scope.

## Quarantine / Deferred Debt

- `DatabaseService` remains quarantined until method-level caller proof and backup/restore regression support removal.
- `LegacySettingsAuditService` / `LegacySettingsMigrationService` remain quarantined pending historical-data proof.
- Overlapping settings/env service naming remains deferred until caller imports are completely mapped.
- Dependency-topological module boot ordering remains deferred to root module runtime work.
- Distributed locking for concurrent module transitions remains deferred.
- Physical migration ownership/provenance of the `settings` table remains to be proven before schema cleanup.

## PR Gate

1. **COMPLETE** — architecture audit and ownership plan approved.
2. **COMPLETE** — `MODULE.md` architecture contract established.
3. **COMPLETE** — settings tab ownership cleanup implemented.
4. **COMPLETE** — Admin-first login landing and optional root target implemented.
5. **COMPLETE** — Website-off root/404/Auth corrective boundary implemented.
6. **COMPLETE** — ClientPortal manifest ownership corrected.
7. **COMPLETE** — focused corrective tests passed: 17 passed, 1 skipped, 102 assertions.
8. **COMPLETE** — route inspection, frontend build and impacted Auth/ClientPortal regression passed.
9. **COMPLETE** — manual UI smoke passed.
10. **COMPLETE** — final Pint corrective-slice gate passed.
11. **COMPLETE** — final System regression passed: 187 passed, 1 skipped, 1072 assertions.
12. **COMPLETE** — final worktree clean and synchronized.
13. **COMPLETE** — PR #141 merged to `main`; merge checkpoint `124f640f1bd5d6efba25da4fa1160e4728f7cc77`.
14. **COMPLETE** — architecture-boundaries phase closed; remaining items stay explicitly deferred/quarantined for later work.

---

## Previous Closeout — Module Catalog & Runtime Boundaries

- Delivery branch: `refactor/system-module-catalog-runtime-boundaries`
- Closeout branch: `docs/system-module-catalog-runtime-boundaries-closeout`
- Main merge checkpoint: `f2dd9ca6565d12b2931b9aa0a844742e0fec23b4`
- Pull request: #82 — merged

That phase separated filesystem catalog discovery, graph validation and current-request registry projection while preserving the existing `config('modules.registry')` consumer contract. It also retired browser-driven module source archival and kept runtime module state file-backed and atomic.

### Corrective Closeout — Account Migration Recovery

After that phase, enabling `Account` exposed an existing schema/migration-ledger mismatch. Recovery was handled through ownership-verified `module:migration-recover`, restoring exactly five verified migration ledger records without replaying migrations. The Account runtime toggle and permission synchronization subsequently passed. Manual ledger insertion remains prohibited.
