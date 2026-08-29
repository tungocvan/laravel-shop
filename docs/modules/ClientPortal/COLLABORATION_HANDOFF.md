# ClientPortal Module — Collaboration Handoff

- Last updated: 2026-08-29
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Stable `main` checkpoint after MR-8 merge: `90290f492fde65fdac9b179705285273c69cd317`
- Completed MR: **MR-8 — PWA Header Account Menu**
- MR-8 pull request: **#68 — MERGED / CLOSED**
- MR-8 merge commit: `90290f492fde65fdac9b179705285273c69cd317`
- MR-8 source head: `96d870807d9e12f3d8b71b3e8ae0d69e060263ca`
- MR-8 status: **MERGED / CLOSED**
- Active delivery: **Corrective — canonical web logout / route cache**
- Next MR/phase: **NOT DETERMINED**

## Stable architecture

ClientPortal remains an authenticated Client/WebApp platform that can host multiple applications without placing Module-specific business logic in Portal core.

Core rule:

> Không được thêm logic đặc thù Module vào ClientPortal core.

MR-1 through MR-8 are merged/closed. MR-7 extends the shared Auth boundary and ClientPortal PWA presentation without creating a separate PWA user model, JWT boundary, or Module-specific authentication stack. MR-8 adds the shared ClientPortal account-menu presentation while preserving that Auth boundary.

## MR-7 implemented contract

```text
MR-7 — PWA Account Registration & Google Authentication

Standard registration
- ordinary user registration through ClientPortal/PWA
- new local account starts inactive/unverified
- email OTP verifies and activates the account
- HMAC-hashed OTP at rest; no plaintext OTP persistence
- OTP expiration, resend cooldown, invalidation and attempt controls
- registration / verification / resend rate limiting by email and IP
- successful verification establishes the normal web-guard session and returns to ClientPortal

Google authentication
- Google OAuth sign-in/registration through Socialite
- Google-verified email required
- new Google account can become active/verified without OTP
- existing google_id remains the stable provider identity
- automatic same-email linking is restricted to an active account with successful MR-7 OTP provenance
- legacy/special existing-email accounts require authenticated explicit linking
- provider conflicts are rejected rather than allowing takeover
- PWA Google flow does not persist Google access/refresh tokens

Shared auth boundary
- same App\\Models\\User and web guard for PWA/browser
- inactive local accounts cannot password-login
- session regeneration, CSRF, authorization and logout behavior remain in the shared Laravel boundary
- successful PWA auth returns to /my-apps
```

## MR-7 PWA / UX contract

The install surface remains available from the Website, while the installed PWA launches into ClientPortal:

```text
manifest id: /
manifest scope: /
manifest start_url: /my-apps
PWA display: standalone
```

ClientPortal pages use the shared Website manifest instead of creating a competing installed-app identity.

When Website pages run inside standalone PWA mode, Website login/register CTAs hand off to `/my-apps/login` and `/my-apps/register`. Normal browser Website behavior continues to use the ordinary Website auth routes.

The shared ClientPortal application shell was also aligned for wide desktop/fullscreen use:

- header and body share the same wide container contract
- desktop sidebar width is reduced/adaptive
- main content receives responsive wide-screen padding
- mobile/tablet adaptive navigation behavior remains intact

## MR-7 security checkpoint

Security hardening completed before acceptance:

- separate `user_email_verifications` persistence
- OTP HMAC hashing keyed by application key
- successful OTP verification is distinct from OTP invalidation (`verified_at` vs `invalidated_at`)
- resend/verification operations serialize the relevant verification state to reduce concurrent OTP races
- registration, verification and resend have independent email/IP rate limits
- Google provider email must be verified
- Google auto-link cannot rely merely on matching an arbitrary legacy email
- google_id ownership/conflict checks reject ambiguous identity states
- explicit Google linking requires an authenticated active local account and matching verified provider email
- PWA Google flow does not store provider tokens

## MR-7 acceptance checkpoint

Focused Auth/security regression after final hardening:

```text
Tests: 19 passed (80 assertions)
Duration: 1.80s
```

ClientApps impacted regression after final PWA/layout contract corrections:

```text
Tests: 105 passed (713 assertions)
Duration: 7.74s
```

Automated status: **PASS**.

Manual UI acceptance: **PASS** for:

- local Registration
- OTP verification / activation
- Google Authentication
- installed PWA launch to `/my-apps`
- standalone PWA Website → ClientPortal auth handoff
- desktop/fullscreen shared ClientPortal layout

