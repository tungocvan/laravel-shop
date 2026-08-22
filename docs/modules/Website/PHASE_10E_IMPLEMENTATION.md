# Phase 10E — Footer Drag/drop & Responsive Preview

## Status

Implemented on `refactor/website-footer-phase-10e`. Awaiting targeted Website tests and local Admin/storefront UI validation before merge.

## Scope

Phase 10E extends the Phase 10D Footer Builder without changing the persistence contract introduced earlier.

### Device filter

The Builder now exposes two explicit editing filters:

- Desktop
- Mobile

Desktop filter shows `desktop.*` slots. Mobile filter shows `mobile.*` slots. The shared `overlay` slot remains visible in both modes.

### Drag/drop

Footer components use native HTML5 drag/drop with Alpine state. No new sorting dependency is introduced.

Every drop calls the Livewire `moveComponentByDrag()` mutation. The mutation validates the target through `FooterComponentRegistry` before changing builder state.

The existing up/down buttons and target-slot select remain available as fallback controls.

### Responsive Preview

The Footer Builder includes a preview with Desktop and Mobile modes. It renders from the current Livewire `builderSlots` and `presentation` state, including unsaved changes.

The preview reflects:

- enabled/disabled components
- component order
- Desktop versus Mobile composition
- background/text/accent/border presentation
- top accent state

The preview is a safe admin representation of Footer composition; it does not execute arbitrary persisted Blade paths.

## Persistence

Unchanged:

- `footer.layout`
- `footer.presentation`

## Authorization

All persistent or structural builder mutations continue to require `website.footer.manage`.

## Testing

Focused tests:

```bash
php artisan test \
  tests/Feature/Website/WebsiteFooterBuilderInteractionTest.php \
  tests/Feature/Website/WebsiteFooterBuilderConfigurationTest.php \
  tests/Feature/Website/WebsiteFooterSchemaConfigurationTest.php \
  tests/Feature/Website/WebsiteFooterPresentationConfigurationTest.php
```

Full Website gate:

```bash
php artisan test tests/Feature/Website
```

Do not run the whole project test suite for this phase.

## Manual UI gate

Open `/admin/footer-settings` → `Bố cục Footer` and verify:

- Desktop/Mobile Builder filters
- overlay visible in both filters
- drag reorder in a valid slot
- drag to another allowed slot
- invalid target rejection
- enable/disable reflected immediately in preview
- Desktop/Mobile preview switching
- presentation changes reflected before save
- save, reload, and storefront persistence
