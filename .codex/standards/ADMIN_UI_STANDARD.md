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

Canonical states:

- Normal: `border-gray-300 bg-white text-gray-900`.
- Hover when useful: `hover:border-gray-400`.
- Focus: `border-indigo-500 ring-2 ring-indigo-100` with no heavy/dark outline.
- Validation error: `border-red-400 focus:border-red-500 focus:ring-red-100`; show the error text immediately below the field.
- Disabled: `cursor-not-allowed border-gray-200 bg-gray-100 text-gray-500 opacity-75`.
- Read-only: visually distinguish it from editable controls, normally `bg-gray-50 text-gray-600`, while retaining a visible border.

Additional rules:

- Do not ship borderless ordinary admin inputs unless a deliberately designed specialized control clearly requires it.
- `border-transparent`, `border-0`, or an input whose border visually disappears into its card/background is not the default admin form style.
- Selects and textareas should use the same border radius, border color, focus treatment and text sizing as text inputs unless their interaction requires otherwise.
- Search/filter inputs may be slightly more compact, but must still have a visible border and focus state.
- Prefix/suffix/icon controls must preserve the same visible outer boundary.
- Placeholder text is supplementary guidance, not a replacement for a visible label when a label is required.
- When a canonical shared input/form component exists, update/reuse that component instead of copying long utility-class strings across every module.

## Searchable Select / Combobox

Reuse the repository's approved searchable-select component (for example `x-select-search`) when it satisfies the requirement. Do not build another combobox implementation without a documented reason.

Use ordinary selects for short/simple option lists.

For large navigation collections, a searchable picker/command-style selector may be preferable to a permanently expanded tree.

## Number and Currency

Human-facing money values should be formatted for readability. Persist clean numeric values without thousands separators. Never persist formatted currency strings as monetary database values.

Clearly distinguish money, quantity and percentage inputs.

## Tables and Lists

Tables/lists should include, when relevant: clear headers, search/filter controls, bounded pagination, responsive overflow, clear actions, consistent status badges, empty state and loading state.

Do not render unbounded production datasets merely to support an `All` option.

For operational/admin datasets, evaluate keyword search, domain-relevant filters, reset-filter, row selection, permission-aware bulk actions and canonical Import/Export when applicable. Do not add meaningless controls only to satisfy a checklist.

## Pagination Standard

Potentially large admin datasets MUST use bounded pagination.

- Never offer an unbounded `All` page-size option for production-capable datasets.
- Recommended page-size choices are `10`, `25`, `50`, `100` unless domain constraints justify another bounded set.
- Invalid/tampered values normalize to a safe default.
- Active pages use the repository accent treatment unless the selected theme explicitly defines otherwise.
- Changing search/filter/page-size resets pagination when necessary.
- Selection scope must be explicit.

## Filters and Search Standard

- Keep useful search/filter controls visible without overwhelming the page.
- Use debounce for free-text live search when appropriate.
- Reset page and row selection when filters change.
- Provide reset behavior when multiple filters can be active.
- Filtering belongs in component/service/query layers, never Blade.
- Search/filter controls retain a visible resting border.

## Row Selection and Bulk Actions

- Selection state belongs to the owning Livewire component.
- Header checkbox means the visible page unless the UI explicitly states otherwise.
- Show selected count when bulk actions are available.
- Destructive bulk actions require clear modal confirmation.
- Buttons use `type="button"` unless intentionally submitting a form.
- Loading/disabled state prevents duplicate execution.
- Authorization is enforced server-side.

## Import / Export UI

When applicable, reuse the canonical shared Import/Export infrastructure and follow `.codex/tasks/create-import-export.md`.

```text
selected_ids empty     -> export all records in the approved export scope
selected_ids not empty -> export only selected records
```

Import/Export remains visually subordinate to the primary workflow and must expose loading, success and inspectable error states.

## Reading / Viewer Experiences

Documentation, knowledge-base and long-form viewer screens should optimize for comfortable reading rather than raw content density.

