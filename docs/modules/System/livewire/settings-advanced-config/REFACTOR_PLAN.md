# Settings/AdvancedConfig Refactor Plan

Status: Approved for immediate implementation by user on 2026-08-12.

## Scope
Refactor `Settings/AdvancedConfig` while preserving its existing ENV page tab and Admin Menu parent.

## Plan
- enforce `system.env.update` on queue test, queue polling, Node health check and save;
- never hydrate the current `BRIDGE_SECRET_KEY` into public Livewire state; blank replacement preserves the existing secret;
- validate queue driver against `sync`, `database`, `redis`;
- validate Node URL as HTTP(S), with server-side host safety policy to reject unsafe arbitrary remote probing while allowing loopback/private deployment endpoints intentionally used for the local Node bridge;
- move persistence/secret-resolution orchestration into a focused service using canonical `EnvManagerService`;
- harden `SystemConfigService::pingNodeJS()` with connect/overall timeouts and generic browser messages; never return response bodies/raw exceptions;
- bound queue polling lifecycle instead of allowing indefinite polling;
- add read-only/loading/validation UX;
- keep `/admin/system/settings/env` and `system.env.view`; no duplicate Admin Menu item;
- add `SystemAdvancedConfigTest` covering authorization, secret preservation, validation, safe Node errors and bounded queue polling.

No migration, new route, new permission or new menu is planned.