Full project regression: **NOT APPLICABLE — scoped Auth + impacted ClientApps strategy**. PR-gate review confirmed `tests/Feature/Auth` contains the same three Auth test files covered by the 19-test run; Website changes are limited to PWA manifest/auth handoff presentation and are covered by ClientApps contracts plus manual UI acceptance.

Local working-tree cleanliness before merge: **CLEAN** (`git status --short` returned no output).

## MR-7 merge checkpoint

```text
PR: #67
PR state: CLOSED
Merged: true
Base: main
Source head: a6a26445af363dffbcf85fba043bdc5b6b58c94d
Merge commit: 3d8adbaa7356d5e41f67af9601693e74ccd5e9b5
main immediately after merge: 3d8adbaa7356d5e41f67af9601693e74ccd5e9b5
MR-7: MERGED / CLOSED
```

Source acceptance does not imply production deployment or Google credential/callback enablement; production operational enablement remains a separate action.

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
Next MR/phase after MR-8: NOT DETERMINED
```

## MR-8 implemented contract

Approved scope:

- one shared ClientPortal account menu for the launcher and application shell;
- read-only `/my-apps/account` information;
- bounded `/my-apps/settings` using the existing Auth-owned Google link;
- canonical CSRF-protected Auth logout;
- no dependency on the disabled Admin-only `Modules/Account`;
- no schema, migration, Website, manifest or service-worker change.

Architecture boundary:

- ClientPortal owns Header/menu/account/settings presentation;
- `App\Models\User` through `auth:web` is the current identity source;
- Auth owns logout, session invalidation, CSRF and Google linking;
- application-specific business logic remains outside ClientPortal core.

Acceptance status:

- focused account-menu tests + impacted ClientApps/Auth regression: **PASS**

```text
Tests: 130 passed (834 assertions)
Duration: 8.52s
```

- desktop/tablet/mobile/standalone manual UI: **PASS — user confirmed**
- mobile evidence: **PASS at 430 × 932**; the account panel stays inside the viewport and exposes all three actions
- frontend asset note: the first mobile check used stale compiled Tailwind output; `npm run build` restored the intended responsive positioning without a source correction
- Git-clean on user local: **CLEAN** (`git status --short` returned no output after synchronizing commit `1095af9f`)
- pull request: **#68 — MERGED / CLOSED**
- source head: `96d870807d9e12f3d8b71b3e8ae0d69e060263ca`
- merge commit: `90290f492fde65fdac9b179705285273c69cd317`
- stable `main` immediately after merge: `90290f492fde65fdac9b179705285273c69cd317`

## MR-8 merge checkpoint

```text
PR: #68
PR state: CLOSED
Merged: true
Base: main
Source head: 96d870807d9e12f3d8b71b3e8ae0d69e060263ca
Merge commit: 90290f492fde65fdac9b179705285273c69cd317
main immediately after merge: 90290f492fde65fdac9b179705285273c69cd317
MR-8: MERGED / CLOSED
```

Production deployment remains a separate operational action. MR-8 introduces no new environment variable, Google credential, migration or runtime module-state change.

## Corrective — Canonical Web Logout / Route Cache

Production optimization exposed a pre-existing duplicate global route name `logout`: Auth owned `POST /logout`, while Website also exposed `POST /website/logout` with the same name. This prevented `php artisan route:cache` from serializing routes.

Approved corrective contract:

```text
branch: fix/auth-canonical-web-logout-route-cache
canonical route: POST /logout
route name: logout
owner: Modules/Auth
handler: Modules\Auth\Http\Controllers\AuthController::clientLogout
legacy /website/logout: removed
admin.logout: unchanged
```

Regression protection requires exactly one route named `logout`, canonical Auth ownership/action, and absence of the legacy Website endpoint.

Acceptance evidence before PR:

```text
php artisan route:cache
PASS — Routes cached successfully.

ClientApps impacted regression
111 passed (754 assertions)
Duration: 8.52s

AuthGuardSeparationTest
6 passed (35 assertions)
Duration: 0.54s
```

Full-project regression: **NOT APPLICABLE — bounded Auth/Website/ClientPortal corrective**. No migration, schema, manifest, service-worker or environment-variable change is introduced.

Production operational note: for the `tnv` stack, `.env` changes require the established `platform-v2 deploy optimize tnv` / `OPTIMIZE / RELOAD .ENV: tnv` operation so long-lived PHP/web processes reload environment. This corrective restores route-cache compatibility for that optimization path.

## Next-step boundary

The canonical logout corrective is awaiting PR review/merge. After merge, update this handoff with its PR/merge checkpoint and return `Active delivery` to `NONE`. Further ClientPortal work still requires an explicitly selected target and approved plan; the next MR/phase remains **NOT DETERMINED**.
