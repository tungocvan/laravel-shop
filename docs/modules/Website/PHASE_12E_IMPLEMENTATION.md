# Phase 12E — PWA Runtime Update & Version Sync

## Goal

Keep an already-installed storefront PWA aware of safe Website appearance changes without requiring users to manually clear browser cache.

## Runtime contract

- `GET /website-pwa-version.json` returns a deterministic version hash derived from safe `website.appearance` fields.
- Responses are `no-cache, no-store` so foreground checks see the current configuration.
- Storefront checks the version after Service Worker registration, when the document becomes visible again, and when the window regains focus.
- When the version changes, the Service Worker refreshes the dynamic Website manifest and version resource.
- The storefront displays `Website vừa được cập nhật` with a `Cập nhật ngay` action.
- Service Worker updates use `registration.update()`, `SKIP_WAITING`, and `controllerchange` to activate runtime code safely.
- The storefront cache namespace is `website-storefront-shell-v3` and does not pre-cache the Client Portal static manifest.

## Platform limitation

This mechanism synchronizes Website runtime/cache and makes current manifest metadata available to the browser. Android/iOS/browser installation surfaces still control when an installed app's launcher name/icon/theme metadata is refreshed. The application must not claim it can force those OS-managed fields to update immediately.

## Isolation

`/manifest.webmanifest` remains the Client Portal manifest. Storefront uses `/website-manifest.webmanifest`; Phase 12E does not overwrite or pre-cache the Client Portal PWA manifest. Storefront version checks use `/website-pwa-version.json` and its own Website cache namespace.
