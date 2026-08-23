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

### Form Control Visual Standard

Admin form controls MUST remain visibly identifiable even when empty. Do not rely on placeholder text, background contrast or browser defaults to show that a field exists.

For ordinary light-theme text inputs, selects, date/number controls and textareas, the preferred baseline visual language is:

```text
w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900
placeholder:text-gray-400
focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100
```

Add `mt-1` when the control follows a label directly and the surrounding field component does not already own vertical spacing.

Example:

```html
<input
    wire:model="applicant_name"
    type="text"
    autocomplete="name"
    class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
>
```

Canonical states:

- Normal: `border-gray-300 bg-white text-gray-900`.
- Hover when useful: `hover:border-gray-400`.
- Focus: `border-indigo-500 ring-2 ring-indigo-100` with no heavy/dark outline.
- Validation error: `border-red-400 focus:border-red-500 focus:ring-red-100`; show the error text immediately below the field.
- Disabled: `cursor-not-allowed border-gray-200 bg-gray-100 text-gray-500 opacity-75`.
- Read-only: visually distinguish it from editable controls, normally `bg-gray-50 text-gray-600`, while retaining a visible border.

Additional rules:

- Do not ship borderless ordinary admin inputs unless a deliberately designed specialized control (for example inline table editing, command palette, or editor surface) clearly requires it.
- `border-transparent`, `border-0`, or an input whose border visually disappears into its card/background is not the default admin form style.
- Selects and textareas should use the same border radius, border color, focus treatment and text sizing as text inputs unless their interaction requires otherwise.
- Search/filter inputs may be slightly more compact, but must still have a visible border and focus state.
- Prefix/suffix/icon controls must preserve the same visible outer boundary; do not make the input appear detached from its icon/action.
- Placeholder text is supplementary guidance, not a replacement for a visible label when a label is required.
- When a canonical shared input/form component exists, update/reuse that component instead of copying long utility-class strings across every module.

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
- Search/filter inputs and selects must retain a visible border in their resting state; do not make an empty filter row look like blank whitespace.

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

## Canonical Admin Shell UI/UX

The approved Admin shell is a configurable system composed of `Sidebar + Header + Content + Footer`. New AI-generated work MUST preserve this ownership model instead of styling each region independently in feature views.

### Shell ownership and full-width behavior

- Sidebar width is configuration-driven; do not hardcode the expanded width in feature views. Collapsed width is also owned by the shell/presentation layer.
- When Sidebar fullscreen-hide is active, Sidebar disappears completely and Header, Content and Footer consume the available full width.
- Sidebar toggle controls belong to the shell. Their visibility is configurable and defaults should keep both normal collapse and fullscreen-hide controls enabled unless product requirements say otherwise.
- Footer is a shell region and should remain at the bottom of the viewport/content layout. It must not float upward merely because the current content is short.
- Header height, Sidebar width, container mode and region surfaces are presentation configuration, not page-level constants.
- Avoid duplicate shell padding, nested global containers or page-specific width hacks that fight the configured shell.

### Header UX

- Brand/title width should be content-aware (`auto`) with safe truncation only when the available viewport requires it; do not impose an arbitrary narrow fixed width.
- Header actions should be compact, icon-led and visually discoverable. Icons require sufficient foreground/background contrast and must not disappear into the Header surface.
- System actions such as Notifications are managed consistently with other Header Actions while preserving their system-owned behavior.
- Dense action configuration should use a compact summary row/card and explicit Edit interaction rather than exposing every input/select permanently.
- UserMenu should follow the same compact summary/edit pattern when configuration becomes dense.
- Header preview in settings should reflect meaningful presentation changes before save when practical.

### Sidebar UX and navigation hierarchy

