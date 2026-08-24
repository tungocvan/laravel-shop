# UI, UX, Responsive, and PWA Screen Specification

> **DEFERRED — Request-first.** Retained as future design reference only; see `docs/modules/Request/ADR-001-REQUEST-FIRST-WORKFLOW-DEFERRED.md`. Current product UI belongs to Request v1.

## 1. UI principles

Follow `ADMIN_UI_STANDARD.md`, `Admin::layouts.master`, current shared components, Tailwind 4 conventions, responsive behavior, visible form boundaries, bounded pagination, and backend authorization.

Workflow is workspace-first. Do not stack designer, inbox, request list, reports, and settings as permanently expanded cards on one page.

The experience must feel operational rather than form-heavy: strong visual hierarchy, short task-oriented copy, progressive disclosure, immediately visible status and next action, and consistent feedback. Responsive composition is based on available space and input mode, not user-agent detection. No essential action may depend only on hover, drag, color, icon, swipe, or toast.

Requester and approver journeys are mobile-first. Definition/form topology design remains fully productive on large tablet and desktop; phone may provide a read-only definition summary, validation result, and explicit guidance to continue on a larger device.

## 2. Navigation

Recommended menu:

```text
Workflow
├── Dashboard
├── Work inbox
├── My requests
├── Create request
├── Definitions
├── Delegations
├── Reports
├── Operations
└── Settings
```

Menu visibility follows capability permissions. Direct route access remains protected independently.

On phone/PWA standalone, use a compact shell with an accessible menu and at most four high-frequency destinations: Home, Inbox, Create, and My requests. Secondary destinations stay in the shell menu according to permission. Do not create a Workflow-owned global navigation shell.

### 2.1 Device responsibility matrix

| Capability | Phone | Tablet portrait | Tablet landscape/desktop |
|---|---|---|---|
| Create/resume request | First-class | First-class | First-class |
| Inbox/task decision | First-class | First-class | First-class |
| Request tracking/detail | First-class | First-class | First-class |
| Reports/operations | Summary and bounded detail | Responsive workspace | Full workspace |
| Definition metadata/validation | Readable summary | Editable panels | Full workspace |
| Graph/form designer | Read-only/device guidance | Panel-based editing when space permits | Full canvas and inspector |
| Publish review | Read-only status on phone | Supported with full diff | Supported with full diff |

## 3. Dashboard

Cards: pending tasks, overdue tasks, my drafts, submitted/running requests, completed this period, SLA compliance. Secondary panels: workload by definition, aging buckets, recent activity, failed/stuck operational count. All metrics state their filter/timezone and link to the matching bounded list.

Phone dashboard prioritizes a single next-action panel, pending/overdue counts, resume-draft action, and recent requests. Secondary analytics move below or behind an explicit details control. Do not render a dense desktop card grid at reduced width.

## 4. Work inbox

Primary task list with:

- keyword search by request number/title/requester
- status, definition, task type, assignment source, due-state, requester, and date filters
- reset filters
- page sizes 10/25/50/100
- tabs for available, claimed, delegated, overdue, completed
- clear candidate/assignee reason and due time
- optional page selection for non-destructive actions; no ambiguous select-all
- empty, loading, permission-denied, and stale-task states

Quick approve/reject is disabled by default for tasks needing full context. Decisions require confirmation where risk warrants, validated reason when required, loading lockout, and stale-version handling.

Desktop may use a table; phone uses task cards or compact rows showing title/request number, requester, current step, due/overdue state, assignment reason, and one clear open/review action. Filters open in a bottom sheet/drawer, show an active-count badge, apply/reset explicitly, and preserve a readable result summary.

## 5. My requests

Tabs: drafts, active, returned, completed, rejected/cancelled. Filters: definition, status, submitted/updated range. Actions are state-aware: edit draft/returned, submit, recall, cancel, duplicate as new draft, view timeline, download authorized attachments.

## 6. Request editor

