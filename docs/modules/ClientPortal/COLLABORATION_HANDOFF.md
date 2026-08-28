# ClientPortal Module — Collaboration Handoff

- Last updated: 2026-08-28
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Stable `main` checkpoint before MR-6: `69d9c7b052e7bc4f580426eed52e42c1e6e33ae6`
- Completed stable MR: **MR-5 — PWA External File Download & Return UX**
- MR-5 pull request: **#64 — MERGED / CLOSED**
- MR-5 merge commit: `e3353cdfe326642eb0ed3081ea20d93ee7f8a363`
- Active MR: **MR-6 — PWA Install UX**
- MR-6 branch: `feat/clientportal-pwa-install-ux`
- MR-6 status: **COMPLETED / READY FOR PR**

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

MR-6 replaces the launcher-only Chromium assumption with an adaptive install UX while preserving the existing ClientPortal launcher and authentication boundaries.

Implemented contract:

```text
MR-6 — PWA Install UX

- detect iPhone/iPad browser context correctly
- provide Safari Share → Add to Home Screen guidance instead of relying on beforeinstallprompt
- preserve beforeinstallprompt behavior for supported Chromium/Android browsers
- hide install UI when already running standalone
- keep unsupported non-iOS browsers free of a fake install CTA
- keep install copy configurable and backward-compatible with older launcher settings/mocks
- add automated contract coverage and manual UI acceptance
```

Implementation scope:

```text
Modules/ClientPortal/config/pwa.php
Modules/ClientPortal/resources/views/pages/apps.blade.php
Modules/ClientPortal/resources/views/partials/pwa-install.blade.php
tests/Feature/ClientApps/ClientPortalPwaInstallUxTest.php
```

Owner-requested workflow policy adjustment in the same branch:

```text
docs/GITHUB_COLLABORATION_WORKFLOW.md
```

The canonical workflow now uses focused tests + Module regression + impacted/cross-module regression as the default for large multi-Module projects. Full project regression is no longer a default per-MR gate and is only applicable for broad shared/core/system changes, release-wide checkpoints, or an explicit request.

Behavior checkpoint:

- iPhone/iPad Safari receives explicit Share → Add to Home Screen guidance.
- iOS/iPadOS non-Safari receives guidance to open the page in Safari before installation.
- Chromium/Android keeps the native `beforeinstallprompt` flow.
- installed/standalone mode hides the install CTA.
- unsupported non-iOS browsers do not receive a fake generic install flow.
- launcher service-worker registration remains intact.
- install-copy keys have view-boundary fallbacks so older launcher settings/mocks do not fail with undefined-array-key errors.

## MR-6 acceptance checkpoint

Focused MR-6 test: **PASS**.

ClientApps Module/impacted regression after the backward-compatibility corrective:

```text
Tests: 95 passed (650 assertions)
Duration: 14.33s
```

Automated status: **PASS**.

Regression scope covers ClientPortal plus ClientApps adapters/contracts directly exercised by the ClientPortal surface, including its Request and Muasamcong integrations.

Full project regression: **NOT APPLICABLE — module-scoped regression strategy**. MR-6 application changes are confined to ClientPortal launcher/PWA presentation and its focused tests; no broad shared/core runtime infrastructure change requires a project-wide suite. The workflow documentation change is docs-only.

Manual UI acceptance: **PASS**.

Git working tree after acceptance: **CLEAN** (`git status --short` returned no output).

MR-6 branch status: **COMPLETED / READY FOR PR**.

No production enablement is implied by MR-6 completion or merge. Production deployment/enablement remains a separate operational action.

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
MR-6 — PWA Install UX: COMPLETED / READY FOR PR
MR-7 — PWA Account Registration & Google Authentication: APPROVED ROADMAP / NOT STARTED
```

## Next-step boundary

Next authorized step for MR-6 is PR creation/review against `main`, then merge only after the PR gate remains clean. After merge, refresh `main`, record the final PR/merge checkpoint in this handoff, and only then begin any separately approved MR-7 work.
