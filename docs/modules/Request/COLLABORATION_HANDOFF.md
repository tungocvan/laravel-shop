# Request Module — Collaboration Handoff

- Last updated: 2026-08-27
- Repository: `tungocvan/laravel-shop`
- Base branch: `main`
- Working branch: `fix/request-demo-seeder-config-cache`
- Pull request: **PENDING — not created**
- Merge commit: **NOT AVAILABLE**
- Main checkpoint inspected: `62afb2e31b9fcf68656768885fee8ffcf3a5ca5b`
- Source checkpoint before handoff refresh: `e3d5b4e34e413bf70e976cb40c489f6a5134cb33`

## Checkpoint status

- Request ClientPortal MR-3 through MR-5: **COMPLETED**
- PR #53 production E2E demo-seeder opt-in: **COMPLETED / MERGED**
- PR #54 handoff closeout: **COMPLETED / MERGED**
- Current config-cache corrective batch: **IN PROGRESS — source/test/docs implemented; owner focused test pending**
- Production demo-data execution after this correction: **NOT YET RETRIED**
- Next Request application MR/phase: **NOT DETERMINED**

## Production defect reproduced

Production used cached Laravel configuration. With host `.env` containing:

```text
REQUEST_ENV=true
```

the exact application container reported:

```text
app_env             = production
env_REQUEST_ENV     = null
config_demo_enabled = true
```

`RequestE2EDemoSeeder` still rejected execution because PR #53 used direct runtime access:

```php
env('REQUEST_ENV', false)
```

This is incompatible with the production config-cache contract. `env()` outside config files may return `null` after Laravel configuration is cached even though the effective application config is correct.

## Corrective scope

This branch is intentionally narrow:

1. change `RequestE2EDemoSeeder` to gate production execution using:

```php
config('request.settings.demo_seeders_enabled', false)
```

2. add a focused contract test preventing a regression back to direct `env('REQUEST_ENV')` access;
3. update `docs/modules/Request/DEMO_SEEDER_RUNBOOK.md` with cached-config behavior and corrected Artisan namespace quoting;
4. add `docs/PRODUCTION_DOCKER_WORKFLOW_GUARDRAILS.md` so the `env()` vs `config()` rule and Compose-project/runtime lessons apply to future production work;
5. do not alter Request domain workflow, Module state, permission model, migrations or unrelated seeders.

## Files before this handoff-only update

GitHub comparison against `main` showed branch `ahead`, `behind_by: 0`, with exactly:

```text
database/seeders/RequestE2EDemoSeeder.php
tests/Feature/Modules/RequestE2EDemoSeederConfigGateTest.php
docs/modules/Request/DEMO_SEEDER_RUNBOOK.md
docs/PRODUCTION_DOCKER_WORKFLOW_GUARDRAILS.md
```

This handoff file is the fifth expected PR file and exists to satisfy the collaboration gate before PR creation.

## Runtime configuration rule

Production runtime must follow:

```text
.env
  ↓
config files call env()
  ↓
Laravel cached/effective configuration
  ↓
application / command / seeder / service calls config()
```

Do not use direct `env()` access in application runtime as an acceptance gate.

For Request demo seeding:

```text
REQUEST_ENV=true
        ↓
request.settings.demo_seeders_enabled=true
        ↓
RequestE2EDemoSeeder reads config()
```

`optimize:clear` was useful only to diagnose the defect. It must not become the normal workaround for a runtime component that reads `env()` incorrectly.

## Seeder command correction

Before seed, verify the class exists:

```bash
docker compose -p tnv exec -T app php artisan tinker --execute='
dump(class_exists(\Database\Seeders\RequestE2EDemoSeeder::class));
'
```

Canonical Docker command:

```bash
docker compose -p tnv exec -T app php artisan db:seed \
  --class="Database\\Seeders\\RequestE2EDemoSeeder" \
  --force
```

The shell must pass the actual PHP class name with single namespace separators:

```text
Database\Seeders\RequestE2EDemoSeeder
```

## Production safety boundary

This branch does not itself:

- seed production data;
- enable/disable Request;
- bypass permission checks;
- change Role/Spatie infrastructure;
- migrate/reset the database;
- clear existing demo data;
- authorize a new Request application feature phase.

Production retry after merge remains a separate explicit operation on the correct Compose project/runtime.

## Focused verification required

Owner local test should be limited to:

```bash
php artisan test tests/Feature/Modules/RequestE2EDemoSeederConfigGateTest.php
php -l database/seeders/RequestE2EDemoSeeder.php
git diff --check main...HEAD
```

Do not run the full test suite for this corrective batch unless a focused failure expands the scope.

## PR gate status

- [x] Corrective branch created from current `main`.
- [x] Branch comparison: ahead, behind_by=0 before handoff refresh.
- [x] Seeder source corrected to runtime `config()`.
- [x] Focused regression test added.
- [x] Request demo runbook corrected.
- [x] Production Docker guardrails added.
- [x] Handoff refreshed before PR.
- [ ] Owner focused test PASS.
- [ ] Pull request created and actual PR number recorded.
- [ ] PR review/diff gate completed.
- [ ] Merge explicitly authorized.
- [ ] Production E2E seeder retried after merged/deployed correction.

## Next authorized step

1. Owner switches local repository to `fix/request-demo-seeder-config-cache` and runs only the focused verification commands above.
2. If PASS, create/review the corrective PR into `main`.
3. Do not merge until explicitly approved.
4. After merge/deploy, verify effective config through `config()` and retry the production E2E seeder on the correct `tnv` stack.
5. Do not begin a new Request feature MR/phase as part of this corrective batch.