- Sidebar Header, Navigation and Footer are separate configurable surfaces. Their backgrounds may differ while preserving readable contrast.
- Navigation search is configurable and should remain compact.
- Sidebar header title is configurable; footer/profile visibility is configurable.
- Menu Item and SubMenu typography, icon size, title/icon colors, spacing, padding, indent and active-state presentation belong to the Design/Theme system, not `/admin/menus` structural data.
- `/admin/menus` owns menu structure, route/URL, permission, icon choice, ordering and structural management. It may link to Design settings but must not duplicate presentation controls.
- Menu Item active and a parent Menu Group whose child is active/open MUST use the same top-level `Menu Active` presentation.
- A selected SubMenu item uses a distinct `Sub Item Active` presentation so hierarchy remains obvious.
- Active parent state is inherited from an actually active child, not from loose URL-prefix matching.
- Concrete menu URLs use exact route/path matching by default. Example: `/admin/system/settings/env` must not independently activate sibling items configured as `/admin/system` or `/admin/system/settings`.
- Opening a Menu Group may visually use the same top-level active treatment, but must not falsely mark unrelated sibling routes active.

### Footer UX

- Footer should be visually quiet and subordinate to the workspace.
- Copyright/owner information is configurable.
- Current date/time may be displayed in the approved local format instead of exposing environment/debug information to ordinary operators.
- Keep Footer content aligned, compact and responsive; it must not compete with primary actions.

## Theme, Design Tokens and Customization

Presentation customization MUST flow through the canonical Design/Theme system and semantic variables. Do not introduce isolated hardcoded color/font values in Header, Sidebar, Content or Footer when a semantic token/configuration already exists.

### Theme editor responsibilities

- A selected theme exposes editable semantic parameters for Sidebar, Header, Content and Footer.
- Users may save a customized configuration as a new theme, duplicate an existing custom theme, and delete custom themes when allowed.
- Built-in/default themes are protected from accidental deletion.
- A clear `Khôi phục Theme mặc định` action must restore the repository-approved professional defaults.
- Preview should communicate the relationship between Sidebar, Header, Content and Footer rather than previewing only one region.
- Region background selectors should support the approved theme color palette instead of artificially limiting users to a few hardcoded choices.
- Foreground/icon/text contrast must be derived or validated so custom backgrounds do not make controls unreadable.

### Typography and Font Library

- Admin Base Font Family belongs to the shared Design typography configuration.
- Region/component typography may use `Inherit` to inherit the Admin base font.
- Font choices come from one shared Font Library. Do not hardcode separate font lists in Sidebar, Header and Footer.
- Every selectable font must map to an approved CSS font stack/fallback. A font name must not appear as available merely because it was typed into a dropdown.
- External/downloaded font support, when introduced, must define loading, fallback and failure behavior explicitly.
- Restore Default returns typography to the approved professional baseline.

### Sidebar Menu design tokens

At minimum, the Theme system may own:

```text
Menu Item
- Font Family / Font Size / Font Weight
- Title Color / Icon Color / Icon Size
- Item Height
- Padding X / Padding Y
- Content Gap / Item Gap
- Active Title Color / Active Icon Color
- Active Border Color / Width / Style

SubMenu
- Font Family / Font Size / Font Weight
- Title Color
- Indent / Offset
- Padding X / Padding Y / Item Gap
- Active background/text treatment
- Active Border Color / Width / Style

Menu Group
- Group spacing
```

Defaults should be visually balanced and usable immediately. Restore Default must restore all related values together; it must not leave stale custom spacing, border or typography values behind.

## Configuration Screen UX

Configuration pages can become visually noisy quickly. Prefer progressive editing over spreadsheet-like exposure of every field.

- Each section begins with a clear title and one short explanation of what it controls.
- Group related fields into semantic cards such as `Brand`, `Core components`, `Header Actions`, `UserMenu`, `Presentation`, `Sidebar Header`, `Navigation`, `Sidebar Footer`, `Theme colors`, and `Typography`.
- Use consistent control height, radius, border, label spacing and focus treatment across text inputs and comboboxes.
- For repeated records such as Header Actions or UserMenu items, default to a compact readable summary. Expose detailed fields through Edit/Add interaction.
- Keep status/enable controls inside the record boundary. Columns must never overflow their card or collide with preview/secondary panels.
- `Add`, `Edit`, `Delete`, `Duplicate`, `Save`, and `Restore Default` actions must have clear hierarchy. Primary Save should be visually strongest; destructive actions should not visually compete until needed.
- Live preview is useful for visual settings, but preview must not become wider or more prominent than the actual settings workspace.
- Sticky save actions are acceptable when the settings page is long, provided they do not cover content or Footer.

