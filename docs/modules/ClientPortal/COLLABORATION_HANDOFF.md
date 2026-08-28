# ClientPortal Module — Collaboration Handoff

- Last updated: 2026-08-28
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Stable `main` checkpoint before MR-5: `de4a2df3585fb713446e2c46d96d1867c81c338a`
- Completed MR: **MR-4 — Muasamcong reference migration**
- MR-4 merge commit: `b8ace3f913c2bfab846ee28ee70db2fda625858c`
- Current MR: **MR-5 — PWA External File Download & Return UX**
- Feature branch: `fix/clientportal-pwa-external-file-handoff`
- Pull request: **#64 — OPEN**
- MR-5 status: **ACCEPTANCE PASS — OWNER MERGE APPROVAL REQUIRED**

## Stable architecture entering MR-5

ClientPortal remains an authenticated Client/WebApp platform that can host multiple applications without placing Module-specific business logic in Portal core.

Core rule:

> Không được thêm logic đặc thù Module vào ClientPortal core.

MR-1 through MR-4 are merged/closed. MR-4 moved Muasamcong-specific presentation concerns out of the shared App Shell and established application-neutral `shell_extensions`. MR-2 adaptive navigation and MR-3 0/1/N Portal Home behavior remain preserved.

## MR-5 trigger and root cause

Manual testing on an installed iPhone PWA found that opening a generated Excel/PDF file could replace the active PWA document with the OS/browser file preview. The application then lost the visible workspace/navigation context.

The relevant backend download routes remain authenticated same-origin routes. File generation, authorization and storage availability are not changed by MR-5.

The required contract is:

- preserve the installed-PWA top-level workspace before handing a file to the OS;
- keep authenticated access and existing permissions unchanged;
- avoid public temporary URLs;
- avoid service-worker caching of private binary responses;
- keep normal desktop/browser native download behavior unchanged.

## MR-5 implementation

The accepted implementation is Muasamcong-scoped through:

```text
Modules/ClientPortal/resources/views/applications/muasamcong/partials/external-file-handoff.blade.php
```

In installed/standalone PWA mode:

1. Excel/PDF link navigation is intercepted before top-level navigation.
2. The protected same-origin URL is fetched with `credentials: 'same-origin'` and `cache: 'no-store'`.
3. The response is converted to a temporary `Blob` / `File`.
4. The PWA shows an in-app ready state with an explicit **Mở / Chia sẻ tệp** action.
5. A second user gesture calls `navigator.share({ files: [...] })` so transient user activation is preserved.
6. Unsupported file-share capability keeps the PWA workspace intact and shows a safe fallback instruction instead of replacing the top-level page.
7. The share action disables while a share attempt is running and remains retryable after a non-`AbortError` failure.

Normal desktop/browser behavior remains native because interception is limited to installed/standalone PWA mode.

### Rejected approaches

The following patterns were tested on iPhone installed PWA and are rejected for this case:

- hidden iframe navigation to the authenticated binary URL;
- `window.open(binaryUrl, '_blank')`;
- top-level `window.location` fallback to the binary URL.

These approaches reproduced the original preview/workspace-loss behavior and must not be restored as the generic solution.

## Project-wide documentation contract

Canonical guidance:

```text
docs/PWA_EXTERNAL_FILE_HANDOFF.md
```

`docs/GITHUB_COLLABORATION_WORKFLOW.md` contains the mandatory PWA download/open-file gate. Future Module work involving PWA-capable file actions must read the canonical document and verify applicable iOS/Android/desktop behavior before merge.

## Automated acceptance

Latest owner-reported ClientApps regression after the retry corrective:

```text
Tests: 87 passed (613 assertions)
Duration: 13.17s
```

Automated status: **PASS**.

Focused MR-5 contract remains in:

```text
tests/Feature/ClientApps/ClientPortalPwaExternalFileHandoffTest.php
```

It locks the installed/standalone detection, Excel/PDF markers, authenticated no-store fetch, Blob/File conversion, Web Share handoff, explicit second user action, retry contract and absence of the rejected iframe/window-open/top-level-navigation patterns.

## Manual acceptance

Owner-reported manual acceptance for the final Web Share implementation:

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

MR-5 file-handoff acceptance status: **PASS**.

## Final PR review corrective

PR #64 final diff review found that the share click handler used `{ once: true }`, which made a non-`AbortError` retry button visually re-enable without retaining a listener.

Corrective status: **FIXED**.

- `{ once: true }` was removed;
- the button remains disabled while `navigator.share(...)` is active;
- a non-`AbortError` re-enables the same live handler so the user can retry;
- focused contract coverage asserts the retry behavior;
- latest ClientApps regression is `87 passed (613 assertions)`.

## Separate issue discovered during MR-5 acceptance

A separate PWA installation UX issue was discovered on iPhone: the current launcher install button relies on the Chromium-style `beforeinstallprompt` event. iOS Safari does not provide that install flow, so the current iPhone install button/path can appear non-functional.

This issue is intentionally **not added to MR-5**. It becomes MR-6 so the accepted external-file handoff scope remains isolated.

Planned MR-6 contract:

```text
MR-6 — PWA Install UX

- detect iPhone/iPad browser context correctly
- provide Safari Share → Add to Home Screen guidance instead of relying on beforeinstallprompt
- preserve beforeinstallprompt behavior for supported Chromium/Android browsers
- hide or adapt install UI when already running standalone
- add automated contract coverage and iPhone/Android/manual acceptance
```

## Approved authentication roadmap

A later MR will add end-user account registration/authentication for ClientPortal/PWA without creating a separate PWA identity system.

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

MR-7 is roadmap only. No authentication implementation belongs to MR-5 or MR-6 unless separately approved.

## Roadmap checkpoint

```text
MR-1 — Portal Architecture Foundation: MERGED / CLOSED
MR-2 — Adaptive Navigation: MERGED / CLOSED
MR-3 — Dynamic Portal Home: MERGED / CLOSED
MR-4 — Muasamcong reference migration: MERGED / CLOSED
MR-5 — PWA External File Download & Return UX: PR #64 OPEN / ACCEPTANCE PASS / OWNER MERGE APPROVAL REQUIRED
MR-6 — PWA Install UX: APPROVED NEXT MR / NOT STARTED
MR-7 — PWA Account Registration & Google Authentication: APPROVED ROADMAP / NOT STARTED
```

## Next-step boundary

PR #64 implementation, automated acceptance and applicable manual file-handoff acceptance are PASS. Merge only after GitHub reports a mergeable state and the owner explicitly approves the merge.

After MR-5 is merged, update `main`, refresh this handoff with the merge commit, then create a new branch for MR-6 only after confirming the new `main` checkpoint.
