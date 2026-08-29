# ClientPortal Module — Collaboration Handoff

- Last updated: 2026-08-29
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Completed MR: **MR-8 — PWA Header Account Menu**
- MR-8 pull request: **#68 — MERGED / CLOSED**
- MR-8 merge commit: `90290f492fde65fdac9b179705285273c69cd317`
- MR-8 status: **MERGED / CLOSED**
- Completed corrective: **Canonical web logout / route cache — PR #86 MERGED / CLOSED**
- Corrective merge commit: `0439b7675e6af8b9bb49046c8d941e43ff135ac0`
- Active delivery: **NONE**
- Next MR/phase: **NOT DETERMINED**

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
Next MR/phase after MR-8: NOT DETERMINED
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

MR-8 and the canonical logout corrective are complete and merged into `main`. `Active delivery` is now `NONE`. Before starting further ClientPortal work, explicitly select the next target, inspect affected source/dependencies, and approve a new plan before branch creation or implementation. The next MR/phase remains **NOT DETERMINED**.
