# ClientPortal Module — Collaboration Handoff

- Last updated: 2026-08-28
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Stable `main` checkpoint after MR-5: `e3353cdfe326642eb0ed3081ea20d93ee7f8a363`
- Completed MR: **MR-5 — PWA External File Download & Return UX**
- MR-5 pull request: **#64 — MERGED / CLOSED**
- MR-5 merge commit: `e3353cdfe326642eb0ed3081ea20d93ee7f8a363`
- Next MR: **MR-6 — PWA Install UX**
- MR-6 status: **APPROVED / NOT STARTED**

## Stable architecture after MR-5

ClientPortal remains an authenticated Client/WebApp platform that can host multiple applications without placing Module-specific business logic in Portal core.

Core rule:

> Không được thêm logic đặc thù Module vào ClientPortal core.

MR-1 through MR-5 are merged/closed. MR-4 moved Muasamcong-specific presentation concerns out of the shared App Shell and established application-neutral `shell_extensions`. MR-5 adds an authenticated installed-PWA external-file handoff contract without making ClientPortal core Muasamcong-specific.

## MR-5 completed contract

Manual testing on an installed iPhone PWA found that opening generated Excel/PDF files could replace the active PWA document with OS/browser preview and lose the visible workspace/navigation context.

The accepted implementation preserves the installed PWA top-level workspace, keeps authenticated same-origin access and existing permissions unchanged, avoids public temporary URLs, avoids service-worker caching of private binary responses, and preserves normal desktop/browser native download behavior.

The Muasamcong-scoped implementation lives in:

```text
Modules/ClientPortal/resources/views/applications/muasamcong/partials/external-file-handoff.blade.php
```

Installed/standalone flow:

1. Excel/PDF link navigation is intercepted before top-level navigation.
2. The protected same-origin URL is fetched with `credentials: 'same-origin'` and `cache: 'no-store'`.
3. The response is converted to a temporary `Blob` / `File`.
4. The PWA shows an in-app ready state with an explicit **Mở / Chia sẻ tệp** action.
5. A second user gesture calls `navigator.share({ files: [...] })` so transient user activation is preserved.
6. Unsupported file-share capability keeps the PWA workspace intact and shows a safe fallback instruction instead of replacing the top-level page.
7. The share action disables while a share attempt is running and remains retryable after a non-`AbortError` failure.

Rejected generic approaches for this iOS installed-PWA case:

- hidden iframe navigation to the authenticated binary URL;
- `window.open(binaryUrl, '_blank')`;
- top-level `window.location` fallback to the binary URL.

Canonical project-wide guidance is in:

```text
docs/PWA_EXTERNAL_FILE_HANDOFF.md
```

`docs/GITHUB_COLLABORATION_WORKFLOW.md` contains the mandatory PWA download/open-file gate for future Modules.

## MR-5 acceptance checkpoint

Latest owner-reported ClientApps regression after the retry corrective:

```text
Tests: 87 passed (613 assertions)
Duration: 13.17s
```

Automated status: **PASS**.

Manual acceptance:

```text
iPhone installed PWA
- Excel: PASS
- PDF: PASS
- native open/share handoff: PASS
- PWA workspace/context remains recoverable: PASS

Android installed PWA
- Excel: PASS
- PDF: PASS

Desktop / normal browser
- Excel: PASS
- PDF: PASS
- native browser download behavior preserved: PASS
```

MR-5 status: **MERGED / CLOSED**.

## MR-6 — PWA Install UX

A separate PWA installation UX issue was discovered on iPhone: the current launcher install button relies on the Chromium-style `beforeinstallprompt` event. iOS Safari does not provide that install flow, so the current iPhone install button/path can appear non-functional.

Approved MR-6 contract:

```text
MR-6 — PWA Install UX

- detect iPhone/iPad browser context correctly
- provide Safari Share → Add to Home Screen guidance instead of relying on beforeinstallprompt
- preserve beforeinstallprompt behavior for supported Chromium/Android browsers
- hide or adapt install UI when already running standalone
- add automated contract coverage and iPhone/Android/manual acceptance
```

MR-6 must start from refreshed `main` after the MR-5 merge checkpoint above. Do not create or mutate the MR-6 branch until repository/source bootstrap is re-checked and the implementation plan is confirmed for that new branch.

## Approved authentication roadmap

Planned MR-7 contract:

```text
MR-7 — PWA Account Registration & Google Authentication

Standard registration
- registration form for ordinary user accounts
- new account starts inactive/unverified
- send OTP to the registered email
- OTP verifies email and activates the account
- OTP expiration, resend controls and rate limiting
- OTP must not be stored as plaintext

Google authentication
- Google OAuth sign-in/registration
- valid Google-verified email can activate the new account immediately
- no OTP required for the Google path
- existing-email linking must be handled safely and must not allow account takeover merely because an email matches

Shared auth boundary
- same user/account model for PWA and normal browser
- preserve current session, CSRF, authorization and logout behavior
- successful login returns users to the appropriate ClientPortal/PWA workspace
```

MR-7 remains roadmap only and is not part of MR-6.

## Roadmap checkpoint

```text
MR-1 — Portal Architecture Foundation: MERGED / CLOSED
MR-2 — Adaptive Navigation: MERGED / CLOSED
MR-3 — Dynamic Portal Home: MERGED / CLOSED
MR-4 — Muasamcong reference migration: MERGED / CLOSED
MR-5 — PWA External File Download & Return UX: MERGED / CLOSED — PR #64
MR-6 — PWA Install UX: APPROVED NEXT MR / NOT STARTED
MR-7 — PWA Account Registration & Google Authentication: APPROVED ROADMAP / NOT STARTED
```

## Next-step boundary

Before MR-6 implementation, confirm current `main`, read the current launcher/PWA source and relevant workflow/docs, then propose the exact MR-6 branch/scope. Only create the new MR-6 branch after that bootstrap is confirmed.
