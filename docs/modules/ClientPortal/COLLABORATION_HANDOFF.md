# ClientPortal Module — Collaboration Handoff

- Last updated: 2026-08-28
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Stable `main` checkpoint before MR-5: `de4a2df3585fb713446e2c46d96d1867c81c338a`
- Completed MR: **MR-4 — Muasamcong reference migration**
- MR-4 merge commit: `b8ace3f913c2bfab846ee28ee70db2fda625858c`
- Current MR: **MR-5 — PWA External File Download & Return UX**
- Feature branch: `fix/clientportal-pwa-external-file-handoff`
- Pull request: **PENDING — not created**
- MR-5 status: **IN PROGRESS — IMPLEMENTED / AUTOMATED + MANUAL ACCEPTANCE REQUIRED**

## Stable architecture entering MR-5

ClientPortal remains an authenticated Client/WebApp platform that can host multiple applications without placing Module-specific business logic in Portal core.

Core rule:

> Không được thêm logic đặc thù Module vào ClientPortal core.

MR-1 through MR-4 are merged/closed. MR-4 moved Muasamcong-specific presentation concerns out of the shared App Shell and established application-neutral `shell_extensions`. MR-2 adaptive navigation and MR-3 0/1/N Portal Home behavior remain preserved.

## MR-5 trigger and root cause

After MR-4 closeout, manual testing on an installed mobile PWA found a separate UX defect: opening a generated Excel file could replace the active PWA document with the OS/browser document preview. The user could open the XLSX file, but the PWA workspace/navigation context was no longer available as the active page.

The relevant backend download routes are authenticated same-origin routes and correctly return files through `Storage::disk('local')->download(...)`. File generation, storage availability and authorization were not the cause.

The presentation issue was direct top-level navigation from the installed PWA to the binary response.

Important boundary:

- a web application cannot require iOS/Excel/native preview to provide a custom "Back to PWA" button;
- the correct application responsibility is to preserve the PWA top-level workspace before handing the file to browser/OS download/preview handling.

## MR-5 approved scope

```text
MR-5 — PWA External File Download & Return UX

- preserve installed-PWA workspace when downloading/opening Excel/PDF
- apply the behavior to both Excel and PDF Price List actions
- keep desktop/ordinary browser native behavior unchanged
- keep authenticated routes, permissions and file-existence checks unchanged
- do not create public temporary URLs
- do not broadly cache private binary responses
- document the behavior as a project-wide rule for future Modules
- update docs/GITHUB_COLLABORATION_WORKFLOW.md so future Module work checks this gate
```

No Request changes, database/schema changes, Module enablement changes, storage-generation changes or production feature activation are part of MR-5.

## MR-5 implementation

### Muasamcong Price List file handoff

`Modules/ClientPortal/resources/views/applications/muasamcong/partials/price-list-workspace-polish.blade.php` now detects installed/standalone PWA mode using display-mode plus the iOS `navigator.standalone` fallback.

For installed PWA only, Excel and PDF download links are intercepted before top-level navigation. The existing authenticated same-origin download URL is loaded through a hidden iframe so the binary request uses the current PWA session while the top-level Price List workspace remains alive.

Normal desktop/browser behavior is not intercepted.

The implementation deliberately stays Muasamcong-scoped for now. It is not promoted into a speculative shared ClientPortal component until another active application needs the same runtime primitive.

### Project-wide documentation contract

New canonical operational guidance:

```text
docs/PWA_EXTERNAL_FILE_HANDOFF.md
```

It documents:

- why direct binary navigation can break installed-PWA return UX;
- the requirement to preserve the top-level PWA workspace;
- same-origin authenticated download/session considerations;
- acceptable handoff techniques such as hidden iframe or authenticated fetch/Blob where appropriate;
- service-worker/private-cache boundary;
- iOS/Android/desktop acceptance requirements;
- debugging order and ownership rules.

`docs/GITHUB_COLLABORATION_WORKFLOW.md` now contains a mandatory PWA download/open-file gate. Future Module work involving PWA-capable file actions must read the new document, record the gate in bootstrap/handoff, and verify applicable iOS/Android/desktop behavior before merge.

## Automated coverage

New focused test:

```text
tests/Feature/ClientApps/ClientPortalPwaExternalFileHandoffTest.php
```

It locks:

- standalone-PWA detection;
- top-level navigation prevention for file handoff;
- hidden-iframe authenticated file request behavior;
- both Excel and PDF integration;
- presence of the project-wide PWA file-handoff workflow/documentation contract.

Automated test status: **NOT YET RUN by owner**.

## Manual acceptance required

Before MR-5 can be marked completed, verify at minimum:

```text
iPhone installed PWA
- tap Excel: Price List workspace is not replaced
- file can still be opened/downloaded
- return to PWA: same Price List context remains
- repeat for PDF

Android installed PWA
- Excel/PDF handoff does not destroy application navigation context

Desktop / ordinary browser
- Excel/PDF native download behavior still works

Security
- protected file routes still require the same authenticated session/permissions
- no public URL or private service-worker cache was introduced
```

If a native viewer itself has no visible return button, that is not by itself a failure; the acceptance target is that the PWA page remains recoverable and unchanged when the user returns to the app.

## Roadmap checkpoint

```text
MR-1 — Portal Architecture Foundation: MERGED / CLOSED
MR-2 — Adaptive Navigation: MERGED / CLOSED
MR-3 — Dynamic Portal Home: MERGED / CLOSED
MR-4 — Muasamcong reference migration: MERGED / CLOSED
MR-5 — PWA External File Download & Return UX: IN PROGRESS
```

## Next-step boundary

MR-5 implementation is present on `fix/clientportal-pwa-external-file-handoff`, but the branch is not PR-ready until focused automated tests, ClientApps regression, applicable manual PWA/browser acceptance and Git-clean verification are reported.

Do not create or merge a PR until those gates are recorded as actual evidence. Do not broaden MR-5 into unrelated ClientPortal, Muasamcong domain, Request, database, Module-state or production-runtime work without separate approval.
