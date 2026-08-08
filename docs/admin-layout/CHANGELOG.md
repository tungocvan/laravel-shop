# Admin Layout Changelog

All notable changes to the Admin Layout documentation and architecture should be recorded here.

Use dates in `YYYY-MM-DD` format.

## [Unreleased]

### Added

- Initial documentation set for Admin Layout architecture.
- Current architecture analysis for `master.blade.php`, Livewire header/sidebar partials, sidebar menu services, header menu service, theme manager, toast component, and icon component.
- Target architecture, component tree, responsive strategy, configuration spec, design system, accessibility, performance, Livewire, and Blade component guidance.
- Refactor plan, rebuild spec, implementation roadmap, merge checklist, and ADR log.
- Production admin layout rebuild with module configuration, layout partials, skip link, accessible shell regions, flash message region, mobile search overlay, and global stack roots.
- Admin layout configuration UI backed by database settings.

### Changed

- `Modules/Admin/resources/views/layouts/master.blade.php` now delegates head, shell, content, stacks, and scripts to dedicated partials.
- Header, sidebar, toast, and icon views now include stronger responsive and accessibility attributes.
- Sidebar authorization pruning and active-state calculation now run in `SidebarService` before rendering.
- Admin layout config can now be managed from `/admin/layout` and falls back to `Modules/Admin/config/admin.php`.

### Deprecated

- None.

### Removed

- None.

### Fixed

- None.

### Security

- None.

## Release Entry Template

```md
## [YYYY-MM-DD] - Short Title

### Added

- New behavior, component, document, or contract.

### Changed

- Existing behavior or architecture changed.

### Deprecated

- Behavior that remains available but should not be used for new work.

### Removed

- Removed behavior, component, file, or contract.

### Fixed

- Bug fixes or documentation corrections.

### Security

- Security, authorization, data exposure, or sensitive workflow changes.
```
