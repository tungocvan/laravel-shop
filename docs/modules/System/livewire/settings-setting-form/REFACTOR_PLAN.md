# Settings/SettingForm — P2 Refactor Plan

## 1. Scope

Target:

```text
Modules/System/Livewire/Settings/SettingForm.php
Modules/System/resources/views/livewire/settings/setting-form.blade.php
```

This is a **light P2 refactor / cleanup**. `SettingForm` remains the System settings tab shell; child components retain ownership of their own mutations and authorization.

## 2. Current Contract

Tabs and fixed component aliases:

| Tab | Component alias | Owner |
|---|---|---|
| `theme` | `admin.theme-switcher` | Admin |
| `general` | `system.settings.partials.general` | System |
| `menu` | `admin.header.menu-manager` | Admin |
| `images` | `system.settings.partials.images` | System |
| `seo` | `system.settings.partials.seo` | System |
| `custom` | `system.settings.partials.custom` | System |

The cross-module aliases `admin.theme-switcher` and `admin.header.menu-manager` are intentional composition contracts and must be preserved in this batch.

## 3. Goals

1. Preserve the server-side allowlist for tab/component resolution.
2. Remove jQuery 3.7.1 and Summernote 0.8.18 CDN assets from the shell because the shell itself does not use them and repository search found no supported reason for this component to own those globals.
3. Improve tab semantics/accessibility without redesigning the page.
4. Add focused tests for tab allowlisting and fixed alias resolution.
5. Document the two Admin-owned component dependencies as explicit contracts.
6. Keep persistence/business logic out of this shell.

## 4. PHP Changes

Planned changes to `SettingForm.php`:

- keep `tabs` as the authoritative public tab allowlist;
- keep invalid tab fallback to `theme`;
- keep fixed server-side alias resolution;
- optionally centralize the tab→component mapping in one private/constant mapping if this reduces duplication without exposing arbitrary aliases;
- add strict return types where appropriate without changing Livewire behavior;
- do not add persistence, service calls, or mutation authorization to this shell.

Security invariant:

```text
browser tab key
→ allowlisted server-side key
→ fixed component alias
```

Never change this to accept a component alias directly from public/browser state.

## 5. Blade Changes

Planned changes to `setting-form.blade.php`:

- remove the entire `@once/@push('scripts')` block that loads external jQuery/Summernote assets;
- retain responsive horizontal tab navigation;
- add semantic tab state (`role="tablist"`, `role="tab"`, `aria-selected`, and suitable panel semantics) where compatible with current markup;
- retain stable `wire:key="tab-{{ $activeTab }}"`;
- do not redesign the System settings page.

## 6. Authorization

No permission changes.

- page route remains protected by `system.settings.view`;
- this shell performs no sensitive write;
- mutation authorization remains the responsibility of each child component (`system.settings.update` or the child module's canonical permission as already implemented).

Do not duplicate child authorization in `SettingForm`.

## 7. Cross-module Contract

The following dependencies are explicitly supported by this shell:

```text
System SettingForm → Admin admin.theme-switcher
System SettingForm → Admin admin.header.menu-manager
```

This batch documents rather than relocates them. Moving those components into System would be a module-architecture change and is out of scope.

## 8. Tests

Add:

```text
tests/Feature/System/SystemSettingFormTest.php
```

Focused coverage:

1. settings route retains `system.settings.view` protection;
2. default tab is `theme`;
3. every allowed tab resolves to the expected fixed alias;
4. invalid tab falls back to `theme` and cannot select an arbitrary component;
5. Blade retains dynamic Livewire mounting with stable active-tab key;
6. Blade no longer loads jQuery/Summernote CDN assets;
7. tab accessibility state is present;
8. shell contains no persistence/business workflow.

Then run:

```bash
php artisan test tests/Feature/System/SystemSettingFormTest.php
php artisan test tests/Feature/System
```

Acceptance target: full System regression suite has `0 failed`.

## 9. Files Expected to Change

```text
Modules/System/Livewire/Settings/SettingForm.php
Modules/System/resources/views/livewire/settings/setting-form.blade.php
tests/Feature/System/SystemSettingFormTest.php
docs/modules/System/livewire/settings-setting-form/ANALYSIS.md
docs/modules/System/livewire/settings-setting-form/REFACTOR_PLAN.md
```

No Admin Menu change is required: `SettingForm` is already the content shell for the existing System Settings page.

## 10. Explicitly Out of Scope

- changing route names/URLs;
- changing permission names;
- adding Admin Menu entries;
- moving Admin theme/menu components into System;
- changing child settings persistence;
- changing `SettingsService`;
- adding a new editor package;
- redesigning the settings UI;
- refactoring child components again.

## 11. Rollback

No schema, route, permission, storage, or data migration is involved. The change can be reverted at component/view/test level without data rollback.

## 12. Acceptance Checklist

- [ ] fixed server-side tab/component allowlist preserved;
- [ ] invalid tab safely falls back to `theme`;
- [ ] `admin.theme-switcher` contract preserved;
- [ ] `admin.header.menu-manager` contract preserved;
- [ ] jQuery CDN removed from this shell;
- [ ] Summernote CDN CSS/JS removed from this shell;
- [ ] tabs expose semantic active state;
- [ ] no persistence/business logic introduced;
- [ ] focused SettingForm test passes;
- [ ] full `tests/Feature/System` suite has 0 failures;
- [ ] `ANALYSIS.md` updated after implementation.

## 13. Implementation Gate

**STOP after this plan.**

Do not modify application code until the user explicitly approves implementation, per `.codex/tasks/refactor-livewire.md`.
