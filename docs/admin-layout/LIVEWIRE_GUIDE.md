# Livewire Guide

## Role Of Livewire In The Layout

Livewire should power server-aware interaction. It should not be used for static markup that Blade can render without hydration.

Use Livewire for:

- Search query submission and suggestions.
- Notifications.
- User preference changes.
- Theme switching.
- Menu refresh after admin menu edits.
- Modal forms and drawers that submit data.

Use Blade/Alpine for:

- Static composition.
- Local open/closed UI state.
- Simple dropdown visibility.
- Purely presentational components.

## `wire:navigate`

If admin pages adopt `wire:navigate`:

- Global scripts must be idempotent.
- Toast, modal, and drawer managers must not register duplicate listeners.
- Page-specific scripts should clean up after navigation.
- Sidebar active state should update after navigation.
- Focus should move to page title or main content after navigation.

## Events

Document every cross-component event.

| Event | Direction | Purpose |
|---|---|---|
| `notify` | Browser event | Current toast manager input. |
| `refresh` | Livewire event | Current toast action can dispatch this. |
| `layout:theme-changed` | Proposed | Theme switcher tells shell to update tokens. |
| `navigation:refresh` | Proposed | Menu changes refresh sidebar tree. |
| `command-palette:open` | Proposed | Keyboard shortcut opens search overlay. |

## Teleport

Use Livewire teleport for:

- Modals.
- Drawers.
- Dropdowns that must escape overflow containers.

Rules:

- Teleported content still needs accessible labels.
- Do not teleport content that depends on local CSS context unless styles are global.

## Polling

Polling is acceptable only when:

- The data is small.
- The update interval is reasonable.
- The widget is visible.
- The backend query is cached or inexpensive.

Good candidates:

- Notification count.
- Queue progress.

Avoid polling:

- Full menu tree.
- Large tables.
- Permission checks.

## Lazy Loading

Use `lazy` or deferred loading for:

- Notification lists.
- Search suggestions.
- Charts.
- Expensive dashboard widgets.

Do not lazy-load:

- Core sidebar navigation.
- Header structure.
- Critical page title/actions.

## Forms

Livewire form rules:

- Validate every external input.
- Authorize mutating actions inside the method.
- Use typed properties where practical.
- Keep business workflows in services.
- Dispatch user feedback through a documented notification contract.

## Modals

Modal rules:

- Keep modal open/close state local when no server data is needed.
- Use Livewire when the modal loads or saves data.
- Reset validation errors on close.
- Confirm destructive actions.
- Restore focus after close.

## Notifications

The current notification component is static. Target behavior:

- Load unread count.
- Lazy-load dropdown list.
- Mark items as read.
- Link to relevant records.
- Respect permissions.
- Avoid polling too often.

## Sidebar

The sidebar may remain Livewire if it needs to refresh after menu changes or user preference updates. Otherwise, consider rendering it from a prepared layout view model and using Alpine for local open/closed state.

## Error Handling

Livewire layout components must not expose raw exceptions. Use safe messages and log details server-side.

