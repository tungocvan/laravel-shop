# ClientPortal Module — Collaboration Handoff

- Last updated: 2026-09-01
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Completed MR: **MR-8 — PWA Header Account Menu**
- MR-8 pull request: **#68 — MERGED / CLOSED**
- MR-8 merge commit: `90290f492fde65fdac9b179705285273c69cd317`
- MR-8 status: **MERGED / CLOSED**
- Completed corrective: **Canonical web logout / route cache — PR #86 MERGED / CLOSED**
- Corrective merge commit: `0439b7675e6af8b9bb49046c8d941e43ff135ac0`
- Active delivery: **ClientPortal architecture boundaries refactor**
- Active branch: `refactor/clientportal-architecture-boundaries`
- Current status: **VERIFIED — PR READY**

## Active refactor — ClientPortal architecture boundaries

The approved refactor consolidates the prior architecture-contract, portal-boundary and Muasamcong adapter ownership phases into one bounded delivery to reduce repeated pull/test cycles.

Canonical ownership established in this branch:

- `docs/modules/ClientPortal/MODULE.md` is now the architecture contract for the module;
- ClientPortal remains a `support` module with `Auth` as its only direct module dependency;
- Request and Muasamcong are adapter/source integrations rather than direct module dependencies;
- ClientPortal core owns launcher/PWA shell, client permission/presentation infrastructure and adapter discovery;
- Auth continues to own authentication/session/logout implementation;
- Muasamcong-specific client state now has canonical models under `Modules/ClientPortal/Applications/Muasamcong/Models`;
- legacy root `Modules/ClientPortal/Models/{PriceListExport,PublicShare,SyncRequest}` classes remain compatibility aliases only;
- existing `client_portal_*` tables and migrations remain unchanged, so this refactor introduces no destructive schema/data migration.

Runtime callers moved to canonical adapter models:

```text
MuasamcongApplicationController -> Applications/Muasamcong/Models/SyncRequest
SyncPricingResultsJob           -> Applications/Muasamcong/Models/SyncRequest
MuasamcongPriceListController   -> Applications/Muasamcong/Models/PriceListExport
MuasamcongShareManagementController -> Applications/Muasamcong/Models/PublicShare
PublicDrugShareController       -> Applications/Muasamcong/Models/PublicShare
```

Queue compatibility boundary:

- root jobs `GeneratePriceListExport`, `GeneratePriceListPdf`, and `SendPriceListExportEmail` remain in their existing class names for this delivery;
- they are Muasamcong-specific architecture debt, but moving/removing their serialized job class names may break already queued payloads;
- therefore they are classified `QUARANTINE / DEFER`, not safe-removal candidates in this branch.

Safe-removal status:

- no table or migration removal;
- no root model deletion in this delivery;
- no root queued-job deletion or rename;
- compatibility aliases may only be removed in a future caller/queue-proof cleanup.

Architecture regression coverage is provided by `tests/Feature/ClientApps/ClientPortalArchitectureContractTest.php`, including the direct dependency contract, canonical model/table mapping, legacy compatibility aliases, and runtime adapter source checks preventing new `Modules\ClientPortal\Models\*` imports in the migrated Muasamcong paths.

Final verification after the runtime-caller batch and synchronization with current `main`:

```text
Changed-PHP Pint gate: PASS
ClientPortalArchitectureContractTest: PASS
ClientApps regression: PASS
Manual UI smoke: PASS
Working tree: clean
Branch synchronization: current main merged; no behind commits at PR-preparation checkpoint
```

