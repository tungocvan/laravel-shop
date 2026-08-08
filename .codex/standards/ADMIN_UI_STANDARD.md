# ADMIN UI STANDARD

Canonical UI/UX standard for admin module work in this repository.

Derived from the Laravel Admin UI master guidance and reconciled with the current project stack.

## Priority

Repository reality and existing shared components take precedence over generic examples in older master prompts. Do not perform unrelated global frontend migrations while implementing one module.

## Goals

Admin UI should be:

- Professional and clean.
- Consistent across modules.
- Responsive.
- Accessible and easy to operate.
- Easy to maintain.
- Practical rather than over-engineered.

## Technology

- Laravel Blade.
- Livewire 3.1 class-based.
- Tailwind CSS 4 where the repository already uses/approves it.
- Existing Bootstrap/AdminLTE code may be preserved when required for compatibility; do not introduce new competing patterns when an approved shared component already solves the problem.
- Avoid jQuery unless an existing dependency genuinely requires it.
- Avoid inline CSS when the existing design system/utilities can express the UI.

## Layout

Admin pages must use the repository's canonical admin layout, currently `Admin::layouts.master` where applicable.

Page structure should provide:

- Clear title.
- Short contextual description when useful.
- Primary action area when applicable.
- Responsive spacing/container.
- Consistent card/section structure.

Page Blade remains a shell; interactive feature UI belongs in Livewire Blade.

## Forms

- Labels must be clear.
- Validation errors appear next to the relevant field.
- Inputs, selects, textareas and buttons should have consistent sizing and visual language.
- Group complex forms into clear sections/cards.
- Use responsive grids.
- Long fields such as address/description/note may span full width.
- Disabled/read-only states must be visually clear.

## Searchable Select / Combobox

Reuse the repository's approved searchable-select component (for example `x-select-search`) when it satisfies the requirement. Do not build another combobox implementation without a documented reason.

Use ordinary selects for short/simple option lists.

## Number and Currency

Human-facing money values should be formatted for readability. Persist clean numeric values without thousands separators. Never persist formatted currency strings as monetary database values.

Clearly distinguish money, quantity and percentage inputs.

## Tables and Lists

Tables/lists should include, when relevant:

- Clear headers.
- Search/filter controls.
- Pagination for potentially large datasets.
- Responsive horizontal overflow for tables.
- Clear actions.
- Consistent status badges.
- Empty state.
- Loading state for asynchronous actions.

Do not render unbounded production datasets merely to support an `All` option.

## Loading and Mutation UX

Actions such as save, delete, bulk actions, import and export should expose loading/disabled state when they can take noticeable time or can be double-submitted.

Dangerous actions should be visually distinct and use confirmation where appropriate.

## Livewire

- Use class-based components.
- Prefer the repository-approved binding pattern; `wire:model.live` is appropriate for genuinely live interactions.
- Avoid unnecessary network requests for fields that do not require immediate synchronization.
- Livewire handles UI state/validation and calls services.
- Do not query the database from Blade.
- Do not place core business logic in Blade or Livewire.

## Reuse

Before creating UI primitives, inspect existing shared components. Reuse canonical components for inputs, searchable selects, alerts, modals, pagination, status badges, upload controls and similar patterns when available.

Do not create duplicate UI systems inside individual modules.

## Accessibility and Responsive Behavior

- Maintain keyboard/focus usability for interactive controls.
- Use semantic labels and button types.
- Keep focus/error states visible.
- Ensure tables/forms remain usable on small screens.
- Do not depend on color alone to communicate critical state.

## Anti-patterns

Avoid:

- Database queries in Blade.
- Business logic in Blade.
- Inconsistent form control sizing.
- Non-responsive tables.
- Missing validation feedback.
- Missing empty/loading states where needed.
- Persisting formatted currency strings.
- Duplicating existing shared components.
- Introducing a new CSS/UI framework for a single feature.
- Rebuilding unrelated screens merely to make one module visually uniform.

## Quality Gate

Before UI work is complete, verify:

- It matches the repository's current admin layout and shared components.
- Form validation UX is clear.
- Loading/disabled states prevent accidental repeated mutations where needed.
- Empty states exist for empty collections.
- Tables are responsive and paginated/bounded when necessary.
- Currency/number fields store clean values.
- UI remains usable on desktop and mobile.
- No business logic or database access leaked into Blade.