## Density, Spacing and Professional Defaults

Professional Admin UI in this repository favors compact-but-comfortable density rather than oversized consumer-style controls.

- Repeated navigation rows should have predictable rhythm and minimum usable click targets.
- Use spacing tokens/configuration for repeated shell/menu geometry rather than scattered hardcoded values.
- Cards should have enough padding to separate concepts, but avoid large empty areas around short settings.
- Related controls should align to a consistent grid. A single orphan field should not force an entire oversized row unless its content requires full width.
- Preview/secondary columns should be narrower than the primary settings/workspace column unless preview is the main task.
- Long settings pages should preserve clear section boundaries and stable vertical rhythm.
- Default values must be deliberately selected to produce a professional Admin immediately after install/reset; `Restore Default` is a product feature, not merely a technical reset.

## State, Route and Interaction Correctness

Visual state must correspond to real application state.

- Active navigation state must not be inferred with unsafe prefix matching when routes can nest.
- Parent active state should be derived from an active descendant or explicit open state according to the component contract.
- UI-only open/collapse/fullscreen state may live in Alpine/browser state when server persistence is unnecessary.
- Server-owned configuration remains in Livewire/services/configuration layers.
- Do not move browser-only state into Livewire merely to make it persistent unless persistence is a stated requirement.
- A successful Save/Reset that changes shell presentation must refresh/reconcile the shell predictably; do not require the operator to manually hard-refresh without feedback.

## AI Implementation Workflow for Admin UI

When an AI changes Admin UI, it MUST follow this sequence:

1. Inspect the current runtime component, its Livewire/service owner, existing Design tokens and relevant contract tests before editing.
2. Preserve ownership boundaries: structural data, presentation configuration, shell state and business logic must not be mixed.
3. Reuse existing semantic variables/components before adding new ones.
4. Prefer configuration-driven values over hardcoded width, height, color, font, spacing or visibility values when the setting is intended to be administrable.
5. Keep the first implementation visually compact and professional; do not expose every editable property simultaneously when summary + Edit is clearer.
6. Add/update focused contract tests for new behavior, but never change a test merely to hide a real runtime regression. First inspect the actual Blade/component structure and intended behavior.
7. Run targeted Admin tests, then the full `tests/Feature/Admin` regression suite before merge.
8. Perform an actual UI pass at representative desktop/mobile widths. Automated tests do not replace rendered UI inspection.
9. Only consider the work complete when both automated tests and UI review pass.

When a contract test fails because an implementation uses a semantically equivalent but structurally different Blade/Alpine expression, verify runtime behavior first. Update the contract only when the intended behavior is already correct; otherwise fix runtime code.

## Anti-patterns

Avoid:

- Database queries in Blade.
- Business logic in Blade.
- Inconsistent form control sizing.
- Borderless or visually invisible ordinary admin form controls.
- Using placeholder text as the only visual evidence that an input exists.
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
- Ordinary inputs/selects/textareas are visibly bounded in their resting state and have consistent focus/error/disabled/read-only states.
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

### Phase 13 UI/UX verification additions

- Header, Sidebar, Content and Footer consume canonical semantic presentation variables rather than local hardcoded presentation values when the Design system owns those values.
- Sidebar active parent/child states follow exact-route and hierarchy rules.
- Theme Save As, Duplicate, Delete and Restore behavior preserves protected defaults and custom-theme safety.
- Restore Default restores typography, spacing, colors, borders and region presentation coherently.
- Configuration cards and repeated editable records do not overflow their boundaries.
- Fullscreen-hidden Sidebar causes Header, Content and Footer to use the available full width.
- Footer remains at the bottom for short content.
- Targeted Admin tests pass before merge.
- Full `tests/Feature/Admin` regression passes before merge.
- A rendered UI pass has been explicitly completed.