# Facebook Module

## Module Overview

`Facebook` is a domain module for connecting Meta accounts, managing Facebook Pages, and publishing text, photo, or link posts. It owns its four tables and Graph API integration. Current health requires a major refactor before production reliance; see `ANALYSIS.md`.

## Installation / Registration

The root `Modules\ModuleServiceProvider` discovers `Modules/Facebook`, reads `config/module.php`, registers `FacebookServiceProvider`, routes, views, migrations, Livewire aliases, and console commands. Run migrations and ensure a queue worker and Laravel scheduler are active. The module is enabled as a `domain` module.

## Routes

Admin UI: `/admin/facebook`, `/admin/facebook/pages`, `/admin/facebook/posts`, and post create/show/edit routes. OAuth connect/callback, disconnect, and page sync also live beneath `/admin/facebook`. Meta webhook verification and delivery use `/facebook/webhook`.

## Permissions

Grant only the capabilities required: `facebook.view`, `facebook.connect`, `facebook.pages.manage`, and the six granular post capabilities (`view`, `create`, `update`, `delete`, `publish`, `retry`). Until the form action checks are corrected, do not treat `create`, `update`, and `publish` as safely separable production permissions.

## Features

OAuth with state validation; required-scope checks; encrypted token persistence; Page sync/verification; post drafts; scheduling; queued publishing; failure mapping; token-safe logs; CLI operations; dashboard statistics.

## Dependencies

Laravel 12/PHP 8.3, Livewire 3, Spatie permissions, Laravel queue/scheduler/storage/HTTP client, the Admin layout, and Meta Graph API. No shared import/export dependency exists.

## Import

Not supported.

## Export

Not supported.

## Configuration

Set the `FACEBOOK_*` variables documented in `Modules/Facebook/README.md`, especially App ID/secret, redirect URI, webhook verify token, Graph version, queue, media disk, and scopes. Keep the media disk private. `FACEBOOK_TOKEN_ENCRYPTION` is currently unused; encrypted casts are unconditional.

## Events

No domain events exist. The webhook currently records only object name and entry count.

## Jobs

Run a worker for `FACEBOOK_QUEUE` (default `facebook`). `routes/console.php` dispatches due posts every minute via `facebook:dispatch-scheduled`. Automatic retry state handling is currently defective; monitor failed and processing posts.

## Operations Notes

- Configure the `facebook` log channel and scheduler cron.
- Verify Meta App redirect URI and scopes exactly.
- Avoid enabling the webhook for operational processing until signature validation and CSRF routing are fixed.
- Retain and back up `APP_KEY`; changing it makes encrypted tokens unreadable.
- Establish a retention/cleanup job for stored media before production growth.
- The local analysis environment has no PHP executable, so runtime tests were not executed.

## Future Improvements

Apply `REFACTOR_PLAN.md`: close action-level authorization gaps, authenticate webhooks, repair queue retry/after-commit behavior, define a durable state machine, fix media replacement/cleanup, bound list queries, add database constraints and comprehensive tests, then improve events, observability, and operational recovery.
