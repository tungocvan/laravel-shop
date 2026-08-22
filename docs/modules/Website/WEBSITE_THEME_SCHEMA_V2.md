# Website Theme Schema v2

## Purpose

Website Theme v2 packages only safe visual configuration for fast preview/export/import across storefront installations.

## Included

- `design`: Global Design Tokens
- `layout`: Website Layout Presentation
- `appearance`: PWA & Browser Appearance
- `features.chat_position`
- `features.back_to_top_position`

## Explicitly excluded

The theme must never contain:

- Site name
- Logo / Favicon
- SEO data
- Website maintenance state/content
- Header/Homepage/Footer enabled state
- Chat/Back-to-Top enabled state
- Analytics code
- Header scripts
- Credentials, URLs with secrets, or arbitrary executable code

## Schema

```json
{
  "schema": "flexbiz.website-design-theme",
  "version": 2,
  "name": "Premium Violet",
  "design": {},
  "layout": {},
  "appearance": {},
  "features": {
    "chat_position": "right-middle",
    "back_to_top_position": "bottom-right"
  },
  "updated_at": "ISO-8601"
}
```

## Backward compatibility

Version 1 remains supported. A v1 theme contains only `design`.

- Importing v1 preserves it as v1.
- Applying v1 changes only Design Tokens; current Layout, Appearance and widget positions stay untouched.
- Updating a selected v1 theme explicitly upgrades that theme to v2 using the current form values.

This prevents a legacy import from silently resetting newer Website settings.

## Preview-first apply

Apply never publishes directly. It only loads theme values into the Livewire form. The administrator must use `Lưu thay đổi` to persist them to storefront settings.

## Validation

Import rejects:

- empty JSON
- malformed JSON
- wrong schema
- unsupported version
- missing name/design
- missing required v2 payload groups
- unknown top-level fields
- invalid values after resolver sanitization

Export validates the stored theme before emitting JSON.
