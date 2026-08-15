# Task: /create-module <ModuleName>

Create a new Laravel module that fits this repository and its current module runtime architecture.

## Required Reading

Before planning or writing code, read:

- `.codex/bootstrap/CODEX_BOOTSTRAP.md`
- `.codex/bootstrap/PROJECT_BOOTSTRAP.md`
- `.codex/bootstrap/AI_PROJECT_CONTEXT.md`
- `.codex/standards/MODULE_STANDARD.md`
- `.codex/standards/ADMIN_UI_STANDARD.md`
- `.codex/tasks/create-import-export.md` when the module owns portable/admin tabular data
- `.codex/prompts/import-export.md` when import/export is in scope
- `ROADMAP.md`
- `Modules/ModuleServiceProvider.php`
- `app/Modules/ModuleStateRepository.php`
- `app/Modules/ModuleStateResolver.php`
- `config/modules.php`

`Modules/ModuleServiceProvider.php` is the authoritative module discovery/bootstrap contract for this repository. A new module MUST fit this provider instead of introducing another module system or registration path.

## Phase 1 — Resolve Scope

1. Confirm that `Modules/<ModuleName>/` does not already exist.
2. Read the business requirement/specification supplied for the new module.
3. Identify required features, routes, permissions, models, tables, services, Livewire components, files, imports/exports, events/jobs, console commands, and external/cross-module dependencies.
4. Mark unclear or risky business decisions explicitly. Do not invent domain rules silently.
5. Classify the module as `shell`, `support`, or `domain` based on its actual responsibility and dependency role.
6. Identify whether the module needs Admin UI, public web routes, API routes, background jobs, queue workers, runtime storage, external services, or Docker-specific support.
7. If the module has an Admin list/workspace with more than trivial data, explicitly evaluate search, filters, bounded pagination, row selection/bulk actions and Import/Export. These are default design considerations, not optional afterthoughts.

## Phase 2 — Inspect Repository Conventions

Before designing the module, inspect 1–3 existing modules that are closest in architecture or business shape.

For each reference module, record what convention is being reused, for example:

- manifest structure
- routes and middleware
- permissions
- service boundaries
- Livewire conventions
- models/migrations
- list/filter/pagination patterns
- bulk action/modal patterns
- import/export
- events/jobs
- tests
- module documentation

Do not copy an existing module blindly. Reuse only conventions that still match the current repository architecture and standards.

## Hard Rule — Follow `Modules\ModuleServiceProvider`

Every new module MUST be compatible with the current behavior of `Modules/ModuleServiceProvider.php`.

Before implementation, verify how the provider currently discovers and registers:

- `config/module.php` / `Config/module.php`
- module `type`
- `depends`
- effective enabled state
- optional `Providers/<ModuleName>ServiceProvider`
- `config` / `Config`
- `routes/web.php`
- `routes/api.php`
- `resources/views`
- `resources/lang`
- `Helpers`
- `database/migrations` / `Database/Migrations`
- `Livewire`
- Blade components
- `Console` commands

Do NOT add any of the following unless `Modules/ModuleServiceProvider.php` explicitly requires them:

- `module.json`
- nwidart infrastructure
- manual provider registration in unrelated bootstrap files
- a second module registry
- custom discovery code that duplicates the root provider

If `Modules/<ModuleName>/Providers/<ModuleName>ServiceProvider.php` exists, do not manually re-register routes, migrations, config, Livewire components, Blade components, console commands, or other resources already automatically handled by `Modules/ModuleServiceProvider.php` unless the module has a documented special requirement.

Avoid duplicate route names, duplicate migrations, duplicate component registration, duplicate config loading, and duplicate resource registration.

If a requirement cannot be supported cleanly by the current provider, STOP and propose a provider-level architecture change separately before implementing the module.

## Phase 3 — Module Manifest and Runtime State

Create `Modules/<ModuleName>/config/module.php` according to current repository conventions.

The manifest must define only source/default metadata needed by the repository, such as:

- `name`
- `type`
- `default_enabled` or legacy `enabled` only when consistent with the current repository convention
- `depends`
- `permissions`
- other metadata actually consumed by current infrastructure

Source state and deployment runtime state are different concerns.

Tracked manifest files MUST NOT be used as mutable production state.

Runtime enable/disable state is owned by the runtime module-state architecture and stored through:

- `ModuleStateRepository`
- `ModuleStateResolver`
- `storage/app/system/module-state.json`

A new module must not read or write that JSON file directly when the repository abstraction already exists.

Runtime toggle operations must not modify `Modules/<ModuleName>/config/module.php` and must leave Git clean.

## Phase 4 — Dependency Design

Dependencies must be explicit and valid.

Verify:

- every declared dependency exists or is included in the approved implementation scope
- enabled modules do not depend on disabled modules
- there is no self-dependency
- there is no circular dependency
- dependent modules prevent unsafe disable/archive operations where current infrastructure enforces that rule

