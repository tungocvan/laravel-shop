# Operations, SLA, Queues, and Notifications

## 1. Operational goals

The engine must recover from restarts, duplicated deliveries, transient failures, and worker concurrency without losing or repeating a business decision. Operators need visibility and safe recovery, not direct database/token editing.

## 2. Queue declarations

Workflow declares required queues in `config/module.php`, for example:

```text
workflow-engine       short continuation/subflow work
workflow-timers       due timers, reminders, escalation
workflow-notifications notification delivery
workflow-exports      large private report/definition exports
```

Final names/worker counts/timeouts/tries are approved in CREATE_PLAN. Jobs implement `ShouldQueue`, are idempotent, use explicit queue names from trusted configuration, and carry stable resource/event IDs rather than large model snapshots or secrets.

## 3. Scheduler/commands

Proposed commands:

```text
workflow:timers:process
workflow:outbox:dispatch
workflow:instances:detect-stuck
workflow:audit:verify
workflow:cleanup
```

Commands accept fixed typed allowlisted options, process bounded chunks, coordinate with locks/leases, return meaningful exit codes, and support dry-run for detection/cleanup where applicable. Scheduling follows existing System conventions; Workflow does not create a competing scheduler manager.

## 4. SLA model

SLA can be defined at definition/node level:

- duration and unit
- start event
- calendar mode: 24x7 MUST; business calendar SHOULD
- due behavior
- reminder offsets
- escalation levels and targets
- pause/resume policy for returned/waiting states
- timezone and rounding policy

Due timestamps are calculated and stored when work activates. Policy changes in later definition versions do not change active tasks unless an explicit audited migration is designed.

## 5. Timer processing

1. Select due pending timers in bounded order.
2. Acquire lease/row lock and mark processing.
3. Execute an idempotent timer command using timer key.
4. Commit task/escalation/audit/outbox changes.
5. Mark fired, retry with backoff, or dead after bounded attempts.

Multiple workers must not fire the same timer twice. Lease expiration permits recovery after worker death.

## 6. Reminder and escalation

- Reminders notify current effective assignee/candidates without changing authority.
- Escalation may add an allowlisted role/resolver candidate, reassign, or alert operator according to published policy.
- Escalation never selects arbitrary users/classes/addresses from client data.
- Each level fires once via unique key.
- Missing escalation actors fails visibly and alerts operator; it does not auto-approve.

## 7. Notification delivery

Initial channels:

- database notification
- email when mail is configured

Template variables are allowlisted, escaped, and derived from safe event projections. Notifications link to authorized routes and do not embed sensitive payload/attachments. User preferences may suppress optional reminders but not mandatory security/assignment notices when policy requires them.

Delivery status is independent of workflow decision state. Failed mail retries without replaying approvals/tasks.

## 8. Observability

Metrics/logs should cover:

- active/waiting/failed instances
- task backlog and overdue age
- timer due/fired/failed lag
- outbox pending/failure/dead count and oldest age
- job duration/retry/failure by queue
- command duration and engine steps
- resolver empty/unavailable counts
- definition validation/publication failures
- notification channel success/failure

Structured logs include correlation ID, safe aggregate public ID, event/job ID, definition/version and error code. They exclude raw sensitive payload, secrets, file bytes, tokens and stack traces from user-facing surfaces.

## 9. Stuck detection and recovery

An instance is potentially stuck when running/waiting state has no valid active token/task/timer/subflow, an activation exceeds timeout, or continuation/outbox remains failed past threshold. Detection is read-only and creates an operational incident/projection.

Safe operator actions:

- retry failed outbox message
- retry failed timer/job by stable ID
- resume an engine continuation from recorded activation key
- cancel instance when business policy and permission allow

Each action revalidates state/idempotency, requires reason/confirmation, and audits outcome. No UI operation changes raw status/token/node fields directly.

## 10. Storage and cleanup

- Private attachments follow business retention.
- Temporary uploads/exports have short configurable retention and idempotent cleanup.
- Outbox/idempotency/audit retention is explicit and cannot break retry/evidence requirements.
- Cleanup operates in chunks, skips legal/active records, and supports dry-run/report.
- Docker entrypoint/storage ownership supports both root CLI and `www-data` PHP-FPM without world-writable permissions.

## 11. Disable/maintenance behavior

Runtime module state uses platform abstractions. Before disabling in production, operators must understand running-instance impact. CREATE_PLAN must choose a safe policy, recommended:

- block new user/API entry points
- let already-dispatched internal jobs fail closed or complete only through an explicitly supported maintenance path
- retain all data
- provide pre-disable report of active instances/tasks/timers

Runtime toggle never edits tracked manifest and must leave Git clean.