During final UI verification, the Wishlist icon exposed that this refactor branch predated the Website hotfix which changed the service reference from the removed `Modules\Website\Services\WishlistService` to canonical `Modules\Product\Services\WishlistService`. The refactor branch was synchronized with current `main` (which already contains PR #123), rather than duplicating that Website/Product ownership fix inside ClientPortal. UI verification passed after synchronization.

No additional refactor scope is authorized for this delivery after the final PASS gates.

## Stable architecture

ClientPortal remains an authenticated Client/WebApp platform that can host multiple applications without placing Module-specific business logic in Portal core.

Core rule:

> Không được thêm logic đặc thù Module vào ClientPortal core.

Auth owns shared authentication/session/logout behavior; ClientPortal owns PWA presentation and consumes the canonical Auth contract.

## Roadmap checkpoint

```text
MR-1 — Portal Architecture Foundation: MERGED / CLOSED
MR-2 — Adaptive Navigation: MERGED / CLOSED
MR-3 — Dynamic Portal Home: MERGED / CLOSED
MR-4 — Muasamcong reference migration: MERGED / CLOSED
MR-5 — PWA External File Download & Return UX: MERGED / CLOSED — PR #64
MR-6 — PWA Install UX: MERGED / CLOSED — PR #65
MR-7 — PWA Account Registration & Google Authentication: MERGED / CLOSED — PR #67
MR-8 — PWA Header Account Menu: MERGED / CLOSED — PR #68
Corrective — Canonical web logout / route cache: MERGED / CLOSED — PR #86
Active — ClientPortal architecture boundaries refactor: VERIFIED / PR READY
```

## MR-7 authentication contract

- ordinary local registration uses email OTP activation;
- Google authentication uses verified provider email and safe linking rules;
- ClientPortal/PWA uses the shared `App\Models\User` and `web` guard;
- Auth owns session regeneration, CSRF, authorization and logout;
- PWA Google flow does not persist provider access/refresh tokens;
- successful PWA authentication returns to `/my-apps`.

Production Google credentials/callback enablement remains an operational concern separate from source acceptance.

## MR-8 account-menu contract

- one shared ClientPortal account menu for launcher and application shell;
- read-only `/my-apps/account` information;
- bounded `/my-apps/settings` using Auth-owned Google linking;
- canonical CSRF-protected Auth logout;
- no dependency on Admin-only Account presentation;
- ClientPortal owns menu/account/settings presentation while Auth owns authentication/logout behavior.

MR-8 acceptance:

```text
Tests: 130 passed (834 assertions)
Duration: 8.52s
Manual desktop/tablet/mobile/standalone UI: PASS
PR: #68 — MERGED / CLOSED
Merge commit: 90290f492fde65fdac9b179705285273c69cd317
```

## Corrective — Canonical Web Logout / Route Cache

Production optimization after MR-8 exposed a pre-existing duplicate global route name `logout`:

```text
Modules/Auth:    POST /logout          -> name logout
Modules/Website: POST /website/logout  -> name logout
```

The corrective keeps Auth as the single owner of shared web logout and removes the competing Website endpoint.

Canonical contract:

```text
POST /logout
name: logout
owner: Modules/Auth
handler: Modules\Auth\Http\Controllers\AuthController::clientLogout
legacy /website/logout: removed
admin.logout: unchanged
```

Regression protection guarantees:

- exactly one route is named `logout`;
- canonical logout is `POST /logout`;
- canonical action is Auth `clientLogout`;
- legacy `/website/logout` is absent;
- admin logout remains independently owned by `admin.logout`.

Acceptance evidence:

```text
php artisan route:cache
PASS — Routes cached successfully.

ClientApps impacted regression
111 passed (754 assertions)
Duration: 8.52s

AuthGuardSeparationTest after corrective contract
6 passed (35 assertions)
Duration: 0.54s
```

Full-project regression is not required for this bounded Auth/Website/ClientPortal corrective. No migration, schema, manifest, service-worker or environment-variable change was introduced.

Merge checkpoint:

```text
PR: #86
PR state: CLOSED
Merged: true
Base: main
Source branch: fix/auth-canonical-web-logout-route-cache
Source head: 1be9453b112efe65290da50aa61cebad36e0f7bb
Merge commit: 0439b7675e6af8b9bb49046c8d941e43ff135ac0
Corrective: MERGED / CLOSED
```

## Production operational note

For the `tnv` production stack, `.env` changes require the platform optimize/reload operation so long-lived PHP/web processes receive the new environment. The established operational path is `platform-v2 deploy optimize tnv` / `OPTIMIZE / RELOAD .ENV: tnv`.

The canonical logout corrective restores successful route caching during that optimization path.

## Next-step boundary

The ClientPortal architecture-boundaries refactor is verified and ready for pull-request review. Do not remove compatibility aliases or relocate the three legacy root export job class names in this delivery without explicit queue/caller proof. After merge, record the PR/merge checkpoint before starting any follow-up cleanup.