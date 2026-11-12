# LoanManagement CI regression audit — 2026-11-12

## Observed GitHub Actions result

Workflow run `43140871033`, job `130927403151`, commit `28264ac4310d`: focused route/cache contract PASS; module regression `74 tests, 719 assertions, 2 errors, 12 failures`. This is **NOT** a module regression PASS.

## Confirmed root causes and boundaries

1. `LoanManagementBusinessIntegrationTest`: `loans:demo-seed` and `loans:data-clean` throw `CommandNotFoundException` in CI. The command classes exist at `Modules/LoanManagement/Console/Commands/{SeedLoanDemo,CleanLoanData}.php`; their handlers restrict execution to local/testing and require explicit interactive confirmation before destructive operations. `Modules/ModuleServiceProvider.php` registers console commands only for enabled modules. CI registration state must be verified before altering these safety controls. Do **not** run the destructive commands against Production.
2. The 12 contract failures are concentrated in `LoanManagementInsuranceContractTest`, `LoanManagementTransactionsContractTest`, `LoanManagementUiWorkspaceContractTest`, and `LoanManagementWorkspaceContractTest`. Several assert old workspace markup inside `Modules/ClientPortal/resources/views/applications/loans/dashboard.blade.php`; current dashboard is executive overview and feature workspaces have separate views. This is test/source contract drift, **not proof** that the actual capabilities are broken.
3. No change to Blade, business services, authorization, or Docker runtime is justified solely by these static contract failures. Each old assertion must be mapped to the canonical current view/controller/service and replaced with an equivalent behavioral/permission check. Preserve safety checks; do not delete tests merely to green CI.

## Next controlled batches

- **A: console registration** — inspect effective `ModuleRegistry` testing configuration and command discovery; add a safe isolated command-registration assertion. Fix bootstrapping if needed without weakening command interactive guards.
- **B: UI contract migration** — map each failed assertion to the current capability Blade and controller; replace only assertions that genuinely moved, keeping server authorization, financial validation, and PWA behavior covered. Keep the actual UI unchanged.
- **C: gates** — rerun focused → module regression → impacted ClientPortal tests. User must confirm UI PASS for any UI change. Do not merge until all required gates PASS.

Production UI PASS refers to the previously repaired running environment; the source branch has not been deployed. No Production tests, migrations, cache clears, service reloads or Git resets are authorized.
