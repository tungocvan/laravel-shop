# Decisions

## ADR-001: Admin Module Is A Presentation Shell

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-06-27 |

### Context

Repository bootstrap documentation defines `Admin` as a shell module. Business domains such as Product, Order, Post, Category, Account, Admission, and Pharma should own domain behavior.

### Decision

The admin layout documentation treats Admin as a presentation shell. Layout, navigation, theme, and admin UI composition can live in Admin. Domain workflows should remain in their owning modules or services.

### Consequences

- Layout refactors must avoid moving domain behavior into Admin.
- Admin navigation can link to domain features but should not become their canonical owner.

## ADR-002: Preserve Existing Master Layout Compatibility

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-06-27 |

### Context

Current pages may use either Blade sections or component slots.

### Decision

Any rebuild must preserve `@yield('content')`, `$slot`, stacks, sections, Vite assets, and Livewire resource support unless a migration plan is explicitly approved.

### Consequences

- Rebuild can be incremental.
- Existing pages do not need simultaneous edits.

## ADR-003: Move Navigation Decisions Out Of Blade

| Field | Value |
|---|---|
| Status | Proposed |
| Date | 2026-06-27 |

### Context

The current sidebar Blade filters permissions and computes active state inside the view.

### Decision

Future refactors should move permission pruning and active/open state into a navigation service or view model.

### Consequences

- Blade becomes simpler.
- Navigation behavior becomes testable.
- Permission visibility changes must be verified carefully.

## ADR-004: Use Semantic Theme Tokens

| Field | Value |
|---|---|
| Status | Proposed |
| Date | 2026-06-27 |

### Context

The current sidebar theme config stores Tailwind class fragments. Some are unused because views hard-code colors.

### Decision

Future theme work should define semantic tokens and map them to Tailwind classes in one layer.

### Consequences

- Themes become more consistent.
- Arbitrary class injection risk is reduced.
- Tailwind safelisting/build visibility must be considered.

## ADR-005: Centralize Global JavaScript

| Field | Value |
|---|---|
| Status | Proposed |
| Date | 2026-06-27 |

### Context

Search shortcut and toast behavior currently live inside Blade component scripts.

### Decision

Future layout JavaScript should live in Vite-managed modules with idempotent initialization.

### Consequences

- Better compatibility with future `wire:navigate`.
- Easier testing and cleanup.
- Blade views become more declarative.

## ADR Template

Use this format for future decisions:

```md
## ADR-XXX: Title

| Field | Value |
|---|---|
| Status | Proposed/Accepted/Superseded |
| Date | YYYY-MM-DD |

### Context

Describe the problem and constraints.

### Decision

State the chosen direction.

### Consequences

List benefits, tradeoffs, and follow-up requirements.
```

