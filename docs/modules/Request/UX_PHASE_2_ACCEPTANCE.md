# Request UX Phase 2 — Final Acceptance

Status: **PHASE 2.14 CORRECTIVE AUDIT IN PROGRESS**  
Checkpoint: `feat/request-ux-phase-2-reports-exports` / `9698c8a8`  
Audit branch: `feat/request-ux-phase-2-final-acceptance-audit`

## Scope matrix

| Slice | Workspace | Automated evidence | Status |
|---|---|---|---|
| 2.1 | Permission-aware Request navigation | `RequestWorkspaceNavigationContractTest` | Implemented |
| 2.2 | Production dashboard | `RequestProductionDashboardContractTest` | Implemented |
| 2.3 | Employee workspace | `RequestEmployeeWorkspaceContractTest` | Implemented |
| 2.4 | Request detail and safe actions | `RequestDetailWorkspaceContractTest` | Implemented |
| 2.5 | Approver pending workload | `RequestApproverPendingWorkspaceContractTest` | Implemented |
| 2.6 | Approver processed history | `RequestApproverHistoryWorkspaceContractTest` | Implemented |
| 2.7 | Admin group workspace | `RequestAdminGroupsWorkspaceContractTest` | Implemented |
| 2.8 | Admin type designer | `RequestAdminDesignerWorkspaceContractTest` | Implemented |
| 2.9 | Definition management | `RequestDefinitionManagementWorkspaceContractTest` | Implemented |
| 2.10 | Version history | `RequestVersionHistoryWorkspaceContractTest` | Implemented |
| 2.11 | Definition package | `RequestDefinitionPackageWorkspaceContractTest` | Implemented |
| 2.12 | Operations and recovery | `RequestOperationsWorkspaceContractTest` | Implemented / gates passed |
| 2.13 | Reports and exports | `RequestReportsExportWorkspaceContractTest`, export feature tests | Implemented / gates passed |
| 2.14 | Cross-slice final acceptance | `RequestUxPhaseTwoFinalAcceptanceContractTest` | Corrective audit in progress |

## Verified checkpoint evidence

Phase 2.13 closed with:

- focused reports/export suite: 13 tests, 111 assertions;
- full Request module regression: 101 tests, 5299 assertions;
- manual UI smoke: PASS;
- working tree: clean.

The project-wide `php artisan test` command is intentionally outside this UX audit. The final automated regression is scoped to `tests/Feature/Request` because the repository contains many unrelated modules and components.

## Phase 2.14 audit findings

The final cross-slice review found and hardened:

- dashboard cards now deep-link to the matching employee workspace through URL-synchronized Livewire state;
- the legacy acceptance-dashboard handoff label now points to the production Request overview;
- remaining English administration labels are Vietnamese;
- stale implementation-checkpoint export copy is replaced with current product behavior;
- the 2.1–2.13 workspace contract inventory is locked by one final acceptance contract.

## Final gate evidence

The first UI smoke passed the planned Phase 2.14 scenarios. A follow-up inspection then found that the visible SLA controls did not all map to the persisted stage contract:

- `email_notification_enabled` did not control `email_on_assignment`, so clearing the checkbox could still leave assignment email enabled;
- `suspend_on_overdue` did not control the runtime `timeout_action`;
- the role resolver option used the unregistered key `fixed_role` instead of `role_members`;
- the compact three-column duration layout could overflow the designer content column;
- the readiness summary counted any stage as ready without validating the resolver and SLA configuration.
- publish validation stayed inside Livewire's field error bag without an obvious global warning, so an incomplete SLA could make the publish action appear unresponsive.
- rerunning the Request DEMO/E2E seeder rewrote version 1 as published, moved the current-version pointer back to version 1, and reused the newest draft; this could leave multiple versions marked published and hide the administrator's intended version from the catalog.
- the requester audience was exposed as raw JSON, so a correctly published type could remain absent from an employee catalog without an understandable administrative control. The designer now uses a searchable user selector, preserves non-user rules server-side, and requires the separate `request.type.audience.manage` permission for assignment changes; ordinary definition editors see the assignments read-only.
- the generic expense starter was too sparse for a real approval conversation. It is replaced by a three-section “Đề xuất tạm ứng chi phí” template for Sales, with expense classification, purpose/outcome, schedule, VND estimate, budget status, itemized estimate, recipient/payment/settlement data, conditional bank details, prior-advance status, and up to five confidential supporting files. Starter approval stages now include safe one-day SLA defaults.
- the first Sales-template smoke exposed three additional runtime UX gaps: an unwritable private attachment directory leaked a Flysystem 500 response, optional blank values were still type-validated, and the dense two-column renderer did not express configurable field widths. Attachment storage failures now become actionable validation feedback, multi-file selection is supported, optional blanks are omitted, date fields may use a timezone-aware “today” default, and Designer persists strict required/default/width controls for newly published versions;
- the final Sales follow-up formats VND amounts with grouped thousands and no decimal fraction, while authorized designers can manage select choices such as expense groups through stable key/label rows instead of editing schema JSON.

PR #37 is therefore reopened for a corrective audit. The correction must prove persisted notification preferences, UTC SLA boundaries, idempotent enforcement, suspension safety, responsive layout, actionable publish-validation feedback, non-destructive DEMO seeding, single-current-version recovery, and Request-only regression before final acceptance is restored.

For local UI acceptance, `php artisan request:e2e-reset --rebuild` is the explicit destructive path. It is production-blocked, deletes only Request-owned data/storage targets, and rebuilds the complete Phase 2 status/SLA/recovery/collaboration matrix, including comments and a private attachment; ordinary seeder reruns remain non-destructive to administrator-managed definitions.

| Gate | Result |
|---|---|
| Corrective focused tests | PENDING |
| Full Request module regression | PENDING (previous evidence: 103 tests, 5336 assertions) |
| Corrective manual Request UI smoke | PENDING |
| Git working tree | PENDING |
| Review branch | `feat/request-ux-phase-2-final-acceptance-audit` |

Phase 2.14 remains open until all corrective gates pass. No merge is implied by the earlier evidence record.

Production enablement and operational readiness remain governed by `IMPLEMENTATION_RUNBOOK.md` and `RELEASE_RUNBOOK.md`.
