# Settings/MomoConfig Refactor Plan

Status: Approved for immediate implementation by user on 2026-08-12.

## Scope
Refactor `Settings/MomoConfig` as the MoMo ENV configuration tab without creating a separate Admin Menu item.

## Plan
- enforce `system.env.update` on endpoint test and save;
- do not hydrate existing `MOMO_ACCESS_KEY` or `MOMO_SECRET_KEY` into public Livewire state; blank replacements preserve server-side values;
- move HTTP integration behavior and env orchestration out of Livewire into a focused `MomoConfigService`;
- allow only HTTPS MoMo-owned endpoint hosts (`momo.vn` and subdomains), rejecting arbitrary hosts/IPs before HTTP access;
- use bounded connect/overall HTTP timeouts and generic safe failure messages;
- whitelist the exact MOMO_* keys written through `EnvManagerService`;
- validate endpoint, partner code and replacement credential length;
- render the canonical System-owned Blade instead of Admin-owned view;
- add read-only/loading/validation/confirmation UX;
- retain `/admin/system/settings/env` + `system.env.view` as the existing menu/page boundary;
- add `SystemMomoConfigTest` covering authorization, host policy, secret preservation, safe HTTP failures and view ownership.

No migration, new route, new permission or new menu is planned.
