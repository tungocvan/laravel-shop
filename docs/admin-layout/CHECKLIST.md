# Admin Layout Merge Checklist

## Architecture

- [ ] Change is scoped to the admin layout or documented dependent components.
- [ ] No unrelated module behavior changed.
- [ ] Layout context/config contracts are documented.
- [ ] Navigation logic is not duplicated in Blade.
- [ ] Authorization remains enforced outside the UI.
- [ ] Cache invalidation is targeted.

## UI

- [ ] Header height is stable.
- [ ] Sidebar expanded and collapsed states render correctly.
- [ ] Active navigation state is visible.
- [ ] Dropdowns, buttons, badges, and toasts use design-system variants.
- [ ] Long labels do not break layout.
- [ ] No nested cards were introduced.

## Responsive

- [ ] Desktop expanded sidebar works.
- [ ] Desktop collapsed sidebar works.
- [ ] Tablet drawer works.
- [ ] Mobile drawer works.
- [ ] Mobile search or command palette is reachable.
- [ ] Tables/forms do not overflow unexpectedly.
- [ ] Touch targets are at least 44px where applicable.

## Accessibility

- [ ] Skip link exists and works.
- [ ] Landmarks are present.
- [ ] Toggle buttons have labels and expanded state.
- [ ] Active nav item uses `aria-current`.
- [ ] Dropdowns can be operated with keyboard.
- [ ] Drawers/modals trap focus and restore focus.
- [ ] Toasts use live regions.
- [ ] Focus indicators are visible.
- [ ] Theme contrast meets WCAG AA.

## Performance

- [ ] No new unbounded queries.
- [ ] Menu and settings data are cached appropriately.
- [ ] Livewire payloads remain small.
- [ ] No duplicate global event listeners.
- [ ] Heavy widgets are lazy/deferred.
- [ ] Vite build succeeds.

## Testing

- [ ] Admin user with full permissions tested.
- [ ] Limited permission user tested.
- [ ] User with no optional menu permissions tested.
- [ ] Mobile and desktop layouts tested.
- [ ] Browser console checked for JavaScript errors.
- [ ] Relevant Laravel/Livewire tests pass.

## Documentation

- [ ] Changed contracts are reflected in `docs/admin-layout/`.
- [ ] New decisions are recorded in `DECISIONS.md`.
- [ ] `CHANGELOG.md` has an entry for the layout change.

