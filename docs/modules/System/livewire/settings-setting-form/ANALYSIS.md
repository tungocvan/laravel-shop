# Settings/SettingForm Livewire Analysis

## Executive Summary

`SettingForm` is the canonical tab-shell/orchestrator for `/admin/system/settings`. P2 cleanup is implemented: component selection remains server-side allowlisted, duplicated external jQuery/Summernote CDN ownership has been removed, tab semantics were improved, and the Admin-owned theme/menu aliases are documented as intentional cross-module contracts.

Overall state: **P2 implemented; keep component.**

## Component Purpose

Expose six settings tabs and mount one fixed child Livewire component:

- theme → `admin.theme-switcher`
- general → `system.settings.partials.general`
- menu → `admin.header.menu-manager`
- images → `system.settings.partials.images`
- seo → `system.settings.partials.seo`
- custom → `system.settings.partials.custom`

## Security Contract

The browser supplies only a tab key. `setTab()` checks it against the fixed `TAB_COMPONENTS` allowlist. Invalid values fall back to `theme`. `getTabComponent()` resolves only from that server-side map.

Invariant:

```text
browser tab key → server allowlist → fixed Livewire alias
```

Arbitrary public component aliases are not accepted.

## Authorization

The shell performs no persistence or sensitive mutation. The settings page remains protected by `system.settings.view`; child components own their mutation-level authorization.

## Cross-module Contract

These dependencies are intentional and retained:

```text
System SettingForm → Admin admin.theme-switcher
System SettingForm → Admin admin.header.menu-manager
```

They are composition contracts, not a reason to move Admin implementation into System during this P2 cleanup.

## Blade / UI

Implemented:

- responsive horizontal tabs retained;
- stable active-tab `wire:key` retained;
- `tablist`, `tab`, `tabpanel`, `aria-selected`, `aria-controls`, and `aria-labelledby` semantics added;
- external jQuery and Summernote CDN assets removed from this shell.

The shell no longer owns editor globals unrelated to its orchestration responsibility.

## Performance

No meaningful query or persistence work occurs in this component. Child components remount when the active tab changes because the active-tab key changes; this remains acceptable for the current scope.

## Tests

Focused coverage added in:

```text
tests/Feature/System/SystemSettingFormTest.php
```

It covers fixed alias resolution, invalid-tab fallback, absence of external editor dependencies, tab semantics, stable key, cross-module aliases, and absence of direct persistence calls.

Full System regression suite remains the acceptance gate after pull/merge.

## Residual Debt

No component-specific P2 blocker remains.

Potential future architecture work, only if module ownership is redesigned globally:

- formal interface/registry for cross-module settings tabs;
- moving theme/menu ownership between modules.

Neither is required for the current System refactor.
