# MenuForm Refactor Plan

## Status

**PLANNING COMPLETE — AWAITING APPROVAL**

## Goal

Refactor `MenuForm` into a thinner Livewire component, reuse the module service layer and adopt the current visible-border Admin form standard without changing routes, schema or menu behavior.

## Approved Scope After User Approval

### P1 — Move menu persistence/query rules to MenuService

Move or delegate from Livewire to `MenuService`:

- load menu for edit
- parent option retrieval
- parent validity check
- slug generation / collision handling
- next root/menu sort order where applicable
- create/update persistence
- permission options where appropriate
- cache invalidation after save

Livewire should keep UI state, validation, authorization and redirect/feedback only.

### P1 — Fix malformed permission select markup

Fix the missing `>` in the permission `<select>` opening tag.

### P1 — Adopt shared form controls

Use the new Admin primitives where practical:

```text
x-admin::form.input
x-admin::form.select
```

for name, parent, URL, icon and permission fields. Prefix/icon preview wrappers may remain specialized but ordinary editable controls must have a visible resting border.

### P2 — Safer error feedback

Continue reporting unexpected exceptions but do not render raw exception messages to the administrator. Show a stable user-facing save error.

### P2 — Types / cleanup

Add safe Livewire-compatible property/method types and remove stale commented logging where doing so is low-risk. Preserve section toggle behavior and current redirect route.

## Contracts To Preserve

- `admin.menu.create` / `admin.menu.update` authorization at save boundary.
- Section menus clear URL.
- Parent menu hierarchy remains available.
- Existing menu IDs remain stable on edit.
- Slugs remain unique.
- New menu sort order behavior remains equivalent.
- Cache is invalidated after successful persistence.
- Redirect returns to `admin.menus.index` with success feedback.

## Files Expected To Change

```text
Modules/Admin/Livewire/Menus/MenuForm.php
Modules/Admin/Services/MenuService.php
Modules/Admin/resources/views/livewire/menus/menu-form.blade.php
tests/Feature/Admin/** menu-focused tests
docs/modules/Admin/livewire/menus/menu-form/ANALYSIS.md
docs/modules/Admin/livewire/menus/menu-form/REFACTOR_PLAN.md
```

## Verification

```bash
vendor/bin/pint --test Modules/Admin tests/Feature/Admin
php artisan test tests/Feature/Admin
npm run build
php artisan test
```

Manual smoke:

- create normal menu
- create section menu
- edit existing menu
- change parent
- change icon preview
- choose/remove permission
- toggle active
- validation errors and visible input borders

## Acceptance Criteria

- [ ] MenuForm delegates persistence/domain queries to MenuService.
- [ ] Permission select markup is valid.
- [ ] Ordinary controls use visible-border shared Admin form components.
- [ ] Raw exception detail is not shown to user.
- [ ] Existing menu behavior/routes/permissions remain unchanged.
- [ ] Targeted and full regression pass.

## Approval Gate

**AWAITING USER APPROVAL**
