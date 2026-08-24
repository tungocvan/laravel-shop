# UI/UX, Responsive, and PWA Specification

## 1. Experience principles

- Show the next useful action and the context needed to take it safely.
- Optimize requester/approver flows for one hand and interrupted mobile use.
- Optimize structural design for tablet landscape and desktop.
- Prefer readable stage lists, timelines, cards, and explicit controls over graph spectacle.
- Make state, saving, connectivity, staleness, authorization, and consequences visible.
- Reuse the repository Admin shell and design tokens; Request is not a second design system.

## 2. Information architecture

Requester/approver navigation:

- Request dashboard
- Create request / catalog
- My Requests
- Approval inbox
- Saved drafts

Administration navigation, permission-gated:

- Request groups and types
- Draft/version designer
- Reports and exports
- Operations

Navigation badges use bounded counts and never require an unbounded synchronous query.

## 3. Screen inventory

### 3.1 Dashboard

- Primary actions: Create request and Open inbox.
- Cards: drafts, pending own requests, returned requests, active assigned tasks.
- Recent activity is bounded and policy-filtered.
- No vanity chart blocks above urgent work on phone.

### 3.2 Request catalog

- Search, group chips/list, eligibility state, concise type cards, recent/favorite types only if safely implemented.
- Each card shows name, summary, group, availability, and one clear Create action.
- Ineligible types are hidden or explain safely according to product policy; never leak restricted type details.

### 3.3 Create/edit request

- Page header: type name, draft/save state, connectivity.
- Sections use progressive disclosure with a visible validation summary.
- Autosave is debounced and revision-aware; manual Save remains available.
- Sticky mobile footer exposes Save and Review/Submit without covering content or keyboard.
- Review step summarizes normalized values, attachments, approval stages, and irreversible submit semantics.
- Errors link/focus the exact field; server errors remain after rerender.

### 3.4 My Requests

- Filters: search/number, type/group, status, date range; Reset is visible.
- Page sizes are bounded at 10/25/50/100 where appropriate.
- Phone uses compact cards; tablet/desktop may use a table with visible row actions.
- Status, current stage, updated time, and returned reason indicator are scannable.

### 3.5 Approval inbox

- Default sort prioritizes oldest active task unless product config says otherwise.
- Filters: search/number, type, stage, mode, submitted date, requester where authorized.
- Card/row shows requester, type, request number, submitted time, current stage, and safe key context—not the whole payload.
- Bulk approval is excluded in v1 because each decision requires contextual review.
- Opening a cached/stale task refreshes before action controls activate.

### 3.6 Request detail/decision

- Header: number, type/version, requester, status, current stage, last update.
- Tabs/sections: Request data, Approval, Timeline, Comments, Attachments; on phone use a compact segmented control or accordions.
- Approval section groups runs and stages; active task is prominent.
- Sticky decision bar on mobile contains Approve, Reject, Return with distinct hierarchy and accessible labels.
- Approve shows a concise confirmation; Reject/Return open a reason sheet/dialog with validation.
- Any version conflict/stale task closes the confirmation and refreshes authoritative state.

### 3.7 Type designer

- Tablet/desktop primary; phone displays read-only summary and a message to use a larger viewport for structural editing.
- Metadata, Audience, Form, Approval, Policies, Validation, Versions sections.
- Ordered stage cards show position, name, mode, resolver, candidate summary, and explicit move/duplicate/delete controls.
- Drag-and-drop has keyboard/button alternatives.
- Version diff and publish confirmation are first-class screens.

### 3.8 Operations/reports

- Filters and tables follow repository admin standards with bordered controls and bounded pages.
- Failures show safe error code, entity reference, attempts, last time, and allowlisted Retry when authorized.
- Charts appear only when they materially aid comparison; source counts and date filters remain visible.
- Export uses a review/confirmation and asynchronous status for large data.

## 4. Responsive behavior

### Phone

- Single content column; cards instead of wide tables.
- Full-width primary controls and bottom sheets for filters/decisions where accessible.
- Sticky actions respect safe areas and on-screen keyboard.
- No page-level horizontal scroll. Long identifiers wrap/copy safely.
- Tap targets meet the adopted accessibility standard; destructive/terminal actions are spatially distinct.

### Tablet

- Two-pane inbox/detail where space permits.
- Designer supports stage list plus properties panel in landscape.
- Touch and keyboard both work; no desktop-hover dependency.

### Desktop

