# PWA External File Handoff

## Purpose

This document defines the project-wide rule for opening or downloading generated files from an installed PWA or mobile web-app context.

The problem is not limited to ClientPortal or Muasamcong. Any Module that exposes Excel, PDF, CSV, images, reports, exports, attachments or other binary responses can trigger the same navigation failure if the current PWA page is replaced by a file response or native document preview.

## Core rule

An installed PWA must not use top-level navigation to an authenticated binary download when that navigation can replace the active application workspace.

The application should preserve its current browsing context while handing the file to the browser/OS download, share or preview mechanism.

```text
PWA workspace
    ↓ user requests file
Preserve top-level application page
    ↓
Authenticated same-origin file request
    ↓
Native file handoff / share sheet
    ↓
User returns to PWA
    ↓
Original workspace is still present
```

## Why this matters

On mobile platforms, especially iOS, direct navigation from an installed PWA to an XLSX/PDF response can replace the PWA document with the system document preview. Once the native viewer owns the screen, the web application cannot guarantee a visible "Back to PWA" control.

The correct design goal is therefore not to control the native viewer. The goal is to avoid destroying or replacing the PWA workspace before the file is handed off.

## Authenticated downloads

Do not solve this by blindly forcing the URL into an external browser window. Installed PWA and browser cookie/session behavior can differ by platform, and a protected URL may not have the same authenticated session outside the PWA context.

For same-origin authenticated files, the preferred installed-PWA pattern is:

```text
user taps file action
    ↓
PWA intercepts the navigation
    ↓
authenticated fetch(credentials: same-origin, cache: no-store)
    ↓
Blob → File
    ↓
show an explicit ready action inside the PWA
    ↓ user taps again
navigator.share({ files: [...] })
    ↓
OS share/open sheet
```

The second user gesture is intentional. Web Share requires transient user activation on relevant browsers, so code must not assume that a user activation remains valid after an asynchronous file fetch finishes.

Use `navigator.canShare({ files: [...] })` when available before enabling the native file-share action. If file sharing is unsupported, keep the installed PWA workspace intact and show a clear fallback instruction instead of navigating the PWA document to the binary URL.

Do not expose a protected file through a new public URL merely to work around PWA navigation.

## Known failed patterns on iOS installed PWA

The following patterns are not accepted as a generic solution unless a future platform-specific test proves otherwise:

- **Hidden iframe to the binary URL.** iOS may still hand the XLSX/PDF response to its native preview and occupy the standalone PWA context. This was reproduced manually in ClientPortal MR-5.
- **`window.open(binaryUrl, '_blank')`.** This does not reliably create a recoverable external context from an installed iOS PWA, may still lead to the same native preview behavior, and may introduce session-sharing problems if an external browser context is involved. This was also reproduced manually in ClientPortal MR-5.
- **Top-level `window.location` fallback.** This intentionally replaces the PWA workspace and therefore violates the core rule.

These failed patterns must not be reintroduced in another Module merely because they work in a desktop browser.

## Normal browser behavior

Desktop and ordinary browser navigation should remain native unless there is a demonstrated need to override it. PWA-specific interception should be gated to installed/standalone display mode where possible.

Typical detection:

```js
window.matchMedia('(display-mode: standalone)').matches
    || window.navigator.standalone === true
```

Do not assume user-agent sniffing is sufficient.

## Security boundary

File handoff must preserve the existing authorization contract:

- authenticated routes stay authenticated;
- Module/application/feature permission checks remain server-side;
- file existence checks remain server-side;
- private file responses must not be broadly cached by the service worker;
- no permanent public URL is created as a convenience workaround;
- UI visibility is not a substitute for authorization.

An authenticated fetch used for file handoff must use the existing protected route and preserve the same-origin session. The client-side Blob/File is only a temporary handoff object; it is not a new authorization boundary.

## Service worker boundary

Authenticated binary responses should not be added to a broad offline cache. A file download request should normally pass through to the network unless a separately reviewed secure offline-file design explicitly requires otherwise.

## Acceptance checklist

For any Module that adds or modifies file download/open behavior in a PWA-capable client surface, manually verify the applicable cases:

```text
iOS installed PWA
- tapping the file action does not replace the current workspace
- authenticated file preparation succeeds inside the existing PWA session
- the native share/open sheet appears only after an explicit user action
- Excel/PDF can be handed to Files, Excel or another compatible application
- cancelling or completing the handoff leaves the PWA workspace recoverable

Android installed PWA
- download/open does not destroy the application navigation stack
- supported Web Share file handoff works, or a safe fallback is shown
- returning to the PWA preserves the current page/context

Desktop / normal browser
- native download behavior still works

Security
- protected files still require the same session/permissions
- no private file is made public
- service worker does not broadly cache the private binary response
```

If the affected platform cannot provide an in-viewer return button, this alone is not a failure provided the PWA workspace remains alive and recoverable when the user returns to it.

## Debugging checklist

When a user reports that opening a file prevents returning to the PWA, inspect in this order:

1. whether the action is a normal `<a href="...">` that navigates the top-level PWA document;
2. whether the response is `Content-Disposition: attachment` or inline preview;
3. whether the route is authenticated and must retain the PWA session;
4. whether the service worker intercepts or caches the request;
5. whether the platform is installed PWA, mobile browser or desktop browser;
6. whether the implementation preserves the original application document before handing off the file;
7. whether Web Share supports file payloads on the affected device;
8. whether the share call is made from a fresh user gesture rather than after an asynchronous fetch without another tap.

Do not start by changing storage permissions, file generation, route authorization or service-worker caching unless evidence points there.

## Ownership

A Module/application may implement the smallest local handoff needed for its current file action. Promote the behavior into a shared ClientPortal primitive only when more than one active application needs the same contract or an approved MR explicitly requires the shared abstraction.

This avoids both duplicated platform bugs and speculative shared-component work.
