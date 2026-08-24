# Reporting, Export, and Template Packages

## 1. Reporting scope

V1 reporting is operational, bounded, and explainable. It does not become a general BI engine and does not scan arbitrary JSON paths on demand.

Required metrics with explicit date/type/status filters:

- request counts by status/type/group;
- submissions, approvals, rejections, returns, cancellations over time;
- current backlog and active task count;
- median/percentile completion time only where database/report implementation can compute it safely;
- approval workload and completion count by user/role with authorization and privacy controls;
- return/reject rate by request type;
- operational failure counts for outbox/notification/export.

No metric is labeled SLA compliance in v1 because no formal SLA engine exists.

## 2. Reporting data rules

- Report dates are defined (submitted, terminal, or created) and displayed in configured local timezone while stored/calculated from UTC.
- Status counts use canonical request/run/task states and avoid double-counting resubmission runs unless the metric explicitly reports runs.
- The UI labels `requests` vs `runs` vs `tasks` clearly.
- Report queries use indexed columns/approved projections. Arbitrary schema field reporting is excluded unless a field is explicitly declared reportable and a safe projection/index design is approved.
- Permissions and data scopes apply before aggregation; counts must not reveal hidden groups/types/users.
- Small-count suppression or equivalent privacy control should be considered for user-level reports.

## 3. Report workspace

- Visible date range, type/group/status filters and Reset.
- Summary cards plus at most a few useful comparisons/trends.
- Accessible data table accompanies any chart.
- Loading/empty/error and timezone/last-refreshed labels.
- Drill-down only into the same authorized filtered record scope.
- URL filters contain no sensitive payload values.

## 4. Export catalog

### 4.1 Request register export

Authorized rows with selected safe columns such as number, type, requester display, status, submitted/terminal times, current stage, and completion duration. Dynamic field columns are opt-in, classification-filtered, and bounded.

### 4.2 Approval/task export

Authorized stage/task/decision rows with actor/time/outcome and safe reason inclusion policy. It distinguishes effective task, replacement, skipped/cancelled task, and run sequence.

### 4.3 Audit export

Auditor-only safe event columns. It does not export raw audit context indiscriminately.

### 4.4 Request detail document

Private printable/PDF representation of one authorized request, pinned form snapshot, run/stage outcomes, and selected audit/timeline information. It includes generation time/version and excludes non-approved confidential content.

### 4.5 Request Type definition package

Sanitized versioned package for backup/transfer to another environment as a draft after explicit mapping/validation. This is not a live request export.

## 5. Export execution

- Interactive preview is strictly bounded.
- Large export creates an authorized queued job with immutable filter/field snapshot.
- Worker revalidates module/config and operates under the captured authorization scope design chosen in `CREATE_PLAN.md`; download always reauthorizes the current user.
- Artifact is stored privately with random opaque path, checksum, format, row count, expiry, and safe status.
- Completion sends minimal in-app/email notification with protected link.
- Expired/failed artifacts are cleaned through bounded operational maintenance.
- Retry regenerates delivery artifact idempotently; it never replays a request decision.

## 6. Formats

Reuse the repository-approved Shared import/export foundation rather than introducing an unrelated package. `CREATE_PLAN.md` must inspect its actual supported formats and choose only formats proven in the current repository.

Preferred intent:

- CSV/XLSX for tabular registers if Shared supports them safely.
- PDF or print HTML for single-request human document if an approved renderer exists.
- JSON/ZIP-like package only for Request Type definition packages with strict archive safety.

No format is promised merely because a generic prompt mentions it. Missing runtime libraries are implementation-plan dependencies, not permission to add them silently.

## 7. Import boundary

Supported import is limited to a Request Type definition package and creates a draft after dry-run, diff, and explicit user/role mapping. It never:

- imports live request instances/runs/tasks/decisions;
- publishes automatically;
- creates users/roles;
- resolves a user by unverified email/name;
- changes a published version;
- calls another domain module;
- executes package content.

## 8. Template governance

The templates in `13-REQUEST_TEMPLATE_CATALOG.md` are starter examples, not hard-coded business logic. Installing one creates an editable draft through the same validation/publication path.

Each template declares:

- stable template key and version;
- suggested group/type metadata;
- form schema and classifications;
- approval-stage placeholders using supported resolvers;
- assumptions and required mapping;
- localization keys;
- checksum/license/source metadata if imported.

Fixed users/roles are never silently bound by name. Template application asks for explicit mappings and remains draft.

## 9. Export acceptance

- IDOR and permission tests at create/status/download/expiry routes.
- Field classification and row-scope tests.
- Formula injection, XSS, PDF remote-fetch, filename, and archive traversal tests.
- Concurrent duplicate export idempotency and retry tests.
- Artifact private-storage and expiry/cleanup verification.
- Row/count/checksum consistency against authorized source query.
- Report counts distinguish requests, runs, and tasks under return/resubmit.
