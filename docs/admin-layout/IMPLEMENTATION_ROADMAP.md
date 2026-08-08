# Implementation Roadmap

## Phase 0: Documentation And Baseline

| Item | Estimate | Difficulty | Risk | Impact | Dependencies |
|---|---:|---|---|---|---|
| Publish admin layout docs | 0.5 day | Low | Low | High | None |
| Capture current screenshots | 0.5 day | Low | Low | Medium | Running app |
| Inventory admin pages using master layout | 0.5 day | Low | Low | Medium | None |

## Phase 1: Layout Context

| Item | Estimate | Difficulty | Risk | Impact | Dependencies |
|---|---:|---|---|---|---|
| Define config contract | 0.5 day | Medium | Low | High | Phase 0 |
| Add layout context service/view model | 1 day | Medium | Medium | High | Config contract |
| Move setting reads into context | 0.5 day | Medium | Medium | Medium | Context |

## Phase 2: Navigation Builder

| Item | Estimate | Difficulty | Risk | Impact | Dependencies |
|---|---:|---|---|---|---|
| Create navigation DTO/array contract | 1 day | Medium | Medium | High | Phase 1 |
| Move permission pruning to service | 1 day | High | High | High | DTO contract |
| Move active-state detection to service | 1 day | High | High | High | DTO contract |
| Replace broad cache flushes | 0.5 day | Medium | Medium | Medium | Menu services |

## Phase 3: Component Extraction

| Item | Estimate | Difficulty | Risk | Impact | Dependencies |
|---|---:|---|---|---|---|
| Extract nav item component | 0.5 day | Medium | Medium | Medium | Phase 2 |
| Extract nav group component | 0.5 day | Medium | Medium | Medium | Phase 2 |
| Normalize icon component | 0.5 day | Medium | Medium | Medium | Component tests |
| Add page header and toolbar | 1 day | Medium | Medium | High | Design system |

## Phase 4: Accessibility And Responsive

| Item | Estimate | Difficulty | Risk | Impact | Dependencies |
|---|---:|---|---|---|---|
| Add skip link and landmarks | 0.5 day | Low | Low | High | Phase 1 |
| Add drawer focus management | 1 day | Medium | Medium | High | JS module |
| Add mobile search overlay | 1 day | Medium | Medium | Medium | Search component |
| Add toast live region semantics | 0.5 day | Low | Low | Medium | Toast component |

## Phase 5: JavaScript Consolidation

| Item | Estimate | Difficulty | Risk | Impact | Dependencies |
|---|---:|---|---|---|---|
| Move search shortcut to Vite module | 0.5 day | Medium | Medium | Medium | Asset build |
| Move toast manager to Vite module | 1 day | Medium | Medium | High | Toast contract |
| Add layout Alpine store | 1 day | Medium | Medium | High | Context/config |

## Phase 6: Verification

| Item | Estimate | Difficulty | Risk | Impact | Dependencies |
|---|---:|---|---|---|---|
| Add layout feature tests | 1 day | Medium | Medium | High | Test infra |
| Add Livewire tests for widgets | 1 day | Medium | Medium | Medium | Components |
| Run frontend build | 0.25 day | Low | Low | High | Assets |
| Manual responsive audit | 0.5 day | Low | Low | High | Running app |

## Recommended Release Slices

1. Documentation plus screenshots.
2. Layout context with no visual change.
3. Navigation builder with same markup.
4. Component extraction with accessibility improvements.
5. Responsive and JavaScript cleanup.
6. Optional visual polish.

