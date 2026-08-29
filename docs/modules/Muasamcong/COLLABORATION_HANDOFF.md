# Muasamcong Module — Collaboration Handoff

- Last updated: 2026-08-29
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Baseline source checkpoint: `3c755169ecb99610a0a00c6a023d57b80cfe6f2b`
- Delivery: **Muasamcong baseline analysis documentation**
- Delivery branch: `docs/muasamcong-baseline-analysis`
- Accepted analysis checkpoint: `0715a6494de0bf1818c7f7177881a08fe1cabc47`
- Pull request: **#70 — OPEN / awaiting user review**
- Pull request URL: `https://github.com/tungocvan/laravel-shop/pull/70`
- PR head before handoff refresh: `089f1615690418b9ccd8b304a870f7129b68a28e`
- Merge commit: **PENDING POST-MERGE VERIFICATION**
- Delivery status: **COMPLETED — documentation-only baseline accepted by user**
- Next MR/phase: **NOT DETERMINED**

## Current checkpoint

The Muasamcong baseline analysis has been completed and accepted by the user. The delivery is documentation-only and follows `.codex/tasks/analyze-module.md` plus the repository collaboration workflow.

Files refreshed by the baseline:

- `docs/modules/Muasamcong/ANALYSIS.md`
- `docs/modules/Muasamcong/INFORMATION.md`
- `docs/modules/Muasamcong/README.md`

This handoff was created as the separately approved post-analysis handoff batch required before a pull request. No application source, route, controller, Livewire component, service, model, migration, test, configuration, runtime state, or production state was changed.

## Baseline conclusion

Final recommendation:

```text
Major Refactor
Delivery approach: compatible incremental hardening and extraction
Full rebuild: not recommended
```

No P0 issue was observed in the reviewed tracked source. Runtime secrets, production data, effective production module state, and live upstream behavior were not inspected.

Material P1 findings accepted as the current starting point:

1. mutation authorization is inconsistent across wishlist deletion, pricing-history deletion, contractor refresh/delete/sync, KQLCNT/HSMT sync, manual-lot replacement, and verified-lot sync;
2. one-time session import token validation/consumption does not atomically claim the token before cookie mutation;
3. contractor queue dispatch and selected contractor sync are not fully idempotent under concurrency;
4. ClientPortal local search returns at most 500 matching rows while reporting the result as complete;
5. database/file snapshot and raw-payload retention is undefined;
6. long synchronous upstream/export workflows need measured operational thresholds;
7. core controllers and Livewire components carry excessive orchestration responsibility.

The detailed evidence, file paths, impact, recommendation, test gaps, and unknowns are canonicalized in `ANALYSIS.md`.

## Accepted Admin Dashboard requirement

The user requested an Admin Dashboard that centralizes Muasamcong information and links to the module's management functions.

Accepted compatibility contract for the future capability:

```text
New Admin Dashboard route: /admin/muasamcong/dashboard
Existing /admin/muasamcong: remains the Smart Pricing workspace
```

This avoids changing the current index URI behavior and preserves existing Smart Pricing bookmarks and links.

Expected Dashboard boundary for the future implementation:

- permission-aware links to Smart Pricing, contractor/history/manual-lot workflows, HSMT, synced data/export, wishlist, and configuration/session tools;
- bounded aggregate/status DTOs from a service; no model queries or business logic in Blade;
- recent/failed queue state, snapshot freshness, bounded record counts, and configuration/session health without exposing secrets;
- canonical Admin page shell and responsive workspace/card patterns;
- explicit empty/loading/error/stale states and keyboard-accessible actions;
- no domain logic moved into ClientPortal.

Dashboard source implementation has **NOT STARTED** in this branch. A focused implementation plan, affected-file inventory, permission design, tests, UI acceptance, compatibility boundary, and new branch require explicit approval before code changes.

## Architecture and ownership boundaries

- Muasamcong remains the domain owner for upstream integration, normalization, persistence, snapshots, jobs, export profiles, and personal sessions.
- ClientPortal remains the presentation owner for customer-facing Muasamcong routes/views and consumes Muasamcong models/services in one direction.
- Dependency direction remains `ClientPortal -> Muasamcong`; no circular dependency was observed.
- Canonical repository loader remains `Modules\ModuleServiceProvider`.
- Existing route names, table/schema contracts, storage paths, export profile formats, and ClientPortal consumers require an explicit compatibility plan before change.
- Upstream source data must remain separate from manual administrative enrichment.
- A winner/contractor must not be mapped to an exact lot/medicine without a verified join key.

## Security and safety boundaries

The following existing controls must be preserved:

- exact HTTPS host `muasamcong.mpi.gov.vn` and port validation;
- upstream redirects disabled;
- production TLS verification always enabled;
- token/cookie values never committed, logged, rendered, or hydrated into public Livewire state;
- personal session cookie encrypted at rest;
- private/temporary export and HSMT storage behavior;
- server-side export identifier validation and user-scoped export profiles;
- ClientPortal protected-file/PWA handoff security and session boundary.

The next implementation work should begin with capability-specific authorization and denial tests before adding broader mutation/navigation surfaces.

## Database, migration, storage, and operations

Current baseline inventory:

- 13 active Muasamcong-owned database tables;
- 19 historical/current migration files;
- private HSMT JSON/XLSX/metadata snapshots under `muasamcong/hsmt/<notifyNo>/`;
- one domain queue job: `FetchContractorHistoryJob`;
- FastExcel and PhpSpreadsheet exports;
- encrypted personal-session storage and hashed one-time import tokens.

This delivery introduced:

```text
Migrations: none
Seeders: none
Storage changes: none
Environment changes: none
Operational commands: none
Runtime module-state changes: none
Production changes: none
```

Retention periods, cleanup rules, capacity thresholds, and production data/storage volume remain **NOT DETERMINED / NOT VERIFIED**.

## Verification evidence

### Static baseline review

- module source/config/migrations/views/controllers/Livewire/services/models/jobs reviewed;
- 14 Muasamcong feature test files inventoried;
- 11 directly related ClientPortal/ClientApps test files inventoried;
- repository bootstrap, module/admin standards, import/export guidance, workflow, module docs, and direct ClientPortal adapter reviewed;
- GitHub branch/PR/main checkpoints reverified before this handoff.

### User local synchronization

User local fast-forwarded successfully:

```text
3c755169..0715a649
Branch: docs/muasamcong-baseline-analysis
Tracking: origin/docs/muasamcong-baseline-analysis
Working tree paths: none reported
```

The user-reported diff contained exactly the three baseline documentation files before this handoff was added.

### Gate status

| Gate | Status | Evidence / scope |
|---|---|---|
| Baseline source/static analysis | PASS | Required source, direct dependencies, standards, docs, and test inventory reviewed |
| User review/acceptance | PASS | User explicitly accepted the baseline and Dashboard route proposal on 2026-08-29 |
| Markdown whitespace check | PASS | `git diff --check main...HEAD` produced no reported error before handoff creation; generated handoff checked separately before commit |
| Focused automated tests | NOT APPLICABLE | Documentation-only delivery; no executable behavior changed |
| Muasamcong regression | NOT APPLICABLE | Documentation-only delivery |
| ClientPortal impacted regression | NOT APPLICABLE | Documentation-only delivery; adapter source/contracts unchanged |
| Full project regression | NOT APPLICABLE — module-scoped documentation strategy | No shared/core/runtime change |
| Admin UI smoke | NOT APPLICABLE | No Admin UI implementation/change in this delivery |
| Admin UI standard | REVIEWED FOR ANALYSIS | Mandatory future Dashboard implementation boundary recorded; no rendered UI to accept |
| PWA file handoff acceptance | NOT APPLICABLE | No download/open behavior changed |
| Runtime/upstream verification | NOT RUN | Outside documentation-only scope |
| Production enablement | NOT AUTHORIZED / NOT CHANGED | Source documentation does not imply production action |
| Local Git clean | PASS at initial handoff checkpoint `089f1615` | User pulled the handoff commit; output showed aligned branch status and no working-tree file paths |

Automated test files were inspected as evidence of coverage intent. This handoff does not claim a fresh runtime test PASS.

## Documentation state

Current entry order:

1. `COLLABORATION_HANDOFF.md` — current continuation checkpoint.
2. `README.md` — developer entry point and operational boundaries.
3. `INFORMATION.md` — factual route/component/service/model/table inventory.
4. `ANALYSIS.md` — evidence, P0/P1/P2 findings, Dashboard assessment, and final recommendation.
5. `ROUTES.md`, `SYNCED.md`, `ENV_DOCTOR.md` — supporting references outside the baseline output contract; verify against source.
6. `AI_HANDOFF.md` — legacy investigation context, especially winner/lot research; not the canonical current handoff.

Historical/supporting docs may still contain stale route or workflow statements because the `/analyze` contract authorized only `ANALYSIS.md`, `INFORMATION.md`, and `README.md`.

## Blockers and deferred work

No blocker prevents review of this documentation delivery.

Deferred/unknown:

- effective production Muasamcong enablement and current runtime configuration;
- intended callers/capability policy for the Sanctum Muasamcong API;
- snapshot/raw-payload/HSMT/export/job retention policy;
- actual production queue, database, and storage volumes;
- verified upstream winner/contractor-to-exact-lot/medicine join key;
- implementation design and tests for the accepted Admin Dashboard capability;
- correction batches for the P1 findings.

## Production boundary

Merging this documentation branch does not:

- deploy source;
- enable or disable Muasamcong;
- change secrets, endpoint configuration, queue workers, database state, storage, or permissions;
- authorize production changes;
- authorize a Dashboard implementation automatically.

Production verification/enablement remains a separate operational action with explicit authorization.

## Remaining work / next authorized step

The current authorized sequence is:

1. synchronize and verify the PR #70 handoff-refresh commit on the user local branch;
2. review PR #70 and merge it manually only after the refreshed diff/Git-clean gate passes;
3. after merge, verify `main` and perform the required stable handoff closeout because this delivery handoff contains temporary PR state;
4. bootstrap a separate Dashboard implementation task from latest `main`, read `ADMIN_UI_STANDARD.md` and shared Admin patterns, propose the focused architecture/permission/test/UI plan, and wait for explicit implementation approval.

No Dashboard feature branch, MR number, source change, merge, production action, or next refactor batch should be inferred from this handoff. The next MR/phase remains **NOT DETERMINED**.
