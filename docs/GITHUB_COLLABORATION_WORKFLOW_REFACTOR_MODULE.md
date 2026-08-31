# GITHUB Collaboration Workflow — Refactor Module Trigger

> This document is a mandatory extension of `docs/GITHUB_COLLABORATION_WORKFLOW.md` for Major/Clean Module Refactor work. It does not replace the base workflow.

## Trigger

When the user says any equivalent of:

```text
Áp dụng refactor Module
Module: <Module>
```

including `Refactor Module: <Module>` or `Major Refactor Module <Module>`, the task must be treated as a Major/Clean Module Refactor and this extension becomes mandatory.

The trigger authorizes read-only bootstrap and analysis only. It does not by itself authorize branch creation, implementation, deletion, migration, destructive operations, PR creation, or merge.

## Mandatory documents and runtime evidence

Before proposing implementation, read and reconcile:

1. `docs/GITHUB_COLLABORATION_WORKFLOW.md`
2. `docs/MODULE_REFACTOR_WORKFLOW.md`
3. `docs/modules/<Module>/MODULE.md`
4. `docs/modules/<Module>/COLLABORATION_HANDOFF.md` when present
5. `Modules/<Module>/config/module.php` or `Modules/<Module>/Config/module.php`
6. the Module's routes, runtime source, persistence boundaries, tests, and relevant cross-module callers/dependencies
7. `.codex/standards/ADMIN_UI_STANDARD.md` when Admin UI is in scope
8. `docs/PWA_EXTERNAL_FILE_HANDOFF.md` when PWA file handoff is in scope

`MODULE.md` is the durable architectural contract. `COLLABORATION_HANDOFF.md` is the current delivery/checkpoint state. Neither may silently override runtime evidence.

## Missing MODULE.md gate

If `docs/modules/<Module>/MODULE.md` does not exist:

```text
MODULE.md missing
    ↓
DO NOT start implementation
    ↓
Audit current runtime and ownership
    ↓
Use docs/modules/MODULE_TEMPLATE.md
    ↓
Propose Module Contract
    ↓
User approval
    ↓
Create/update MODULE.md
    ↓
Then begin approved Major Refactor implementation
```

A missing contract is therefore a refactor bootstrap blocker, not permission to infer ownership from directory names.

## Architecture drift gate

If `MODULE.md` disagrees with current source/schema/config/routes/tests:

1. mark the condition `ARCHITECTURE DRIFT`;
2. trace the actual runtime and callers;
3. identify whether documentation, runtime, or both require correction;
4. propose the target architecture;
5. obtain user approval;
6. only then implement the correction.

Do not automatically treat either documentation or runtime as the intended target architecture when they conflict.

## Route-first ownership audit

Major Refactor must start with runtime ownership rather than directory cleanup:

```text
Route
→ Controller
→ View / Livewire
→ Service
→ Model / Persistence
→ Callers
→ Cross-module dependencies
```

For Admin-facing functionality, inspect `/admin/*` routes in specialized modules as well as `Modules/Admin/routes/web.php`. A URL prefix does not establish module ownership.

Each affected artifact/boundary must be classified as:

- `KEEP`
- `REHOME`
- `DELETE`
- `QUARANTINE`
- `DEFER`

A duplicate or similarly named implementation in another module is not sufficient deletion proof.

## Approval gate

Before implementation, present one coherent refactor manifest/plan covering at least:

- current responsibility and target responsibility;
- direct and integration dependencies;
- canonical ownership and explicit non-ownership;
- architecture drift found;
- `KEEP / REHOME / DELETE / QUARANTINE / DEFER` decisions;
- persistence/migration risks;
- compatibility and deprecated boundaries;
- impacted modules and proposed regression scope;
- deferred debt and target owner.

Do not create the implementation branch or modify application source until the user approves this target plan, unless the user has explicitly authorized implementation already.

## Contract update gate

Any PR that changes the architectural contract must update in the same PR:

```text
docs/modules/<Module>/MODULE.md
```

Architectural contract changes include:

- purpose/responsibility;
- canonical ownership or non-ownership;
- direct dependency requirements;
- canonical route ownership;
- public integration boundaries;
- persistence ownership;
- compatibility/deprecation boundaries;
- quarantine rules;
- refactor invariants.

`COLLABORATION_HANDOFF.md` must also be updated according to the base GitHub collaboration workflow.

Therefore, for an architectural refactor PR:

```text
Source/runtime change
+ contract tests
+ MODULE.md when contract changes
+ COLLABORATION_HANDOFF.md
```

form one coherent delivery boundary.

## Contract-test rule

When an intentionally retired/re-homed architectural artifact is protected by a stale test, update that contract test in the same refactor slice. Tests must protect the approved target architecture rather than require legacy files to continue existing.

Do not weaken tests merely to make deletion pass; replacement assertions must prove the new canonical owner/boundary where applicable.

## Regression and closeout

Follow `docs/MODULE_REFACTOR_WORKFLOW.md` and the base collaboration workflow for focused tests, Module regression, impacted/cross-module regression, routes, Pint, build, UI acceptance, Git-clean verification, handoff, PR and post-merge closeout.

Full-project regression remains conditional, not automatic.

A Major Refactor is complete only when:

- approved ownership boundaries are implemented;
- applicable regression gates pass;
- architecture contract and runtime agree;
- deferred debt has an explicit target owner/phase;
- `MODULE.md` reflects the durable final architecture;
- `COLLABORATION_HANDOFF.md` reflects the delivery checkpoint;
- PR/merge/post-merge closeout rules are satisfied.

## Canonical short prompt

After this workflow extension is merged, the preferred new-chat entry is:

```text
Áp dụng refactor Module
Module: <Module>
```

The assistant must interpret that prompt as invoking both `docs/GITHUB_COLLABORATION_WORKFLOW.md` and the clean Module refactor workflow defined by `docs/MODULE_REFACTOR_WORKFLOW.md`, with `docs/modules/<Module>/MODULE.md` as the mandatory durable architecture contract.