- Header with definition, request number/draft state, autosave/saved indicator.
- Form sections rendered from published schema.
- Inline validation beside fields and summary on submit.
- Attachment progress and safe failure messages.
- Save draft and submit are distinct actions.
- Return/resubmission shows reviewer feedback and field changes.
- Concurrent edit conflict offers reload/compare, never silent overwrite.
- Phone renders sections as one column with a progress/section navigator, safe-area-aware sticky Save draft/Continue actions, and submit only on the final review step.
- Autosave state distinguishes `saving`, `saved_online`, `saved_locally`, `offline_changes`, `syncing`, `conflict`, and `failed`; it never labels a local-only draft as saved to the server.
- Fields excluded from offline storage are visibly marked when offline. Attachments require online upload and are not retained as offline binary drafts.

## 7. Request detail

Tabs:

1. Information
2. Tasks and approvals
3. Timeline
4. Comments
5. Attachments
6. Audit (permission gated)

Persistent context includes status, requester, definition/version, current step, created/submitted/due timestamps, and available actions. Timeline distinguishes business events, task decisions, notifications, and operational incidents without exposing secrets.

On phone, render a concise context summary before the current task, collapse secondary history, and keep the permitted primary decision action reachable without covering content. Approve/reject/return opens a focused confirmation sheet showing request identity, action, required reason, current revision, and online status. The sheet must refetch/revalidate before commit and surface `409` stale conflicts without guessing the outcome.

## 8. Definition library

Search/filter by name/code/category/status/owner/update date. Show current published version and draft status. Actions: create, edit draft, duplicate, compare, validate, publish, retire, export package. Destructive/retire operations use centered confirmation and impact summary.

## 9. Definition designer

Workspace layout:

```text
Definition header + version/status/actions
├── Metadata
├── Form builder
├── Workflow designer
├── Policies and SLA
├── Validation
├── Simulation
└── Version diff
```

The workflow designer provides node palette, canvas/list fallback, property inspector, transition editor, validation markers, zoom/focus, keyboard actions, and accessible non-drag controls. Graph coordinates are presentation metadata; execution uses stable node/transition keys.

Publishing is blocked on errors. Confirmation displays executable diff, permission impact, resolver/quorum changes, form field changes, and checksum.

Large tablet/desktop uses canvas plus inspector with collapsible regions. Tablet portrait uses one active panel at a time and an always-available outline/list fallback. Phone is not required to support graph topology editing or publication; it displays version/status, validation summary, read-only node list, and device guidance rather than a broken miniature canvas.

## 10. Delegations

Users manage own future/active delegations within policy. Admins with broad permission can manage all. Show delegate, scope, period, status, chain restrictions, and affected definition/role summaries. Create/revoke requires validation and audit confirmation.

## 11. Reports

Filters are explicit and bounded. Initial reports:

- throughput by definition/period
- average/percentile cycle time
- SLA compliance and overdue aging
- backlog by user/role/definition
- decision outcome, return, rejection and recall rates
- task reassignment/delegation counts

Reports avoid exposing sensitive payload fields by default. Export is permission gated, queued for large results, private, and scoped to the applied filters.

## 12. Operations

For authorized operators only:

- failed/waiting/stuck instances
- due/failed timers
- pending/failed/dead outbox messages
- failed Workflow jobs and queue health summaries
- safe retry/resume actions from allowlisted operations
- correlation ID search

No raw token editing, arbitrary command, payload SQL, event class, queue, path, or service invocation is exposed.

## 13. Visual and interaction system

