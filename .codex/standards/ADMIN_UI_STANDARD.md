# ADMIN UI STANDARD

Canonical UI/UX standard for admin module work in this repository.

Derived from the Laravel Admin UI master guidance and reconciled with the current project stack and approved admin workspace patterns.

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
- Focused on the user's primary task instead of exposing every feature at once.

## Technology

- Laravel Blade.
- Livewire 3.1 class-based.
- Tailwind CSS 4 where the repository already uses/approves it.
- Existing Bootstrap/AdminLTE code may be preserved when required for compatibility; do not introduce new competing patterns when an approved shared component already solves the problem.
- Avoid jQuery unless an existing dependency genuinely requires it.
- Avoid inline CSS when the existing design system/utilities can express the UI.

## Layout

Admin pages must use the repository's canonical admin layout, currently `Admin::layouts.master` where applicable.

The shared admin shell should generally use the available content width with modest responsive padding. Avoid centering every page inside a narrow global `max-width` when the admin shell already owns width and spacing. Individual pages or reading/form surfaces may apply their own maximum width when that improves usability.

Page structure should provide:

- Clear title.
- Short contextual description when useful.
- Primary action area when applicable.
- Responsive spacing/container.
- Consistent card/section structure.
- A clear visual hierarchy between navigation, primary workspace and secondary information.

Page Blade remains a shell; interactive feature UI belongs in Livewire Blade.

## Workspace-first Admin Screens

For feature-rich modules, prefer a focused workspace instead of stacking many independent management cards vertically on one long page.

A professional workspace usually follows this hierarchy:

```text
Module Header
├── Search / primary actions
├── Context / status
└── Workspace
    ├── Navigation / library / filters
    └── Main task surface
```

Guidelines:

- Keep the primary task visible and give it the majority of the available width.
- Move secondary functions into tabs, drawers, collapsible panels, menus or dedicated workspace modes.
- Avoid rendering Search + Create Form + Table + Settings + Sync/Import/Export all expanded at the same time unless the workflow truly requires it.
- Sidebar/navigation width should be intentional and compact; do not let secondary navigation dominate the workspace.
- On mobile/tablet, collapse persistent sidebars into drawers, tabs or searchable pickers.
- Preserve context when switching workspace modes where practical.
- Avoid nested global containers that introduce duplicated padding or large dead space.

## Editor-first Screens

When a module's main task is authoring or editing long-form content, code, configuration, templates or structured text, the editor is the primary workspace and should receive most of the viewport.

Recommended pattern:

```text
Editor Header / Context
Collapsible Metadata
Authoring Toolbar
Large Editor / Split Preview
Status + Primary Save Action
```

Guidelines:

- Give long-form editors a practical viewport height, commonly around `60–70vh` on desktop when appropriate.
- Keep metadata such as slug, description, status and sort order compact or collapsible so it does not push the editor below the fold.
- Keep primary save/publish actions easy to find.
- Provide fullscreen/focus mode when authoring benefits from additional space.
- For source formats such as Markdown, keep the persisted source canonical; toolbar buttons should generate source syntax rather than silently converting the storage model to arbitrary HTML.
- A rich toolbar may provide common authoring actions such as bold, italic, headings, lists, task lists, quote, link, image, inline code, fenced code, table, horizontal rule, undo/redo and preview.
- Do not introduce a heavyweight WYSIWYG editor when a lightweight source-aware toolbar solves the requirement better.

## Preview and Split View

When users benefit from seeing rendered output while editing:

- Prefer explicit modes such as `Editor`, `Split`, and `Preview`.
- Desktop split view should preserve enough width for both source and rendered output.
- Mobile should switch modes with tabs rather than force two narrow columns.
- Preview must use the same safe rendering rules as the final viewer whenever feasible.
- Preview must not bypass output sanitization or permission boundaries.

## Progressive Disclosure

Secondary or infrequent operations should not compete visually with the primary workflow.

Appropriate progressive-disclosure patterns include:

- Collapsible metadata panels.
- Modal/drawer for short create/edit forms.
- Tabs for separate tasks such as editor/list/sync/history.
- `More` menus for infrequent actions.
- Searchable pickers instead of persistent large trees when the dataset is primarily navigational.

Do not hide critical validation, destructive warnings or required workflow state behind unclear interactions.

