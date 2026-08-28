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
- MR-5 status: **IN PROGRESS — WEB SHARE CORRECTIVE IMPLEMENTED / ACCEPTANCE REQUIRED**

## Stable architecture entering MR-5

ClientPortal remains an authenticated Client/WebApp platform that can host multiple applications without placing Module-specific business logic in Portal core.

Core rule:

> Không được thêm logic đặc thù Module vào ClientPortal core.

MR-1 through MR-4 are merged/closed. MR-4 moved Muasamcong-specific presentation concerns out of the shared App Shell and established application-neutral `shell_extensions`. MR-2 adaptive navigation and MR-3 0/1/N Portal Home behavior remain preserved.

## MR-5 trigger and root cause

After MR-4 closeout, manual testing on an installed iPhone PWA found a separate UX defect: opening a generated Excel file replaced the active PWA document with the OS/browser document preview. The user could open the XLSX file, but the PWA workspace/navigation context was no longer available as the active page.

The relevant backend download routes are authenticated same-origin routes and correctly return files through `Storage::disk('local')->download(...)`. File generation, storage availability and authorization were not the cause.

The presentation issue is the mobile installed-PWA handoff from an application workspace to an authenticated binary response.

Important boundary:

- a web application cannot require iOS/Excel/native preview to provide a custom "Back to PWA" button;
- the application must preserve its own top-level workspace before handing the file to the OS;
- authenticated file access must continue to use the existing session and protected route.

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

## MR-5 implementation evolution

### Rejected approach 1 — hidden iframe

The first implementation intercepted Excel/PDF links in installed-PWA mode and loaded the authenticated binary URL through a hidden iframe.

After the VPS was confirmed on the correct MR-5 branch and Laravel cache was cleared, manual iPhone testing reproduced the original native XLSX preview behavior. Hidden iframe is therefore **REJECTED for this iOS installed-PWA case**.

### Rejected approach 2 — external `window.open`

A corrective attempt used a capture-phase handler and `window.open(binaryUrl, '_blank')` to try to move the binary response into an external context.

Manual iPhone testing again reproduced the same old preview behavior. This approach is also **REJECTED** and must not be treated as an accepted generic PWA solution. It additionally risks authenticated-session differences between standalone PWA and external browser contexts.

### Current corrective — authenticated fetch + Web Share File handoff

The current implementation keeps the behavior Muasamcong-scoped through:

```text
Modules/ClientPortal/resources/views/applications/muasamcong/partials/external-file-handoff.blade.php
```

In installed/standalone PWA mode:

1. Excel/PDF link navigation is intercepted before top-level navigation.
2. The existing protected same-origin URL is fetched with `credentials: 'same-origin'` and `cache: 'no-store'`.
3. The binary response is converted to a temporary `Blob` / `File` in the current authenticated PWA context.
4. The UI shows a ready state and an explicit **Mở / Chia sẻ tệp** button.
5. A second user tap calls `navigator.share({ files: [...] })`, preserving the user-activation requirement of Web Share.
6. If native file sharing is unsupported, the PWA stays intact and shows a safe fallback instruction instead of navigating to the binary URL.

Normal desktop/browser behavior remains native because interception is gated to installed/standalone PWA mode.

The old hidden-iframe runtime helper has been removed from the Price List workspace partial. The current handoff code contains neither binary `window.open` nor top-level `window.location` fallback.

The implementation remains application-scoped for MR-5. It is not promoted into a speculative shared ClientPortal component until another active application needs the same primitive or an approved MR requires promotion.

## Project-wide documentation contract

Canonical guidance:

```text
docs/PWA_EXTERNAL_FILE_HANDOFF.md
```

It now records:

- the requirement to preserve the top-level installed-PWA workspace;
- authenticated same-origin fetch and private-cache boundaries;
- Web Share file handoff with a second explicit user gesture after asynchronous preparation;
- hidden iframe and `window.open(binaryUrl, '_blank')` as empirically failed iOS patterns in MR-5;
- safe unsupported-platform fallback behavior;
- iOS/Android/desktop acceptance requirements;
- the boundary that web code cannot force a native viewer to provide a "Back to PWA" control.

`docs/GITHUB_COLLABORATION_WORKFLOW.md` contains the mandatory PWA download/open-file gate. Future Module work involving PWA-capable file actions must read the canonical document and verify applicable iOS/Android/desktop behavior before merge.

## Automated coverage

Focused test:

```text
tests/Feature/ClientApps/ClientPortalPwaExternalFileHandoffTest.php
```

The corrective contract now locks:

- installed/standalone PWA detection;
- Excel and PDF handoff markers;
- authenticated `fetch` with `cache: 'no-store'`;
- Blob/File construction;
- `navigator.canShare` / `navigator.share` native file handoff;
- explicit second user action;
- absence of hidden iframe, binary `window.open`, and top-level location fallback in the current handoff implementation;
- project-wide workflow/documentation contract.

Historical pre-Web-Share checkpoint reported by owner:

```text
ClientApps: 87 passed (598 assertions)
```

That result predates the current corrective implementation and does **not** validate the new Web Share code.

Current corrective automated status: **NOT YET RUN by owner**.

## Manual acceptance required

Before MR-5 can be marked completed, verify at minimum:

```text
iPhone installed PWA
- tap Excel: PWA shows file preparation/ready UI instead of replacing the workspace
- tap “Mở / Chia sẻ tệp”: native share/open sheet appears
- cancel or complete handoff: Price List workspace remains recoverable
- repeat for PDF

Android installed PWA
- Excel/PDF handoff does not destroy application navigation context
- Web Share file handoff works, or a safe unsupported message is shown

Desktop / ordinary browser
- Excel/PDF native download behavior still works

Security
- protected file routes still require the same authenticated session/permissions
- no public URL or private service-worker cache was introduced
```

If a native viewer itself has no visible return button, that is not by itself a failure; the acceptance target is that the PWA page remains alive and recoverable when the user returns to the app.

## Roadmap checkpoint

```text
MR-1 — Portal Architecture Foundation: MERGED / CLOSED
MR-2 — Adaptive Navigation: MERGED / CLOSED
MR-3 — Dynamic Portal Home: MERGED / CLOSED
MR-4 — Muasamcong reference migration: MERGED / CLOSED
MR-5 — PWA External File Download & Return UX: IN PROGRESS
```

## Next-step boundary

MR-5 Web Share corrective implementation is present on `fix/clientportal-pwa-external-file-handoff`, but the branch is not PR-ready until focused automated tests, ClientApps regression, applicable manual PWA/browser acceptance and Git-clean verification are reported.