- Reuse shell design tokens and approved components for color, type, spacing, radius, elevation, borders, focus, breakpoints, motion, icons, and feedback. Workflow does not create a parallel theme.
- Establish hierarchy in every workspace: page purpose, status/context, primary action, supporting data, then audit/advanced detail.
- Use status chip plus text/icon, stepper or node summary for progress, due-state emphasis, and a chronological timeline. Color is supplemental only.
- Use plain action labels such as `Save draft`, `Submit request`, `Approve`, `Reject`, and `Return`; avoid ambiguous icon-only controls for business actions.
- One primary action per decision surface. Destructive/high-impact actions include identity/context, consequence, and confirmation.
- Loading uses stable skeleton/placeholder geometry when useful; empty states explain why and the next permitted action; errors stay near the failed context and offer a safe retry.
- Minimum touch target is 44×44 CSS pixels with sufficient separation. Gestures have visible button/keyboard equivalents.
- Motion is brief and functional, honors `prefers-reduced-motion`, and never conveys required state alone.

Reusable Workflow presentation primitives should be composed from shell components rather than copied per page:

| Primitive | Purpose | Required behavior |
|---|---|---|
| `WorkflowStatus` | Request/task/definition state | text + icon/color, accessible name, stable vocabulary |
| `WorkflowProgress` | Current step and completed/remaining path | compact step summary on phone, expanded stepper/node list on larger screens |
| `RequestSummaryCard` | Mobile request/task list item | identity, status, requester, due state, next action; whole card is not an ambiguous button |
| `ContextSummary` | Decision/request header | pins identity, revision, current step, due time, acting/delegation context |
| `TaskDecisionBar` | Approve/reject/return entry point | permission/state aware, safe-area aware, online guard, confirmation/refetch |
| `WorkflowFilterPanel` | Bounded list filters | drawer/sheet on phone, panel on desktop, active count, explicit apply/reset |
| `DraftSyncStatus` | Local/server draft truth | text for local/online/sync/conflict/error state and accessible announcement |
| `WorkflowTimeline` | Evidence/history | chronological grouping, lazy detail, redaction, no color-only event type |
| `ConnectivityBanner` | Offline/stale/reconnect/update/session state | persistent when actionability changes, non-blocking, explicit recovery action |

## 14. Responsive, touch, and accessibility contract

Layouts adapt to content width rather than device names. Reference acceptance widths are 360–430 px phone, 768 px tablet portrait, 1024 px tablet landscape, and 1440 px desktop.

- Phone uses one primary column, cards/compact rows instead of compressed wide tables, drawers/bottom sheets for filters and secondary actions, and safe-area-aware sticky actions.
- Tablet portrait uses split views only when both panes remain usable; otherwise switch explicit panels. Tablet landscape may expose canvas/list plus inspector.
- Desktop canvas/workspace uses available width with bounded readable form/text columns.
- Page-level horizontal scrolling is forbidden. Only an intentional designer canvas/data region may pan/scroll inside a labeled bounded container.
- Do not hide required context solely to make a screen fit. Use summary/detail disclosure and preserve request identity, status, due state, revision, and action consequence.
- Keyboard access is required for all actions, including designer move/reorder alternatives. Focus is visible, follows logical order, and returns to the trigger after a dialog/drawer closes.
- Semantic landmarks, headings, field labels, described validation, accessible names, table headers, live status announcements, and target WCAG 2.2 AA contrast are required.
- Dialogs and blocking sheets trap focus; non-blocking notifications do not steal focus. Status is never communicated by color alone.
- Dates display timezone; stored UTC value may be available to auditors.
- Zoom to 200%, text enlargement, high contrast, screen reader, reduced motion, portrait/landscape rotation, virtual keyboard, and browser safe areas must not block primary journeys.

## 15. PWA ownership and lifecycle

Workflow is an online-first feature within the existing platform PWA. The shell owns the manifest, service worker, install prompt policy, icons, start URL, standalone display, offline fallback, update lifecycle, and global cache names.

Workflow must:

- use an approved shell PWA extension contract and shell navigation;
- expose safe route/app-shell assets for shell caching when required;
- show install/standalone behavior only through shell components;
- detect online, offline, reconnecting, update-available, session-expired, and sync-conflict states;
- provide a persistent but unobtrusive offline/stale banner and an accessible status announcement;
- never register a second service worker, replace the global manifest, invent a second application shell, or depend on a domain module.