## Forms

- Labels must be clear.
- Validation errors appear next to the relevant field.
- Inputs, selects, textareas and buttons should have consistent sizing and visual language.
- Group complex forms into clear sections/cards.
- Use responsive grids.
- Long fields such as address/description/note may span full width.
- Disabled/read-only states must be visually clear.
- Avoid placing a long primary editor inside a narrow form column merely to keep another table visible beside it.

## Searchable Select / Combobox

Reuse the repository's approved searchable-select component (for example `x-select-search`) when it satisfies the requirement. Do not build another combobox implementation without a documented reason.

Use ordinary selects for short/simple option lists.

For large navigation collections, a searchable picker/command-style selector may be preferable to a permanently expanded tree, especially when it can combine search, recent items and favorites without obscuring the main workspace.

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

If a table is secondary to an editor/workflow, place it in its own workspace tab or mode rather than permanently shrinking the primary task surface.

## Reading / Viewer Experiences

Documentation, knowledge-base and long-form viewer screens should optimize for comfortable reading rather than raw content density.

Recommended principles:

- Keep article line length readable even when the card uses a wide viewport.
- Use TOC/navigation without excessively reducing article width.
- Provide reading/focus mode where useful.
- Fullscreen mode should use the real viewport and remove unrelated admin chrome when appropriate.
- Desktop navigation may be persistent; mobile navigation should generally become a drawer/picker.
- Previous/next navigation should remain discoverable without creating large empty layout rows.
- Images must be responsive; code blocks must not force the entire page to overflow horizontally.

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
- UI-only state such as tabs, drawers, local toolbar selection and fullscreen may use lightweight Alpine/browser APIs when server synchronization is unnecessary.

## Reuse

Before creating UI primitives, inspect existing shared components. Reuse canonical components for inputs, searchable selects, alerts, modals, pagination, status badges, upload controls and similar patterns when available.

Do not create duplicate UI systems inside individual modules.

Specialized UI such as a Markdown source toolbar may be module-specific when no shared equivalent exists, but it should still follow the repository's visual language and remain small enough to extract later if reused elsewhere.

## Accessibility and Responsive Behavior

- Maintain keyboard/focus usability for interactive controls.
- Use semantic labels and button types.
- Keep focus/error states visible.
- Ensure tables/forms remain usable on small screens.
- Do not depend on color alone to communicate critical state.
- Toolbars should expose meaningful `title`/`aria-label` text for icon-only actions.
- Drawers/modals should have a clear close action and preserve usable focus behavior.
- Fullscreen/focus modes must provide an obvious exit path; native `Esc` behavior should be preserved when using the Browser Fullscreen API.

## Visual Balance

A screen can be technically responsive and still feel unprofessional if proportions are poor.

Before considering UI complete, inspect actual rendered screenshots at representative desktop and mobile widths and verify:

- Primary content starts close enough to the active sidebar/shell without excessive dead space.
- Left/right columns are proportional to their information density.
- Article/editor width is intentional, not an accidental result of nested `max-width` containers.
- Empty space supports readability instead of making controls appear disconnected.
- Footer/navigation controls do not consume half the viewport when their content is small.
- Header actions wrap cleanly and do not overpower the page title.

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
- Stacking every module feature as a permanently expanded card on one long page.
- Nested `mx-auto/max-w-*` wrappers that create unexplained dead space inside an already constrained admin shell.
- Giving secondary tables/navigation more space than the primary authoring/workflow surface.
- Using WYSIWYG HTML as a shortcut when the domain source of truth is Markdown or another structured text format.

## Quality Gate

Before UI work is complete, verify:

- It matches the repository's current admin layout and shared components.
- The screen has a clear primary task and secondary functions use progressive disclosure where appropriate.
- Form validation UX is clear.
- Loading/disabled states prevent accidental repeated mutations where needed.
- Empty states exist for empty collections.
- Tables are responsive and paginated/bounded when necessary.
- Currency/number fields store clean values.
- Long-form editor/viewer surfaces use the available viewport intentionally.
- Workspace/editor/viewer proportions have been visually inspected in real screenshots.
- UI remains usable on desktop and mobile.
- Fullscreen/drawer/tab interactions have clear exit/navigation behavior.
- No business logic or database access leaked into Blade.
