# Request Module — Collaboration Handoff

Last updated: 2026-08-26  
Repository: `tungocvan/laravel-shop`  
Base branch: `fix/request-e2e-submit-demo`  
Working branch: `feat/request-ux-phase-2-final-acceptance-audit`  
Pull request: `#37 feat(request): complete UX phase 2`  
Last verified code checkpoint: `66d9a6c9`  
Documentation checkpoint before merge: `8ee0b439`  
Merge status: all applicable gates passed; user explicitly approved merging PR #37 after the final workflow/handoff documentation commit.

## Current outcome

Request UX Phase 2.1–2.14 is implemented and the final corrective audit has passed. The module now includes permission-aware requester, approver and administration workspaces; definition/version management; SLA and recovery UX; reports/exports; safe demo data; professional Sales advance templates; governed type duplication; and controlled administrative cleanup.

The project-wide `php artisan test` command is intentionally not used for this work. Regression is scoped to `tests/Feature/Request` because the repository contains many unrelated modules and components.

## Delivery checkpoints

| Scope | Branch/checkpoint | Result |
|---|---|---|
| Phase 2.1–2.11 workspaces | cumulative `feat/request-ux-phase-2-*` branches | Completed |
| Phase 2.12 Operations / Recovery | `feat/request-ux-phase-2-operations` | Completed |
| Phase 2.13 Reports / Export | `feat/request-ux-phase-2-reports-exports` / `9698c8a8` | Completed |
| Phase 2.14 acceptance audit | `feat/request-ux-phase-2-final-acceptance-audit` | Gates passed |
| SLA/runtime correction | `1e9b8174` | Completed |
| Publish validation feedback | `bba13b2a` | Completed |
| Demo/version safety | `19cfb0f8`, `870e991c`, `fd4bc91f` | Completed |
| Audience authorization UX | `e141f158` | Completed |
| Sales advance template | `bec74be8` | Completed |
| Upload/form hardening | `1f361598` | Completed |
| Grouped VND/options UX | `7954ff21`, `487d90a6` | Completed |
| Governed duplication/cleanup | `0725a5c4`–`66d9a6c9` | Completed |

## Important defects fixed

- SLA/email controls did not map consistently to persisted runtime settings; assignment/decision/warning preferences and timeout behavior now map to the actual stage contract.
- Role resolver used an unsupported key; it now uses the registered resolver and publish readiness validates resolver/SLA configuration.
- SLA layout overflowed and publish validation appeared unresponsive; layout is responsive and validation receives an actionable modal/global message.
- Demo/E2E seeding could rewind published version pointers and create multiple published versions; normal seeding is non-destructive and publication restores the single-current-version invariant.
- SQLite rebuild failed on self-referencing version lineage; destructive local rebuild clears lineage in a safe order.
- Raw audience JSON made catalog eligibility unsafe and difficult to manage; Designer uses a searchable user selector protected by `request.type.audience.manage`.
- Published types could be missing from an employee catalog due to audience rules; explicit user eligibility is now visible and manageable.
- Sales expense proposal was too sparse; starter template now covers expense category, purpose, schedule, amount, budget, recipient/payment, settlement and multi-file evidence.
- Private attachment storage could leak a Flysystem 500 due to ownership; storage failures become localized validation feedback and the local rebuild normalizes `www-data:www-data` ownership without `chmod 777`.
- Optional blank fields were still type-validated and optional dates lacked useful defaults; blanks are omitted and configurable dates can default to the current local date.
- VND values lacked grouping and could imply decimals; configured monetary fields use Vietnamese thousands grouping and integer persistence.
- Select options required raw schema editing; authorized designers manage stable key/label rows.
- Report filter controls had weak visual boundaries; comboboxes and date inputs now have explicit borders, backgrounds, padding and focus states.
- Completed-request deletion initially failed on the restrictive audit FK; linked audit rows are detached from the runtime FK and preserved before the request aggregate is deleted.
- Type duplication initially rendered its form outside the current viewport; it now opens as an accessible fixed modal with visible validation and loading state.

## Authorization and safety boundaries

- `request.type.audience.manage`: assign which users may create a request type.
- `request.type.delete`: delete only a type that has never been published and has no runtime requests.
- `request.instance.delete`: delete only terminal requests (`approved`, `rejected`, `cancelled`) from the register.
- `request.operation.delete`: delete only failed outbox/export records.
- Active requests cannot be deleted from the report register.
- Stage-activation failures represent a live workflow and cannot be deleted independently; they must be recovered.
- Published request types cannot be deleted.
- Administrative deletion requires confirmation and leaves an independent audit event.
- Whole-type duplication requires create/update permission; audience copying additionally requires audience-management permission. A copy always starts as an unpublished v1 draft.

After adding or changing Request permissions, synchronize active-module permissions with:

```bash
php artisan db:seed --class='Modules\Role\database\seeders\RolesAndPermissionsSeeder'
```

## Local demo and operational notes

- Destructive local rebuild: `php artisan request:e2e-reset --rebuild`.
- The command is production-blocked and deletes only Request-owned data/storage.
- Demo matrix includes draft, pending, warning, overdue, suspended, approved, rejected, returned, cancelled and failed activation states.
- Demo roles cover requester, approver, finance and audit views.
- Private files remain on a non-public disk; no `chmod 777` workaround is allowed.
- Backend timestamps use UTC; UI displays configured local timezone (`Asia/Ho_Chi_Minh` in the accepted demo).

## Latest validation evidence

| Gate | Evidence |
|---|---|
| Focused permission/catalog batch | 19 passed, 138 assertions |
| Duplication/cleanup service batch | 13 passed, 111 assertions |
| Route/service batch | 15 passed, 168 assertions |
| Request regression after route update | 129 passed, 5713 assertions |
| Audit-FK/duplication batch | 7 passed, 71 assertions |
| Request regression after audit-FK fix | 131 passed, 5724 assertions |
| Final duplication-modal focused test | PASS |
| Final Request regression | PASS |
| Manual UI smoke | PASS |
| Git working tree | clean |

Manual UI acceptance covered report borders, terminal-request deletion, safe operation cleanup, never-published type deletion, protected published types, duplication modal and creation of a new v1 draft.

## Workflow preference learned

Whenever a new batch is ready, provide in one response:

1. `git pull --ff-only`
2. Test 1 (focused)
3. Test 2 (Request regression), executed only if Test 1 passes

If Test 1 fails, stop before Test 2 and return the raw failure. Do not request these three stages over three separate conversation turns when both test commands are already known.

## Remaining work / next authorized step

- PR #37 was open and mergeable at the last handoff verification.
- User explicitly approved updating this workflow/handoff and merging PR #37.
- Before merge, re-check PR head, base and mergeability; after merge, a new chat must verify the merged state on GitHub rather than relying only on this snapshot.
- This PR does not enable the Request module in production; production enablement remains governed by `IMPLEMENTATION_RUNBOOK.md` and `RELEASE_RUNBOOK.md`.
