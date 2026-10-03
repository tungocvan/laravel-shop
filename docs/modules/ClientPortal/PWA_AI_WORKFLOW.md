# ClientPortal / PWA — AI Workflow Gate

Updated: 2026-09-29

## Purpose

This is the mandatory AI entry point for work involving ClientPortal/PWA. It exists so a new chat does not rediscover the architecture, regress established mobile/PWA UX, hard-code configurable presentation, or bypass the repository GitHub workflow.

## Trigger

Read and apply this document **before code analysis or implementation proposals** whenever a task touches any of:

- `/my-apps`, `/my-apps/login`, `/apps/*`;
- `Modules/ClientPortal`;
- a ClientPortal application adapter or manifest;
- installed PWA/mobile Client UX;
- `/admin/client-apps`, PWA settings, Application & Feature presentation;
- file download/open/share behavior from a PWA-capable client surface.

A new chat must not rely only on prior conversation memory. Re-read the repository documents from the current target branch because the contracts may have changed.

## Mandatory reading order

Before inspecting implementation code, read:

```text
docs/GITHUB_COLLABORATION_WORKFLOW.md
docs/modules/ClientPortal/PWA_AI_WORKFLOW.md
docs/modules/ClientPortal/README.md
docs/modules/ClientPortal/MODULE.md
docs/modules/ClientPortal/COLLABORATION_HANDOFF.md
docs/modules/ClientPortal/PWA.md
docs/modules/ClientPortal/PWA_ADMIN_SETTINGS.md
docs/modules/ClientPortal/PWA_APPLICATION_STANDARD.md
.codex/standards/ADMIN_UI_STANDARD.md
```

Then inspect the current manifest, routes, controllers/services, Blade/Livewire views and relevant tests from the target branch. Documentation is a contract/guide, but current source and tests must be checked for drift before changing code.

## Conditional reading

Read these when the scope applies:

- `docs/PWA_EXTERNAL_FILE_HANDOFF.md` — **mandatory** for download/open/share/export/attachment/binary behavior on a PWA-capable surface.
- `docs/modules/ClientPortal/PRICE_LIST_EXCEL.md` — Excel/PDF Price List rendering, profile, artifact or conversion work.
- `docs/modules/ClientPortal/DEBUG_NOTES.md` — regressions, 404/500, Queue, storage, polling, responsive action failures.
- `docs/modules/ClientPortal/FUNCTIONS.md` and `INFORMATION.md` — detailed functional/runtime map when changing an existing workflow.
- `docs/modules/ClientPortal/ANALYSIS.md` — architecture/refactor backlog snapshot. Treat findings as dated assessment; verify against current source before acting.
- feature closeout documents such as `*_CLOSEOUT.md` — only when modifying that feature or tracing historical decisions.

For production/Docker issues, also follow the production gate in `docs/GITHUB_COLLABORATION_WORKFLOW.md`.

## Architecture invariants

Preserve these boundaries unless an explicitly approved architecture change says otherwise:

```text
ClientPortal -> source domain module/service
source domain module -X-> ClientPortal
```

ClientPortal owns Client/PWA presentation, launcher/login shell, client permissions and client-specific workflow state. Domain modules own canonical business data and business rules. Prefer thin ClientPortal adapters over duplicating domain logic.

Client permissions use the `web` guard. Admin management remains independent under the `admin` guard. UI visibility is never a substitute for server-side authorization.

Authenticated navigation must not be broadly cached as reusable private HTML by the service worker.

## Project-wide application standard

For every PWA Module/application — including new applications and refactors outside Pharma — apply:

```text
docs/modules/ClientPortal/PWA_APPLICATION_STANDARD.md
```

This standard is mandatory for Application Hub structure, managed hero/supporting content, configurable Bottom Navigation, Overview evolution, searchable entity selectors and iPhone/iOS-safe date controls. Module-specific implementations may add stricter rules but must not silently diverge from the shared contract.

## PWA UI contract

PWA work is mobile-first and must preserve the installed-app context.

Before declaring UI work complete, verify representative mobile and desktop widths and the actual rendered UI. Check hierarchy, spacing, overflow, touch targets, loading/error/disabled states, action visibility, navigation continuity and permission-dependent content.

Do not introduce desktop-only patterns that degrade one-hand/mobile use. Reuse the existing ClientPortal shell and established components/patterns before creating new ones.

