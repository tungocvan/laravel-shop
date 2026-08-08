# Refactor Plan

## Rule

This plan describes future work only. It does not authorize code changes by itself.

## Strategy

Refactor in small, reversible steps while preserving the current admin behavior. The first goal is separation of concerns, not visual redesign.

## Step 1: Freeze Current Contract

Deliverables:

- Keep this documentation current.
- Record screenshots of desktop, collapsed desktop, tablet, and mobile sidebar states.
- Identify pages that use `@extends('Admin::layouts.master')` or slot rendering.

Risk: Low.

## Step 2: Introduce Layout Context

Deliverables:

- Define a layout context service or view composer.
- Move favicon, title sidebar, theme tokens, and shell config into a prepared object.
- Keep existing Blade output unchanged.

Risk: Medium because settings reads move.

## Step 3: Normalize Navigation View Model

Deliverables:

- Move permission pruning out of sidebar Blade.
- Move active/open state calculation out of sidebar Blade.
- Return a render-ready menu tree.
- Add targeted cache invalidation.

Risk: High because navigation visibility and active state are user-facing.

## Step 4: Extract Sidebar Components

Deliverables:

- Create nav item and nav group Blade components.
- Preserve current classes initially.
- Add accessibility attributes.
- Add collapsed tooltip/accessibility strategy.

Risk: Medium.

## Step 5: Centralize Shell JavaScript

Deliverables:

- Move search shortcut and toast manager to Vite-managed modules.
- Keep browser event contracts documented.
- Make initialization idempotent for future `wire:navigate`.

Risk: Medium.

## Step 6: Define Page Chrome

Deliverables:

- Create page header, breadcrumbs, toolbar, flash messages.
- Allow pages to opt into full-width or narrow containers.
- Avoid changing every page at once.

Risk: Medium.

## Step 7: Improve Responsive Layout

Deliverables:

- Add mobile search overlay.
- Add focus-managed mobile drawer.
- Document and implement table/card responsive patterns.

Risk: Medium.

## Step 8: Theme Token Migration

Deliverables:

- Convert sidebar theme config from raw fragments to semantic tokens.
- Keep backward compatibility during migration.
- Validate all themes for contrast.

Risk: Medium.

## Step 9: Optional Visual Refresh

Only after architecture is stable:

- Adjust density.
- Normalize radius.
- Improve shadows.
- Align header/sidebar/dropdown styles.

Risk: Medium.

## Verification Gates

| Gate | Required Checks |
|---|---|
| Navigation | Admin, limited permission user, no-permission user. |
| Responsive | Mobile, tablet, laptop, desktop, collapsed desktop. |
| Accessibility | Keyboard path, focus restore, labels, contrast. |
| Performance | Query count, Livewire payload, cache behavior. |
| Build | Vite build and relevant Laravel tests. |

