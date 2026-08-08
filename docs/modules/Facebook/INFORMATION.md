# Facebook Module Information

## Purpose

The Facebook domain module connects an administrator-owned Meta identity, discovers Facebook Pages, stores encrypted user/Page access tokens, and creates, schedules, queues, and publishes Page posts through Meta Graph API.

Evidence: `Modules/Facebook/config/module.php`, routes, services, models, and migrations.

## Features

- OAuth connection and scope verification.
- Page synchronization, token verification, activation, and default selection.
- Text, single-photo, and link post drafts.
- Immediate and scheduled queued publishing.
- Retry/cancel/duplicate/delete controls for post records.
- Dashboard counts, CLI diagnostics, and a webhook verification/receipt endpoint.

## Routes

All admin routes use `web`, `auth:admin`, and a named Spatie permission. Prefix: `/admin/facebook`; name prefix: `admin.facebook.`.

| Method | URI | Name | Permission |
|---|---|---|---|
| GET | `/admin/facebook` | `admin.facebook.index` | `facebook.view` |
| GET | `/admin/facebook/connect` | `admin.facebook.connect` | `facebook.connect` |
| GET | `/admin/facebook/callback` | `admin.facebook.callback` | `facebook.connect` |
| POST | `/admin/facebook/disconnect` | `admin.facebook.disconnect` | `facebook.connect` |
| POST | `/admin/facebook/sync-pages` | `admin.facebook.sync-pages` | `facebook.pages.manage` |
| GET | `/admin/facebook/pages` | `admin.facebook.pages.index` | `facebook.pages.manage` |
| GET | `/admin/facebook/posts` | `admin.facebook.posts.index` | `facebook.posts.view` |
| GET | `/admin/facebook/posts/create` | `admin.facebook.posts.create` | `facebook.posts.create` |
| GET | `/admin/facebook/posts/{id}` | `admin.facebook.posts.show` | `facebook.posts.view` |
| GET | `/admin/facebook/posts/{id}/edit` | `admin.facebook.posts.edit` | `facebook.posts.update` |
| GET | `/facebook/webhook` | `facebook.webhook.verify` | Public, throttled |
| POST | `/facebook/webhook` | `facebook.webhook.handle` | Public, throttled; currently affected by CSRF and lacks signature verification |

## Permissions

Manifest capabilities: `facebook.view`, `facebook.connect`, `facebook.pages.manage`, `facebook.posts.view`, `facebook.posts.create`, `facebook.posts.update`, `facebook.posts.delete`, `facebook.posts.publish`, and `facebook.posts.retry`. `Super Admin` is globally allowed by `Modules/ModuleServiceProvider.php`.

## Dependencies

- Laravel HTTP client, queue, scheduler, cache-backed unique jobs, database, encrypted Eloquent casts, Storage, and Livewire 3.
- Spatie Laravel Permission with the `admin` guard.
- `Admin::layouts.master` from the Admin shell module.
- `App\Http\Controllers\Controller` and `app/Console/Commands/FacebookTestCommand.php`.
- External Meta OAuth and Graph API endpoints.

## Services

- `FacebookGraphClient`: versioned HTTP requests, retry loop, error mapping, redacted logging.
- `FacebookOAuthService`: state generation/validation, token exchanges, identity and scope lookup.
- `FacebookConnectionService`: connection persistence, disconnect, latest-page synchronization.
- `FacebookPageService`: list/query, sync, verify, default, activation, token masking.
- `FacebookPostService`: drafts, scheduling, queueing, duplication, cancellation, deletion.
- `FacebookMediaService`: private-disk photo storage and media-row creation.
- `FacebookPublishingService`: Graph publishing and outcome persistence.
- `FacebookDashboardService`, `FacebookErrorMapper`, `FacebookRedactor`, `FacebookTokenMasker`.

## Imports

None. The shared import/export foundation is not referenced.

## Exports

None.

## Models

- `FacebookConnection`: soft deletes, encrypted/hidden `user_access_token`, has many pages.
- `FacebookPage`: soft deletes, encrypted/hidden `page_access_token`, belongs to connection, has many posts.
- `FacebookPost`: soft deletes, status/type constants, belongs to page, has many media.
- `FacebookPostMedia`: belongs to post; no soft deletes.

## Database Tables

- `facebook_connections`: OAuth identity, encrypted token, scopes, status, verification/error metadata.
- `facebook_pages`: external Page identity, encrypted token, tasks, active/default flags, verification/error metadata. Unique `(facebook_connection_id, page_id)`.
- `facebook_posts`: content, lifecycle timestamps/status, external result, idempotency key, sanitized response/error metadata.
- `facebook_post_media`: stored photo metadata and external media result.

## Events

No Laravel domain events are declared or dispatched.

## Jobs

`PublishFacebookPostJob` is unique by post ID for a configurable period, runs on the configured Facebook queue, has three attempts, a 120-second timeout, and backoff of 60/300/900 seconds.

## Configuration

Module config key is `facebook`, merged by `FacebookServiceProvider`. The generic module loader also exposes the manifest under `facebook.module`. The schedule invokes `facebook:dispatch-scheduled` every minute with overlap prevention. A dedicated `facebook` log channel writes `storage/logs/facebook.log`.

## Environment Variables

`FACEBOOK_GRAPH_BASE_URL`, `FACEBOOK_OAUTH_BASE_URL`, `FACEBOOK_GRAPH_VERSION`, `FACEBOOK_APP_ID`, `FACEBOOK_APP_SECRET`, `FACEBOOK_REDIRECT_URI`, `FACEBOOK_WEBHOOK_VERIFY_TOKEN`, `FACEBOOK_HTTP_TIMEOUT`, `FACEBOOK_CONNECT_TIMEOUT`, `FACEBOOK_MAX_RETRIES`, `FACEBOOK_RETRY_DELAY`, `FACEBOOK_TOKEN_ENCRYPTION`, `FACEBOOK_QUEUE`, `FACEBOOK_MEDIA_DISK`, `FACEBOOK_DUPLICATE_LOCK_SECONDS`, and `FACEBOOK_SCOPES`.

## Known Risks

- Post-form mutations do not re-authorize the required action; `publishNow` does not require `facebook.posts.publish`.
- Webhook payload authenticity is not checked, and POST delivery is not CSRF-exempt.
- Queue retries cannot reclaim a post already marked failed/processing.
- Dispatch occurs inside a transaction without `afterCommit`.
- Photo replacement and file cleanup are incomplete.
- `All` list mode creates unbounded queries.
- Functional, authorization, HTTP, queue, storage, and migration tests are absent.