Do not hard-code the same dependency graph in multiple places when the module registry already owns it.

## Phase 5 — Create Implementation Plan

Before application code, create or update:

`docs/modules/<ModuleName>/CREATE_PLAN.md`

The plan must include:

- Purpose and business scope.
- Selected module type: `shell`, `support`, or `domain`, with rationale.
- Reference modules and conventions reused.
- Compatibility notes for `Modules/ModuleServiceProvider.php`.
- Proposed module structure.
- Manifest design and default state.
- Runtime-state behavior.
- Cross-module dependencies.
- Routes and permissions.
- Database/model design.
- Service boundaries and transaction rules.
- Livewire/UI design.
- List workspace design when applicable: search, filters, reset behavior, pagination, selection and bulk actions.
- Import/export design when applicable.
- Events/jobs/queue/console design when applicable.
- Runtime storage and Docker considerations when applicable.
- Seeder design when applicable.
- Security and data-integrity considerations.
- Tests and acceptance criteria.
- Files to create/change.
- Suggested MR/phase breakdown.
- Risks and unresolved questions.

For a new operational/Admin data module, the plan MUST explicitly evaluate these capabilities:

```text
Search
Domain filters
Reset filters
Bounded pagination
Checkbox selection
Bulk actions
Import / Export
Selected-row export
Success/loading/confirmation UX
```

If a capability is not appropriate, document why instead of silently omitting it.

Import/Export is strongly recommended when the module owns portable tabular business data that administrators reasonably need to back up, migrate, bulk-load, review in Excel/CSV, or transfer between environments. It is not mandatory for configuration-only, ephemeral, security-sensitive, or non-tabular domains where such portability would be harmful or meaningless.

`CREATE_PLAN.md` must also contain a concise **Bootstrap Contract** table or block proving compatibility with `Modules/ModuleServiceProvider.php`, covering at least:

- Manifest: `config/module.php`
- Type: `domain` / `support` / `shell`
- Dependencies: explicit module list
- Module Provider: required / not required
- Config: yes / no
- Web routes: yes / no
- API routes: yes / no
- Migrations: yes / no
- Livewire: yes / no
- Blade components: yes / no
- Console commands: yes / no
- Runtime state: supported / not applicable

The Bootstrap Contract must describe only mechanisms actually supported by the current root provider. Do not use the plan to justify parallel registration infrastructure.

## Approval Gate

After `CREATE_PLAN.md` is generated, STOP.

Do not create application code until the user explicitly approves the plan or explicitly requests implementation based on the approved plan.

## Phase 6 — Implementation After Approval

After approval:

1. Re-read the approved `CREATE_PLAN.md`, required standards, and `Modules/ModuleServiceProvider.php`.
2. Create only the folders/classes required by the approved plan.
3. Keep the module discoverable through the existing root module provider without adding parallel infrastructure.
4. Add routes, controllers, Page Blades, Livewire components, services, models, migrations, policies/permissions, imports/exports, events/jobs, console commands only when required.
5. Keep business logic in services/actions rather than Blade or large Livewire methods.
6. Explicitly authorize sensitive mutations on the backend; hiding buttons is not authorization.
7. Preserve current folder casing and namespace conventions.
8. Add focused tests as each meaningful implementation batch is completed.
9. Update module documentation so it reflects implemented reality.
10. When Admin list UI is applicable, implement useful search/filter/reset controls, bounded pagination and clear empty/loading states from the beginning rather than adding them as late polish.
11. For potentially large datasets, use bounded page sizes; recommended defaults are `10`, `25`, `50`, `100`. Do not create an unbounded `All` option.
12. When row selection is useful, show selected count and use explicit modal confirmation for destructive bulk actions.
13. When Import/Export is applicable, reuse the canonical shared foundation and follow `.codex/tasks/create-import-export.md` rather than building module-private infrastructure.
14. For checkbox-enabled export, no selection exports all approved records; selected IDs export only those records.
15. Successful import/export or destructive actions that materially change visible data should provide explicit success feedback and a clean refresh path.

## Implementation Phases / MR Guidance

Prefer small coherent batches. A typical module may use:

- MR-0 — analysis / architecture / `CREATE_PLAN.md`
- MR-1 — module skeleton + manifest + bootstrap verification
- MR-2 — database + models
- MR-3 — services / business logic
- MR-4 — routes / Admin / Livewire
- MR-5 — permissions + menu integration
- MR-6 — filters / bulk UX / import-export / jobs / external integration when applicable
- MR-7 — tests + documentation
- MR-8 — final regression + manual smoke

This is a template, not a requirement. Merge, split, or omit phases when module size justifies it.

## Database Rules

When database work is required:

- follow the repository migration path discovered by `Modules/ModuleServiceProvider.php`
- use consistent table naming
- define required indexes and foreign keys
- ensure migrations can run through the module lifecycle
- do not edit unrelated historical migrations
- do not use `migrate:fresh` as a bug fix
- test both schema contracts and module enable/migration behavior when relevant

