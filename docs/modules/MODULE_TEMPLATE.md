# <Module> — Module Contract

> Copy this file to `docs/modules/<Module>/MODULE.md` and replace placeholders using runtime evidence. Do not invent ownership to fill the template.

## 1. Identity

- Module: `<Module>`
- Type: `shell | domain | integration | support`
- Status: `active | transitional | deprecated`
- Manifest: `Modules/<Module>/config/module.php`
- Routes: `Modules/<Module>/routes/web.php`
- Last architecture review: `<YYYY-MM-DD>`

## 2. Purpose

Describe why this Module exists and the responsibilities it owns.

## 3. Canonical Ownership

| Capability | Canonical owner | Runtime entry |
|---|---|---|
| `<capability>` | `<Module>` | `<route/controller/service>` |

## 4. Explicit Non-Ownership

| Capability | Canonical owner | Relationship |
|---|---|---|
| `<capability>` | `<OtherModule>` | `consumes / integrates / links` |

## 5. Dependencies

### Direct dependencies

| Module | Reason | Required |
|---|---|---|
| `<Module>` | `<reason>` | `Yes/No` |

Must remain synchronized with `Modules/<Module>/config/module.php`.

### Integration dependencies

Document cross-module capabilities consumed without transferring ownership.

## 6. Consumers

| Consumer | Capability |
|---|---|
| `<Module/surface>` | `<capability>` |

## 7. Canonical Routes

List the canonical route groups owned by this Module.

Ownership audit must trace:

`Route → Controller → View/Livewire → Service → Model/Persistence → Callers → Cross-module dependencies`.

## 8. Canonical Runtime Components

### Controllers

- ...

### Livewire / UI Components

- ...

### Services

- ...

### Models

- ...

Describe canonical boundaries; do not mechanically enumerate every file.

## 9. Persistence Ownership

| Table / storage | Owner | Migration/source | Notes |
|---|---|---|---|
| `<table>` | `<Module>` | `<migration>` | `<contract>` |

## 10. Integration Boundaries

For each important cross-module boundary document:

- business owner;
- consumer;
- allowed dependency direction;
- exchanged contract/data;
- behavior that must not be duplicated.

## 11. Compatibility / Deprecated Boundaries

| Artifact | Canonical replacement | Status | Removal condition |
|---|---|---|---|
| `<artifact>` | `<replacement>` | `deprecated` | `caller-proof + regression` |

Deprecated does not mean dead code.

## 12. Quarantine

List dangerous, persistence-sensitive, or insufficiently proven boundaries.

Quarantine means do not expand, rehome, beautify, or delete outside a separately approved phase.

## 13. Refactor Invariants

Every refactor must preserve the Module's approved:

1. canonical routes;
2. authentication/authorization boundaries;
3. middleware/permissions;
4. persistence contracts;
5. public integration contracts;
6. required UI slots/stacks/scripts/assets where applicable;
7. dependency direction;
8. business ownership boundaries;
9. compatibility artifacts until caller-proof exists;
10. quarantine boundaries unless separately approved.

Add Module-specific invariants below.

## 14. Required Refactor Audit

Before implementation:

`Route → Controller → View/Livewire → Service → Model/Persistence → Callers → Cross-module dependencies`

Classify affected artifacts:

- `KEEP`
- `REHOME`
- `DELETE`
- `QUARANTINE`
- `DEFER`

If this contract disagrees with runtime, mark `ARCHITECTURE DRIFT`, audit the evidence, propose target architecture, and obtain approval before implementation.

## 15. Required Regression Scope

Document minimum focused/module/impacted tests, route checks, Pint/build requirements, and manual UI/PWA acceptance applicable to this Module.

## 16. Architectural Change Rules

`MODULE.md` is the architectural source of truth for this Module.

Update this file in the same PR whenever changing:

- responsibility;
- ownership/non-ownership;
- direct dependencies;
- canonical routes;
- integration boundaries;
- persistence ownership;
- compatibility/deprecation;
- quarantine;
- refactor invariants.

Source and `MODULE.md` must not merge with conflicting architectural contracts.

## 17. Deferred Debt

| Debt | Owner/target module | Reason | Exit condition |
|---|---|---|---|
| `<debt>` | `<Module>` | `<reason>` | `<proof/condition>` |

## 18. Architecture Decisions

Only record significant architectural decisions, not routine changelog entries.

### YYYY-MM-DD — <Decision>

**Decision:** ...

**Reason:** ...

**Impact:** ...
