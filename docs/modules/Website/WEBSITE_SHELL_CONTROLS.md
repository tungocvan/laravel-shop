# Website Shell Controls

## Purpose

`/admin/website/settings` includes global shell controls for the storefront. These controls sit above Header, Homepage and Footer configuration and do not delete or mutate the internal layout data of those systems.

## Setting contract

The source is stored under:

```text
website.shell
```

with this contract:

```text
header_enabled
homepage_enabled
footer_enabled
maintenance.enabled
maintenance.title
maintenance.message
```

`WebsiteShellService` is the resolver and safety boundary. Missing/invalid values fall back to safe defaults.

## Visibility behavior

- `header_enabled=false`: the frontend shell does not render `Website::partials.header`.
- `footer_enabled=false`: the frontend shell does not render `Website::partials.footer`.
- `homepage_enabled=false`: only the content of the named `home` route is suppressed; normal product/blog/account pages continue rendering.
- Turning a shell region off never deletes its Header/Footer/Homepage configuration.

## Storefront maintenance mode

This is intentionally not Laravel `artisan down` maintenance mode.

When `maintenance.enabled=true`, the public frontend layout renders `Website::partials.layout.maintenance` instead of page content. Admin routes remain available so administrators can continue configuring and repairing the Website.

Maintenance title/message are plain text. Runtime output uses escaped Blade syntax and the resolver strips HTML tags. Do not convert these fields to raw `{!! !!}` output.

## Admin UX

The `Bố cục Website` workspace provides:

- Header master toggle + link to Header manager
- Homepage master toggle + link to Homepage manager
- Footer master toggle + link to Footer manager
- Website maintenance toggle
- Maintenance title
- Maintenance message

All values participate in the standard Website Save confirmation/validation/feedback flow described in `ADMIN_OPERATION_VALIDATION_STANDARD.md`.

## Dashboard quick access

`/admin/website` exposes `Cài đặt Website` alongside Homepage, Header and Footer so the shell controls are reachable directly from Website administration.