## Livewire / Admin UI Rules

When Admin UI is required:

- follow `.codex/standards/ADMIN_UI_STANDARD.md`
- use repository permission naming conventions
- protect Admin routes with expected auth/permission middleware
- authorize every sensitive Livewire mutation server-side
- validate bounded input
- do not expose raw internal exceptions to the browser
- provide loading/error/confirmation/success states where appropriate
- keep business logic in services
- use filters that match real domain questions, not decorative controls
- reset pagination and selection when filter scope changes
- keep pagination bounded and visually aligned with the repository accent/indigo treatment
- use centered modal confirmation for destructive selected/all actions
- treat Import/Export as a secondary tool so it does not overpower the primary workspace

## Seeder Rules

When seeders are required:

- make them production/Docker safe
- do not depend on Faker when production dependencies may not include Faker
- prefer deterministic seed data
- use idempotent patterns such as `updateOrCreate()` / `firstOrCreate()` when appropriate to the business rule
- do not let demo seeders overwrite production data or administrator credentials

## Runtime Storage / Docker Rules

If the new module creates runtime files or directories:

- inspect `Dockerfile`
- inspect `docker/entrypoint.sh`
- verify `www-data` ownership/permissions
- verify persistent volume requirements
- avoid `chmod 777`
- account for CLI commands run as `root` versus PHP-FPM running as `www-data`

If infrastructure support is needed, include it in the approved plan instead of silently creating runtime paths with incompatible ownership.

## Required Verification

Choose tests according to actual scope. Consider:

- route configuration tests
- permission/authorization tests
- service tests
- model/database tests
- Livewire feature tests
- search/filter/pagination tests
- selection/bulk-action tests
- import/export tests
- job/queue/console tests
- dependency tests
- module bootstrap tests
- runtime-state tests

For every new module, explicitly verify module discovery through `Modules/ModuleServiceProvider.php`.

When runtime state applies, verify at least:

1. manifest/default state is resolved correctly
2. runtime override ON works
3. runtime override OFF works
4. required/shell protection works when applicable
5. dependency rules use effective state
6. runtime toggle does not modify the module manifest
7. `git status` remains clean after runtime operations

## Regression Strategy

Use layered verification:

1. syntax / focused tests
2. module-specific regression
3. System regression when shared infrastructure is touched
4. full project regression before merge

Do not run full regression after every small edit.

When a test fails, determine whether the root cause is code, test, environment, permission, database, runtime state, dependency, or another module before changing unrelated code.

## Manual UI Smoke

For modules with UI, verify before merge:

- menu visibility
- route accessibility
- permissions
- CRUD/actions
- search/filter/reset behavior
- pagination/page-size behavior
- selection/bulk actions
- destructive confirmation modals
- Import/Export, including all-vs-selected export when applicable
- validation
- success/loading/error states
- refresh/persistence behavior
- Livewire behavior
- no unexpected 404/500
- no important browser console errors
- desktop/mobile usability
- module enable/disable behavior when applicable
- dependency/required rules when applicable
- Git remains clean after runtime operations

## Documentation

Create/update documentation under:

`docs/modules/<ModuleName>/`

Follow repository documentation conventions and avoid duplicate documents with overlapping responsibility.

Documentation should be sufficient for another developer/AI to understand, maintain, debug, refactor, or rebuild the module.

## Completion Criteria

Do not declare the module complete until applicable criteria pass:

- architecture review approved
- `Modules/ModuleServiceProvider.php` compatibility verified
- manifest correct
- runtime-state compatibility verified
- dependencies valid
- database/migrations pass
- services/business rules pass
- routes pass
- permissions pass
- UI/Livewire pass when applicable
- list search/filter/pagination/bulk UX passes when applicable
- Import/Export passes when applicable
- seeders pass when applicable
- Docker/runtime storage pass when applicable
- focused tests pass
- module regression passes
- System regression passes when applicable
- full project regression passes
- manual smoke passes
- `git status` is clean
- documentation is current
- no debug/temp files remain

Only after these gates pass should the module be proposed for merge into `main`.

## Rules

- Use namespace `Modules\\<ModuleName>`.
- Preserve repository folder casing/conventions; current module conventions favor lower-case `config`, `routes`, `resources`, and `database` where used.
- `Modules/ModuleServiceProvider.php` is the primary integration contract for new modules.
- Do not modify unrelated modules.
- Keep the first implementation minimal, coherent, production-safe, and reviewable.
- Do not introduce a new framework or module system that conflicts with the repository.
- Do not implement unresolved high-risk assumptions without explicit approval.
- Do not mutate tracked module manifests to represent runtime enable/disable state.
- For Admin UI, `.codex/standards/ADMIN_UI_STANDARD.md` is a completion contract, not optional visual guidance.