An available shell update must not interrupt an in-progress draft. Notify the user, preserve permitted draft state, and apply the update after explicit confirmation or a safe idle/reload point.

## 16. Offline data and synchronization contract

### 16.1 Allowed offline capabilities

- Open the cached application shell and a safe offline route.
- View selected sanitized, previously fetched inbox/request/task summaries as clearly stale and read-only.
- Create or continue a local draft using only schema fields allowed for offline storage.
- Review local-versus-server draft state and explicitly synchronize after reconnection.

### 16.2 Forbidden offline capabilities

Submit, approve, reject, return, complete, claim/unclaim, recall, cancel, reassign, delegate/revoke, publish/retire, upload/download uncached private files, comment, export, operation retry, and any other authoritative mutation require an authenticated online request. They are disabled offline with an explanation and a retry/reconnect action.

The service worker, Background Sync, IndexedDB client, or any custom queue must never enqueue/replay these business commands. Idempotency protects a real online retry; it is not permission to guess or defer a business decision offline.

### 16.3 Local storage model

Use versioned IndexedDB records, not `localStorage`, for permitted dynamic data. Every record includes user identity scope, schema version, definition/version, local ID, optional server public ID/revision, updated time, expiry, and dirty-field metadata.

Never persist authentication/CSRF tokens, attachment binaries or temporary keys, audit/report exports, secrets, unrestricted API responses, fields marked sensitive, or fields whose schema denies offline storage. Clear Workflow local data on logout, account change, access-revocation response, retention expiry, schema incompatibility that cannot be migrated safely, or explicit `Clear offline data` action.

### 16.4 Reconnect flow

1. Reauthenticate and refresh authorization.
2. Fetch the current server draft revision and published schema identity.
3. If compatible and unchanged, let the user explicitly save the local draft online.
4. If changed, show field-level/local-versus-server comparison and require `Keep local`, `Keep server`, or `Copy as new draft` as permitted.
5. Never auto-submit, auto-decide, or silently overwrite either side.
6. After success, refresh the sanitized snapshot and accurately label server/local state.

When connectivity fails after an online mutation was sent, show `Outcome unknown`, refetch by resource/idempotency key, and display the authoritative server result before enabling another attempt.

## 17. Perceived performance and quality

- Render the page shell and meaningful context before secondary charts/history; lazy-load non-critical panels without moving primary actions.
- Preserve filters, scroll position, active tab, and safe unsent input across normal responsive navigation where appropriate.
- Debounce search and autosave, cancel stale reads, bound payload/list sizes, and avoid duplicate requests on reconnect.
- Images/icons/assets are responsive and cacheable through the shell; private payloads and files are not exposed through generic caches.
- Target Core Web Vitals at the 75th percentile on the agreed production-like profile: LCP ≤ 2.5 s, INP ≤ 200 ms, CLS ≤ 0.1. CREATE_PLAN records the test device/network and any justified exception.
- No important console error, unhandled promise rejection, focus loss, duplicate mutation, or stale state presented as current is acceptable.

## 18. UI/PWA smoke matrix

Verify:

- phone 360×800 and 390×844 with touch for create/resume draft, inbox, task context/decision, and request tracking;
- tablet 768×1024 portrait and 1024×768 landscape for requester/approver flows and responsive designer panels;
- desktop 1440×900 with mouse/keyboard for all workspaces and full designer;
- installed PWA standalone on supported Android Chromium and Safari/Add to Home Screen smoke where supported;
- menu permissions, filters/pagination, claim/decision, validation, draft persistence, conflict, files, timeline, designer validation/publish, delegation, reports, and operations;
- empty/loading/offline/reconnecting/stale/update/session-expired/syncing/conflict/unknown-outcome/error/success states;
- keyboard, focus, screen-reader names/status announcements, contrast, zoom, rotation, reduced motion, safe areas, and virtual keyboard behavior;
- Cache Storage/IndexedDB inspection proving no sensitive field, token, attachment binary/key, audit export, raw unrestricted API response, or queued business mutation exists.