- Keep article line length readable.
- Use TOC/navigation without excessively reducing article width.
- Provide reading/focus mode where useful.
- Fullscreen mode should use the real viewport and remove unrelated admin chrome when appropriate.
- Desktop navigation may be persistent; mobile navigation should generally become a drawer/picker.
- Images must be responsive; code blocks must not force page-level horizontal overflow.

## Loading and Mutation UX

Actions such as save, delete, bulk actions, import and export should expose loading/disabled state when they can take noticeable time or can be double-submitted.

Dangerous actions should be visually distinct and confirmed where appropriate. Prefer centered modal confirmation with clear scope, Cancel and explicit destructive action.

Successful operations that materially change the dataset should provide explicit feedback. When refresh is needed to synchronize parent/child Livewire state, use the canonical success modal with an explicit reload action rather than relying only on transient flash text.

## Livewire

- Use class-based components.
- Use `wire:model.live` only for genuinely live interactions.
- Avoid unnecessary network requests.
- Livewire handles UI state/validation and calls services.
- Do not query the database from Blade.
- Do not place core business logic in Blade or Livewire.
- UI-only state such as tabs, drawers, local toolbar selection and fullscreen may use lightweight Alpine/browser APIs when server synchronization is unnecessary.

## Reuse

Before creating UI primitives, inspect existing shared components. Reuse canonical inputs, searchable selects, alerts, modals, pagination, status badges, upload controls and similar patterns.

Do not create duplicate UI systems inside individual modules.

## Accessibility and Responsive Behavior

- Maintain keyboard/focus usability for interactive controls.
- Use semantic labels and button types.
- Keep focus/error states visible.
- Ensure tables/forms remain usable on small screens.
- Do not depend on color alone to communicate critical state.
- Icon-only actions expose meaningful `title`/`aria-label` text.
- Drawers/modals have a clear close action.
- Fullscreen/focus modes have an obvious exit path.

## Visual Balance

A screen can be technically responsive and still feel unprofessional if proportions are poor.

Before considering UI complete, inspect actual rendered screenshots at representative desktop and mobile widths and verify:

- Primary content starts close enough to the active sidebar/shell without excessive dead space.
- Left/right columns are proportional to their information density.
- Content width is intentional, not an accidental result of nested `max-width` containers.
- Empty space supports readability instead of making controls appear disconnected.
- Footer/navigation controls do not consume disproportionate space.
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
- Unbounded `All` pagination.
- Destructive confirmation rendered far below the action that triggered it.
- Hardcoded shell widths/colors/fonts when the Design system owns those values.
- Loose prefix route matching that activates multiple nested menu items.
- Separate font lists or color systems for individual shell regions.
- Dense Header Action/UserMenu edit forms permanently expanded in the settings page.
- A Footer that floats upward on short pages instead of respecting shell layout.
- Treating a passing automated test suite as proof that UI/UX is visually correct.

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
- Bulk selection/actions have clear scope, selected count and modal confirmation when destructive.
- Currency/number fields store clean values.
- Workspace/editor/viewer proportions have been visually inspected in real screenshots.
- UI remains usable on desktop and mobile.
- Fullscreen/drawer/tab interactions have clear exit/navigation behavior.
- Header/Sidebar/Content/Footer consume canonical semantic presentation variables rather than local hardcoded presentation values.
- Sidebar active parent/child states follow exact-route and hierarchy rules.
- Theme Save As/Duplicate/Delete/Restore behavior preserves protected defaults and custom-theme safety.
- Restore Default restores typography, spacing, colors, borders and region presentation coherently.
- Configuration cards and repeated editable records do not overflow their boundaries.
- Fullscreen-hidden Sidebar causes Header/Content/Footer to use the available full width.
- Footer remains at the bottom for short content.
- Targeted tests pass.
- Full `tests/Feature/Admin` regression passes before merge.
- A rendered UI pass has been explicitly completed.
- No business logic or database access leaked into Blade.