- Dense but readable table/workspace layout using `Admin::layouts.master`.
- Optional split view for inbox/detail and builder/properties.
- Preserve a logical keyboard focus sequence across panes.

## 5. Component contract

Prefer existing repository/shared components for:

- page headers, breadcrumbs, cards, alerts, badges;
- bordered text/select/date controls and validation messages;
- search/filter/reset/pagination toolbars;
- confirmation modal/drawer;
- empty/loading/error states;
- file upload/progress where an approved shared component exists;
- shared import/export panel for definition package workflows where its contract fits.

New Request components must be domain-focused (request status badge, stage progress, decision panel, dynamic field renderer) and must not duplicate shell layout or global controls.

## 6. State language

Every remote surface supports:

- initial loading and incremental loading;
- empty with a useful next action;
- validation error and recoverable system error;
- permission loss/not found without data leakage;
- saving/saved/unsaved/conflict;
- online/offline/reconnecting/stale snapshot;
- success only after server confirmation.

Status color is never the sole cue. Icons/labels have accessible text. Terminal decisions include actor/time/reason as authorized.

## 7. Accessibility

- Semantic landmarks, headings, lists, tables, buttons, and form labels.
- Keyboard access to all actions including stage reordering and dialogs.
- Visible focus and reliable focus restoration after Livewire updates/dialog close.
- Error summary links to fields; `aria-describedby` associates help/errors.
- No focus trap outside active modal/drawer; Escape behavior is consistent.
- Contrast and reduced-motion compliance; animations do not communicate the only state change.
- Screen-reader announcements for save, submit, decision, conflict, upload, and connectivity changes without excessive chatter.
- Localized Vietnamese copy remains concise and does not rely on untranslated identifier text.

## 8. PWA ownership

The application Shell owns:

- web manifest, installability, icons, service worker registration/update;
- navigation fallback/app shell;
- global authentication/offline lifecycle;
- cache versioning and eviction infrastructure.

Request only registers namespaced cacheable resources/read models through an approved Shell contract if one exists. It must not create a service worker, web manifest, global fetch interceptor, or general cache.

## 9. Offline data classes

| Data | Offline rule |
|---|---|
| Static shell/assets | Shell policy may cache |
| Catalog summary | Optional sanitized, per-user, expiring read snapshot |
| My Requests/inbox summary | Optional minimal expiring snapshot; clearly stale/read-only |
| Request detail | Optional only for approved non-confidential fields; default deny |
| Non-sensitive draft values | Optional encrypted-at-rest where platform contract supports it; versioned and expiring |
| Confidential fields | No offline persistence by default |
| Attachment binaries/URLs | Never cache/persist in v1 |
| Decisions/comments/uploads/submits | Never queue offline |

Browser storage is not considered a secure secret store. The type/field policy and server response determine cacheability; the client cannot upgrade it.

## 10. Draft synchronization

Local draft envelope contains request/type/version, owner ID, schema version/checksum, base server revision, allowed field subset, timestamps, and local revision. It contains no auth token, attachment binary, role/user display cache beyond minimum safe needs, or confidential field excluded by policy.

On reconnection:

1. Authenticate and validate same user/module access.
2. Fetch current server revision and pinned schema.
3. If unchanged, show the values to synchronize and require an intentional save/review.
4. If divergent, present field-level conflict choices for allowed fields or require a new copy.
5. Server validates and saves a draft only; no auto-submit.
6. Clear/advance local envelope after confirmed server save.

## 11. Cache invalidation and privacy

- Cache/storage keys include environment, authenticated stable user, Request schema/cache version, and resource key.
- Clear on logout, account switch, module disable response, authorization failure/revocation, version incompatibility, expiry, or explicit Clear local data.
- Sensitive pages use appropriate cache-control headers.
- Notifications and deep links do not put request payloads/reasons in URL query strings.
- Shared-device warning and local-data controls are visible when offline drafts are enabled.

## 12. UX acceptance journeys

Manual and automated responsive QA must cover:

1. Create a multi-section request on a narrow phone, leave/resume, review, submit online.
2. Open inbox offline and confirm stale read-only state and disabled decisions.
3. Reconnect, refresh task, approve/reject/return with confirmation and reason validation.
4. Handle task already decided in another tab/device.
5. Edit returned request and distinguish old/new run history.
6. Design/reorder/validate/publish a type on tablet landscape using touch and keyboard.
7. Logout/account switch and verify Request cached data/drafts are cleared.
