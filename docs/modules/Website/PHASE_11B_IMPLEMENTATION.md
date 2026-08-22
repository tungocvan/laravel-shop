# Phase 11B — Homepage Builder State & Safe Drag/Drop

## Status

Implemented on `refactor/website-homepage-phase-11`.

Phase 11B changes the Homepage Builder lifecycle so editing actions no longer mutate the database before the administrator explicitly saves.

## Core contract

Builder mutations are preview-first:

```text
Admin interaction
  -> Livewire builder state only
  -> review current builder state
  -> click "Lưu thay đổi"
  -> one controlled write transaction
  -> structured WebsiteSection state + compatibility home_* settings
```

The following actions MUST NOT persist immediately:

- reorder sections;
- duplicate section;
- remove/hide section;
- restore section.

## Livewire state

`HomeSettings` owns the working state:

- `sectionOrder` — ordered layout keys;
- `layout` — visibility per layout key;
- `sectionTypes` — trusted section type per section key.

A duplicated section receives a deterministic temporary key such as:

```text
featured_copy_1
featured_copy_2
```

The duplicate exists only in Livewire state until Save.

## Persistence boundary

`HomepageContentWriteService::save()` is the only Phase 11B publish boundary.

It performs the compatibility write and structured builder persistence inside one outer `DB::transaction`.

`HomepageBuilderPersistenceService` then:

1. validates each builder key through `HomepageSectionRegistry`;
2. creates missing duplicate sections by copying the canonical structured section;
3. copies structured section items for new duplicates;
4. normalizes section position;
5. persists `all / desktop / mobile / none` visibility;
6. disables hidden canonical sections;
7. deletes persisted `*_copy_N` sections no longer present in Builder state;
8. clears Homepage composition cache.

No renderer path comes from the browser or database.

## Drag/drop safety

The existing drag UI submits an ordered key list to `reorderSections()`.

The server accepts only keys already present in current Builder state, removes duplicates, and appends any omitted valid keys. Unknown keys are ignored.

Every persistent mutation path requires:

```php
authorizeAdminPermission('website.home.manage')
```

## Compatibility layer

Phase 11B intentionally retains `home_*` compatibility settings. Structured content consolidation remains Phase 11F.

The compatibility write and structured publish happen in the same save transaction so the two representations do not intentionally diverge during the rollback window.

## Out of scope

Phase 11B does not yet add:

- per-section settings panel;
- Homepage presentation tokens;
- responsive preview canvas;
- Homepage layout themes;
- removal of legacy `home_*` keys.

These belong to 11C–11F.

## Targeted tests

Run only the Homepage/Website tests related to the changed contract:

```bash
php artisan test \
  tests/Feature/Website/WebsiteHomepageBuilderStateConfigurationTest.php \
  tests/Feature/Website/WebsiteHomepageSectionRegistryConfigurationTest.php
```

Do not run the whole project test suite for this phase.

## UI acceptance

At `/admin/homepage-settings` verify:

1. drag a section to another position, refresh without saving, and confirm persisted frontend order did not change;
2. duplicate a section, refresh without saving, and confirm no duplicate was persisted;
3. duplicate again and click Save, then refresh and confirm the duplicate exists;
4. remove a duplicated section without Save and confirm refresh restores it;
5. remove the duplicate and Save, then confirm it is deleted;
6. hide/restore a canonical section and confirm only Save publishes the state.
