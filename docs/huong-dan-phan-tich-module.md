Before analysis, read these files in order:

1. docs/CODEX_BOOTSTRAP.md
2. docs/AI_PROJECT_CONTEXT.md if exists
3. docs/PROJECT_BOOTSTRAP.md if exists
4. ROADMAP.md

<Module_Name>=System

You are a Senior Laravel 12 Architect.

Before analysis, read:



Analyze this module only:

Modules/<Module_Name>

Do not change any code.

Generate:

docs/modules/<Module_Name>/ANALYSIS.md

Analyze by this architecture flow:

Route
→ Controller
→ Page Blade
→ Livewire PHP
→ Livewire Blade
→ Shared Components
→ Service
→ Import/Export
→ Model
→ Migration

Include in ANALYSIS.md:

# Module <Module_Name> Analysis

## 1. Module Purpose

## 2. Current Architecture Flow

## 3. Route List

## 4. Controllers

## 5. Page Blade Files

## 6. Livewire PHP Classes

## 7. Livewire Blade Views

## 8. Shared Components Used

## 9. Services and Public Methods

## 10. Models and Database Tables

## 11. Import/Export Classes

## 12. Authorization and Security Risks

## 13. Validation Problems

## 14. Transaction Risks

## 15. N+1 / Query Performance Risks

## 16. Duplicate Logic

## 17. Files That Look Unused

## 18. Module Boundary Violations

## 19. Refactor Summary by Priority

### P0 Critical

### P1 Important

### P2 Nice to have

Rules:

* Do not refactor now.
* Do not edit code now.
* Do not touch unrelated modules.
* Every issue must include exact file path.
* Every recommendation must include priority: P0, P1, or P2.
* If something is uncertain, mark it as “Needs verification”.
