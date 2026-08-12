# Settings/SettingForm — P2 Refactor Plan

Status: **Implemented 2026-08-12.**

Implemented scope:

- fixed server-side tab → component allowlist centralized in `TAB_COMPONENTS`;
- invalid tab still falls back to `theme`;
- `admin.theme-switcher` and `admin.header.menu-manager` cross-module contracts preserved;
- external jQuery 3.7.1 and Summernote 0.8.18 CDN assets removed from the System settings shell;
- semantic tab/tablist/tabpanel and active ARIA state added;
- stable active-tab Livewire key retained;
- no persistence, service workflow, permission, route, migration or Admin Menu change introduced;
- focused `SystemSettingFormTest` added;
- `ANALYSIS.md` updated to the implemented state.

Verification after pull/merge:

```bash
php artisan test tests/Feature/System/SystemSettingFormTest.php
php artisan test tests/Feature/System
```

Acceptance: focused test passes and full System regression suite has `0 failed`.
