# ClientPortal Module — Collaboration Handoff

- Last updated: 2026-08-28
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Stable `main` checkpoint after MR-6: `db9a4418593bb62518f4787d7f40199d8214a1c6`
- Completed MR: **MR-6 — PWA Install UX**
- MR-6 pull request: **#65 — MERGED / CLOSED**
- MR-6 merge commit: `db9a4418593bb62518f4787d7f40199d8214a1c6`
- Next authorized roadmap item: **MR-7 — PWA Account Registration & Google Authentication**
- MR-7 status: **APPROVED ROADMAP / NOT STARTED**

## Stable architecture after MR-6

ClientPortal remains an authenticated Client/WebApp platform that can host multiple applications without placing Module-specific business logic in Portal core.

Core rule:

> Không được thêm logic đặc thù Module vào ClientPortal core.

MR-1 through MR-6 are merged/closed. MR-4 moved Muasamcong-specific presentation concerns out of the shared App Shell and established application-neutral `shell_extensions`. MR-5 added the authenticated installed-PWA external-file handoff contract. MR-6 adds adaptive PWA installation UX while preserving the launcher/authentication boundary and keeping installation behavior browser-capability aware.

## MR-6 completed contract

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

Regression scope covered ClientPortal plus ClientApps adapters/contracts directly exercised by the ClientPortal surface, including its Request and Muasamcong integrations.

Full project regression: **NOT APPLICABLE — module-scoped regression strategy**. MR-6 application changes were confined to ClientPortal launcher/PWA presentation and focused tests; no broad shared/core runtime infrastructure change required a project-wide suite.

Manual UI acceptance: **PASS**.

Git working tree before PR/merge acceptance: **CLEAN** (`git status --short` returned no output).

PR #65 review/merge checkpoint:

```text
Base: main
Diff review: PASS — no unexpected application scope found
Mergeable before merge: PASS
GitHub status checks: none configured/reported for the PR head
GitHub Actions PR workflow runs: none reported
Merged: 2026-08-28
Merge commit: db9a4418593bb62518f4787d7f40199d8214a1c6
```

MR-6 status: **MERGED / CLOSED**.

No production enablement is implied by MR-6 completion or merge. Production deployment/enablement remains a separate operational action.

## Workflow policy checkpoint

During MR-6, the canonical workflow was updated at owner request for a large multi-Module repository:

```text
focused tests
→ Module regression
→ impacted/cross-module regression when dependencies/shared contracts are involved
→ full project regression only when applicable to broad shared/core/system scope, a release-wide checkpoint, or an explicit request
```

When full project regression is not applicable, handoff/PR gate records:

```text
NOT APPLICABLE — module-scoped regression strategy
```

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

MR-7 remains roadmap only and has not started.

## Roadmap checkpoint

```text
MR-1 — Portal Architecture Foundation: MERGED / CLOSED
MR-2 — Adaptive Navigation: MERGED / CLOSED
MR-3 — Dynamic Portal Home: MERGED / CLOSED
MR-4 — Muasamcong reference migration: MERGED / CLOSED
MR-5 — PWA External File Download & Return UX: MERGED / CLOSED — PR #64
MR-6 — PWA Install UX: MERGED / CLOSED — PR #65
MR-7 — PWA Account Registration & Google Authentication: APPROVED ROADMAP / NOT STARTED
```

## Next-step boundary

MR-6 is fully closed at source/handoff level once this docs-only post-merge closeout is merged into `main`. Do not begin MR-7 implementation merely because it is present in the roadmap; on the next chat/session, re-bootstrap `main`, verify current source/docs, propose the exact MR-7 implementation plan, and only create/mutate an MR-7 branch after that plan is approved.