For long lists on mobile, preserve the established native-like progressive loading pattern where the feature already uses it rather than reintroducing desktop pagination mechanically.

## Hub -> Capability -> Focused Workspace navigation contract

Treat the ClientPortal application page (for example the Pharma PWA page headed `Không gian làm việc Pharma`) as the **Application Hub / App Shell**. It is the place where the User discovers permitted capabilities. The Hub keeps the application-level header/navigation.

After the User enters a capability from that Hub, distinguish browse/index screens from task/workspace screens:

- **Application Hub**: keep the global application shell.
- **Pharma capability browse/index**: after entering a capability from `/apps/pharma`, hide the application header and mobile bottom navigation. Provide a local route back to the Pharma Hub; the Hub remains the canonical place for switching capabilities.
- **Focused task/workspace** (create, edit, detail that drives a task, approval, allocation, policy, assignment, wizard): also hide the application header and mobile bottom navigation. Provide a local back affordance, local task title/context and task-specific actions instead.
- Do not mechanically render both global application navigation and local task navigation on a focused screen.
- For Pharma PWA, do not restore the global capability navigation inside a capability merely because a screen is an index/list. Return to `/apps/pharma` to switch capabilities.

For Blade screens using `ClientPortal::layouts.application`, the established focused-screen sections are:

```blade
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
```

The exact UI may evolve, but the architectural rule is stable: **Hub for discovery/navigation; focused workspace for completing a business task.**

When auditing or creating a PWA capability, explicitly classify every route/screen as `Hub`, `Browse/Index`, or `Focused Task/Workspace` before changing its shell. Verify the classification on Mobile/PWA and Desktop during the real UI acceptance gate.

## Managed page content — no hard-coded feature hero copy

Every user-facing routable PWA feature must expose default page presentation in its application manifest:

```php
'eyebrow' => '...',
'page_title' => '...',
'page_description' => '...',
```

The defaults must flow through:

```text
application manifest
    -> ApplicationRegistry
    -> ClientPortalSettingsService::featurePresentation()
    -> controller
    -> Blade
```

Admin must be able to manage these values at:

```text
/admin/client-apps
-> Giao diện Application & Feature
-> Nội dung trang PWA
```

Do not duplicate the configurable eyebrow/title/description as hard-coded Blade copy. New PWA feature work is incomplete until the manifest defaults, Admin edit path, runtime consumption, automated contract tests and real UI acceptance are present.

Presentation settings must not make route names, permissions, controller classes, source-module boundaries or business logic Admin-editable.

## File handoff gate

If the task touches Excel, PDF, CSV, image, report, attachment or another binary open/download action, stop and read:

```text
docs/PWA_EXTERNAL_FILE_HANDOFF.md
```

Do not use top-level navigation, hidden iframe or `window.open(..., '_blank')` as a generic installed-PWA solution for authenticated files. Preserve the workspace and the existing authorization/session boundary. Required platform acceptance follows the handoff document.

## GitHub collaboration gate

All ClientPortal/PWA work follows `docs/GITHUB_COLLABORATION_WORKFLOW.md`:

```text
read mandatory docs
-> inspect current GitHub branch/source/tests
-> architecture/root-cause analysis
-> plan/approval when required
-> feature branch
-> coherent implementation batch
-> git pull --ff-only
-> focused tests
-> ClientApps/impacted regression
-> real UI acceptance
-> git-clean verification
-> update COLLABORATION_HANDOFF.md
-> PR/review
-> explicit merge approval
```

For UI work, automated tests are not sufficient. Real UI acceptance is a merge gate.

Do not merge merely because tests pass. Do not merge without the user's explicit approval.

## New-chat checklist

Before answering a new-chat request to develop or debug ClientPortal/PWA, the AI should be able to state internally:

```text
[ ] I read the current GitHub collaboration workflow.
[ ] I read this PWA AI workflow gate.
[ ] I read ClientPortal README, MODULE, handoff, PWA, PWA Admin settings and PWA Application Standard.
[ ] I read ADMIN_UI_STANDARD.
[ ] I read PWA_EXTERNAL_FILE_HANDOFF if files/downloads are involved.
[ ] I inspected the current target branch source and relevant tests.
[ ] I identified domain ownership and permission boundaries.
[ ] I checked whether page copy belongs in /admin/client-apps instead of Blade.
[ ] I know the focused + regression + manual UI gates before PR.
```

If any applicable item is missing, complete it before implementation.
