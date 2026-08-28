# ClientPortal Module — Collaboration Handoff

- Last updated: 2026-08-28
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Stable `main` checkpoint before MR-7: `302e051686f8b2bfa0e608f300d4cbde3f82a634`
- Completed MR: **MR-6 — PWA Install UX**
- MR-6 pull request: **#65 — MERGED / CLOSED**
- MR-6 merge commit: `db9a4418593bb62518f4787d7f40199d8214a1c6`
- Active MR: **MR-7 — PWA Account Registration & Google Authentication**
- MR-7 branch: `feat/clientportal-pwa-account-auth`
- MR-7 pull request: **#67 — OPEN / MERGEABLE**
- MR-7 status: **ACCEPTANCE PASS / PR GATE PASS / READY FOR MANUAL MERGE**

## Stable architecture

ClientPortal remains an authenticated Client/WebApp platform that can host multiple applications without placing Module-specific business logic in Portal core.

Core rule:

> Không được thêm logic đặc thù Module vào ClientPortal core.

MR-1 through MR-6 are merged/closed. MR-7 extends the shared Auth boundary and ClientPortal PWA presentation without creating a separate PWA user model, JWT boundary, or Module-specific authentication stack.

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

Local working-tree cleanliness: **CLEAN** (`git status --short` returned no output).

## PR #67 gate checkpoint

```text
Base: main
Base checkpoint: 302e051686f8b2bfa0e608f300d4cbde3f82a634
Head branch: feat/clientportal-pwa-account-auth
PR: #67
State: OPEN
Draft: false
Mergeable: true
Unexpected diff scope: none found
GitHub commit statuses/checks: none configured/reported
PR gate: PASS
```

MR-7 is ready for owner/manual merge. Do not interpret this source acceptance as production deployment or Google credential/callback enablement; production operational enablement remains a separate action.

## Roadmap checkpoint

```text
MR-1 — Portal Architecture Foundation: MERGED / CLOSED
MR-2 — Adaptive Navigation: MERGED / CLOSED
MR-3 — Dynamic Portal Home: MERGED / CLOSED
MR-4 — Muasamcong reference migration: MERGED / CLOSED
MR-5 — PWA External File Download & Return UX: MERGED / CLOSED — PR #64
MR-6 — PWA Install UX: MERGED / CLOSED — PR #65
MR-7 — PWA Account Registration & Google Authentication: PR #67 / READY FOR MANUAL MERGE
```

## Next-step boundary

The owner may now merge PR #67 after reviewing the PR link. After merge:

1. switch/pull `main`;
2. verify the actual merge commit/checkpoint;
3. refresh this handoff against merged `main` and mark MR-7 **MERGED / CLOSED**;
4. do not start a new roadmap MR until the next target is explicitly selected and approved.
