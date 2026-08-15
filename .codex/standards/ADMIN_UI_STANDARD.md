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

For operational/admin datasets, the default list workspace SHOULD also evaluate and implement when applicable:

- Keyword search.
- Domain-relevant filters such as status, category/type, owner, date range or related entity.
- A clear reset-filter action.
- Row selection with checkboxes when bulk actions or selected export are supported.
- Bulk actions that are permission-aware and explicitly confirmed when destructive.
- Import/Export through the repository's canonical shared infrastructure when the dataset is reasonably portable and the business workflow benefits from it.

Do not add meaningless filters or Import/Export only to satisfy a checklist. Document why they are not applicable when omitted from a newly created operational module.

If a table is secondary to an editor/workflow, place it in its own workspace tab or mode rather than permanently shrinking the primary task surface.

## Pagination Standard

Potentially large admin datasets MUST use bounded pagination.

Canonical expectations:

- Never offer an unbounded `All` page-size option for production-capable datasets.
- Recommended page-size choices are `10`, `25`, `50`, `100` unless domain constraints justify a different bounded set.
- Invalid/tampered page-size values must normalize to a safe default.
- Pagination controls must match the admin visual language; active pages should use the repository accent/indigo treatment rather than a visually heavy black/dark block unless the active theme explicitly requires dark styling.
- Previous/next controls, disabled state and current page must remain clear and keyboard usable.
- Pagination belongs below the dataset with stable spacing and should not visually dominate the table.
- Changing search/filter/page-size must reset pagination when the current page may become invalid.
- Selection semantics must be explicit: selecting the page is not the same as selecting the entire dataset.
- Do not accidentally export only the current page when export scope is intended to be all or selected records.

When a canonical/shared pagination view exists, reuse it rather than creating module-specific variants. A module-specific pagination view is acceptable only when no shared equivalent exists yet and it follows this standard closely enough to be extracted later.

## Filters and Search Standard

For list workspaces with more than a trivial dataset:

- Keep the most useful search/filter controls visible without overwhelming the page.
- Use debounce for free-text live search when appropriate.
- Reset page and row selection when filters change.
- Make the active scope understandable to the operator.
- Provide `Xóa bộ lọc` / reset behavior when more than one filter can be active.
- Avoid database work from Blade; filtering belongs in component/service/query layers.
- Keep filter values bounded and validated where they can affect query shape.

## Row Selection and Bulk Actions

When checkboxes are present:

- Selection state belongs to the owning Livewire component.
- A header checkbox should mean the visible page unless the UI explicitly states "select all matching records".
- Show selected count when bulk actions are available.
- Destructive bulk actions require a clear modal confirmation, not a subtle inline message far from the initiating button.
- Confirmation modal copy must state the number/scope of affected records when known.
- Buttons must use `type="button"` unless they intentionally submit a form.
- Loading/disabled state must prevent duplicate execution.
- Authorization must be enforced server-side for the action; hiding the button is not sufficient.

## Import / Export UI

When Import/Export is applicable, reuse:

```text
Modules/Shared/Services/ImportExport
Modules/Shared/Livewire/ImportExport
shared.import-export.panel
```

and follow `.codex/tasks/create-import-export.md`.

Canonical selected export behavior for checkbox-enabled list screens:

```text
selected_ids empty     -> export all records in the approved export scope
selected_ids not empty -> export only selected records
```

Additional UI requirements:

- Do not silently equate "no selection" with "current page only".
- Checkbox visibility must support users allowed to export selected rows even if they do not have delete permission.
- Import/Export is a secondary tool: keep it visually subordinate to the primary list/workflow, using a collapsible/secondary panel where useful.
- Show loading/disabled states for template generation, import and export.
- Successful import/dry-run/export should use the canonical success modal when available, with an explicit OK/refresh action.
- Row-level import errors should remain inspectable inline after failure.
- Exported files should be importable back for update/upsert when safe and practical; never expose secrets merely to achieve round-trip compatibility.

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

For destructive list actions, prefer centered modal confirmation with overlay, clear scope, Cancel and explicit destructive confirmation. Avoid rendering the confirmation at the bottom of a long list where the operator may not see it.

Successful operations that materially change the dataset should provide explicit feedback. When refresh is needed to synchronize parent/child Livewire state, use the canonical success modal with an `OK — tải lại` style action rather than relying only on transient flash text.

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
- Unbounded `All` pagination.
- Black/heavy active pagination styling that conflicts with the repository accent treatment.
- Destructive confirmation rendered far below the action that triggered it.
- Export that ignores selected rows or exports only the visible page by accident.

## Quality Gate

Before UI work is complete, verify:

- It matches the repository's current admin layout and shared components.
- The screen has a clear primary task and secondary functions use progressive disclosure where appropriate.
- Form validation UX is clear.
- Loading/disabled states prevent accidental repeated mutations where needed.
- Empty states exist for empty collections.
- Tables are responsive and paginated/bounded when necessary.
- List screens have appropriate search/filter/reset controls or document why they are unnecessary.
- Pagination follows the bounded page-size and accent-style standard.
- Bulk selection/actions have clear scope, selected count and modal confirmation when destructive.
- Import/Export follows the shared selected-export and success-feedback contracts when applicable.
- Currency/number fields store clean values.
- Long-form editor/viewer surfaces use the available viewport intentionally.
- Workspace/editor/viewer proportions have been visually inspected in real screenshots.
- UI remains usable on desktop and mobile.
- Fullscreen/drawer/tab interactions have clear exit/navigation behavior.
- No business logic or database access leaked into Blade.
