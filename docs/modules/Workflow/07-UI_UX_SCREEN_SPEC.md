# Admin UI and UX Screen Specification

## 1. UI principles

Follow `ADMIN_UI_STANDARD.md`, `Admin::layouts.master`, current shared components, Tailwind 4 conventions, responsive behavior, visible form boundaries, bounded pagination, and backend authorization.

Workflow is workspace-first. Do not stack designer, inbox, request list, reports, and settings as permanently expanded cards on one page.

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

## 3. Dashboard

Cards: pending tasks, overdue tasks, my drafts, submitted/running requests, completed this period, SLA compliance. Secondary panels: workload by definition, aging buckets, recent activity, failed/stuck operational count. All metrics state their filter/timezone and link to the matching bounded list.

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

## 7. Request detail

Tabs:

1. Information
2. Tasks and approvals
3. Timeline
4. Comments
5. Attachments
6. Audit (permission gated)

Persistent context includes status, requester, definition/version, current step, created/submitted/due timestamps, and available actions. Timeline distinguishes business events, task decisions, notifications, and operational incidents without exposing secrets.

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

## 13. Accessibility and responsive behavior

- Keyboard access for all designer actions, including move/reorder alternatives.
- Visible focus, labels, described validation, adequate contrast, semantic buttons/tables.
- Desktop canvas/workspace uses available width; mobile switches designer panels via tabs/drawers.
- Modals trap focus and return focus on close.
- Status is not communicated by color alone.
- Dates display timezone; stored UTC value may be available to auditors.

## 14. UI smoke matrix

Verify desktop and mobile for menu permissions, inbox filters/pagination, claim/decision, form validation, draft persistence, concurrent conflict, files, timeline, designer validation/publish, delegation, reports, operations, empty/loading/error/success states, and no important console errors.

