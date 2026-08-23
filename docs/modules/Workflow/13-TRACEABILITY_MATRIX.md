# Requirements Traceability Matrix

This matrix prevents implementation from silently omitting enterprise controls or adding unapproved scope.

| ID | Requirement | Primary specification | Verification |
|---|---|---|---|
| WF-ARCH-001 | Module type is domain | REQUIREMENTS §3, Master §8 | Manifest/boot test |
| WF-ARCH-002 | Dependencies are shell-only | REQUIREMENTS §4, RBAC §9 | Dependency architecture test |
| WF-ARCH-003 | No domain-module imports/data access | REQUIREMENTS §3–4 | Namespace/table scan test |
| WF-ARCH-004 | Root provider/runtime-state compatible | Master §8 | Discovery and ON/OFF tests |
| WF-DEF-001 | Stable definition identity and versions | Domain §1–2 | Service/database tests |
| WF-DEF-002 | Published versions immutable | Domain §2, Definition §2 | Update/delete denial tests |
| WF-DEF-003 | Graph/schema/security validation | Definition §3 | Validator scenario matrix |
| WF-DEF-004 | Deterministic safe DSL | Definition §4 | Operator/type/fuzz tests |
| WF-DEF-005 | Version checksums/diff/publication audit | Definition §1–2 | Publication integration test |
| WF-FORM-001 | Versioned dynamic schema | Form §2–4 | Schema validation tests |
| WF-FORM-002 | Server validation/normalization | Form §6–8 | Draft/submit tests |
| WF-FORM-003 | Safe computed/conditional fields | Form §6–7 | Cycle/tamper/type tests |
| WF-FORM-004 | Private managed attachments | Form §9, Security §9 | Upload/download security tests |
| WF-REQ-001 | Internal Workflow requests only | REQUIREMENTS §1–3 | API/model architecture tests |
| WF-REQ-002 | Canonical request lifecycle | REQUIREMENTS §6.3 | Transition scenario tests |
| WF-REQ-003 | Pinned definition version | Domain invariants | Version continuity test |
| WF-ENG-001 | Transactional deterministic engine | Engine §1–3 | Rollback/retry tests |
| WF-ENG-002 | Sequential/conditional execution | Engine §4, §6 | Path scenario tests |
| WF-ENG-003 | Parallel/quorum/join | Engine §5, §7 | Concurrent quorum tests |
| WF-ENG-004 | Return/reject/recall/cancel | Engine §8 | Lifecycle/policy tests |
| WF-ENG-005 | Durable timers/subflows | Engine §10–11 | Timer/subflow tests |
| WF-REL-001 | Idempotent commands/jobs | Engine §2, API §6 | Replay/fingerprint tests |
| WF-REL-002 | Transactional outbox | API §7–8 | Rollback/delivery/dedupe tests |
| WF-REL-003 | Locking/optimistic concurrency | Engine §2, Domain invariants | Race/stale-version tests |
| WF-ACT-001 | User/Role actor resolution | RBAC §1–3 | Resolver snapshot tests |
| WF-ACT-002 | Extensible allowlisted resolver contract | RBAC §2 | Registry/validation tests |
| WF-ACT-003 | Delegation/reassignment evidence | RBAC §7, Engine §9 | Scope/cycle/audit tests |
| WF-ACT-004 | Separation of duties/self-approval | RBAC §8 | Policy matrix tests |
| WF-SEC-001 | Capability + record authorization | RBAC §4–5 | Allow/deny feature tests |
| WF-SEC-002 | No executable/untrusted registry input | Security §3–4 | Malicious input tests |
| WF-SEC-003 | Append-only redacted audit | Security §6–7 | Mutation/redaction/hash tests |
| WF-SEC-004 | No digital signature in v1 | REQUIREMENTS §2, Security §7 | Documentation/surface test |
| WF-API-001 | Versioned Sanctum API | API §2–3 | Route/auth contract tests |
| WF-API-002 | Safe errors/correlation IDs | API §5 | Error mapping tests |
| WF-UI-001 | Workspace-first Admin UI | UI §1–12 | Livewire/UI smoke |
| WF-UI-002 | Search/filter/reset/bounded pagination | UI §4–5 | List feature tests |
| WF-UI-003 | Responsive/accessibility states | UI §13–14 | Manual/automated smoke |
| WF-OPS-001 | Durable SLA/reminder/escalation | Operations §4–6 | Due/retry tests |
| WF-OPS-002 | Queue/scheduler conventions | Operations §2–3 | Registry/command tests |
| WF-OPS-003 | Safe stuck detection/recovery | Operations §8–9 | Read-only/allowlist tests |
| WF-OPS-004 | Private retention/cleanup | Operations §10 | Dry-run/retention tests |
| WF-TEST-001 | Layered release gate | Test §1, §13 | CI/manual evidence |

## Scope guard

Any implementation item without a traceability ID must be classified as:

- required implementation detail for an approved requirement;
- non-blocking documentation/refactor;
- new scope requiring approval.

The following always require new approval: non-shell dependency, domain integration, multi-tenancy, full BPMN, PKI signature, arbitrary webhook/code execution, destructive cross-module schema change, or root provider redesign.

