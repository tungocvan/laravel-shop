
<Module_Name>="Account"

Step 01:
Read docs/NAME_PROJECT_ANALYSIS.md

Based on the analysis:

Create a ROADMAP.md

Classify tasks:

P0 = Critical
P1 = Important
P2 = Nice to have

Estimate:
- Complexity
- Risk
- Impact

Generate implementation order.

Step 02:

<Module_Name>="System"
You are a Senior Laravel 12 Architect.

Read ROADMAP.md first.
docs/AI_PROJECT_CONTEXT.md if exists
docs/CODEX_BOOTSTRAP.md if exists

Analyze this module only:

Modules/<Module_Name>

Do not change any code yet.

Generate this file:

docs/modules/<Module_Name>/ANALYSIS.md

Please analyze by this flow:

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

1. Module purpose
2. Route list
3. Controllers
4. Page Blade files
5. Livewire PHP classes
6. Livewire Blade views
7. Services and public methods
8. Models and database tables
9. Import/Export classes
10. Authorization/security risks
11. Validation problems
12. Transaction risks
13. N+1/query performance risks
14. Duplicate logic
15. Files that look unused
16. Refactor plan:
   - P0 Critical
   - P1 Important
   - P2 Nice to have

Important rules:
- Do not refactor now.
- Do not edit code now.
- Do not touch unrelated modules.
- Every issue must include exact file path.
- Every recommendation must include priority: P0, P1, or P2.




Step 03:

Read:
docs/modules/<Module_Name>/ANALYSIS.md 
docs/AI_PROJECT_CONTEXT.md if exists
docs/CODEX_BOOTSTRAP.md if exists
ROADMAP.md

Do not write code yet.

Create:

docs/modules/<Module_Name>/REFACTOR_PLAN.md
For every issue found in ANALYSIS.md, generate:

# <Module_Name> Refactor Plan

## 1. Executive Summary

## 2. P0 Critical Fixes

For each item:

* Issue
* Root Cause
* Business Impact
* Technical Impact
* Proposed Solution
* Files To Change
* Risk Level
* Complexity
* Estimated Effort
* Acceptance Criteria

## 3. P1 Important Refactors

Use the same structure.

## 4. P2 Nice To Have Improvements

Use the same structure.

## 5. Recommended Implementation Order

### Phase 1: Safety and Security

### Phase 2: Correctness and Maintainability

### Phase 3: Performance and Cleanup

## 6. Files Change Matrix

Create a table:

| File Path | Change Type | Priority | Reason |

## 7. Risk Control

Explain what should not be changed yet.

Important rules:

* Do not generate code.
* Do not modify files except creating REFACTOR_PLAN.md.
* Focus on Laravel 12 best practices.
* Focus on Livewire 3 best practices.
* Keep module boundaries clean.
* Every recommendation must contain exact file paths.

Step 04:

<Module_Name>="System"
ROADMAP.md
docs/AI_PROJECT_CONTEXT.md if exists
docs/CODEX_BOOTSTRAP.md if exists
docs/modules/<Module_Name>/ANALYSIS.md
docs/modules/<Module_Name>/REFACTOR_PLAN.md

Do not write code.

Generate:

docs/modules/<Module_Name>/REBUILD_SPEC.md 

This file will be the implementation specification for rebuilding or refactoring the Category module.

Include:

<Module_Name> Rebuild Specification

1. Goal

Explain what the rebuilt/refactored module must achieve.

2. Target Architecture

Use this flow:

Route
→ Controller
→ Page Blade
→ Livewire PHP
→ Livewire Blade
→ Shared Components
→ Service
→ Import
→ Export
→ Model
→ Migration

3. Database Design

Include:

Tables
Columns
Indexes
Foreign keys
Constraints
Migration notes
4. Model Design

Include:

Model classes
Fillable fields
Casts
Relationships
Scopes
Accessors / mutators if needed
5. Service Design

Include:

Service classes
Public methods
Responsibilities
Transaction boundaries
Business rules
6. Livewire Design

Include:

Component list
State properties
Validation rules
Events
Pagination
Search/filter/sort behavior
7. Blade/UI Design

Include:

Page Blade files
Livewire Blade files
Shared components
AdminLTE/Bootstrap layout rules
Table design
Form design
8. Import Design

Include:

Import classes
Header mapping
Column mapping
Row normalization
Row validation
Duplicate handling
Error reporting
9. Export Design

Include:

Export classes
Query design
Export mapping
Template generation if needed
Large export strategy
10. Permissions and Authorization

Include:

Required permissions
Policy/Gate checks
Livewire action protection
Route middleware
11. Transactions and Data Integrity

Include:

Actions requiring DB transactions
Rollback conditions
Idempotency concerns
12. Performance Strategy

Include:

Eager loading
Query optimization
Pagination
Caching if needed
13. Test Strategy

Include:

Route tests
Livewire tests
Service tests
Import tests
Export tests
Authorization tests
14. Implementation Checklist

Create checklist grouped by:

P0
P1
P2

Important rules:

Do not write code.
Do not modify module files.
Only create REBUILD_SPEC.md.
Every design decision must reference the issue from ANALYSIS.md or REFACTOR_PLAN.md.
If a decision is uncertain, mark it as “Needs confirmation before coding”.

---------------------------


Before doing anything, read these files in order:
<Module_Name>="Post"
1. docs/CODEX_BOOTSTRAP.md
2. docs/AI_PROJECT_CONTEXT.md
3. docs/PROJECT_BOOTSTRAP.md
4. ROADMAP.md
5. docs/modules/<Module_Name>/ANALYSIS.md
6. docs/modules/<Module_Name>/REFACTOR_PLAN.md
7. docs/modules/<Module_Name>/REBUILD_SPEC.md

Then refactor the existing module safely:

Modules/<Module_Name>

Goal:

Rewrite/refactor the <Module_Name> module according to REBUILD_SPEC.md.

Important rules:

* Follow the actual module autoload architecture from docs/PROJECT_BOOTSTRAP.md.
* Follow the coding standards from docs/AI_PROJECT_CONTEXT.md.
* Follow the implementation priorities from ROADMAP.md.
* Follow the module-specific analysis, refactor plan, and rebuild spec.
* Do not modify unrelated modules.
* Do not create a new ServiceProvider unless PROJECT_BOOTSTRAP.md requires it.
* Do not change composer.json unless absolutely required.
* Preserve existing database compatibility unless REBUILD_SPEC.md explicitly says otherwise.
* Preserve existing routes and Livewire aliases unless REBUILD_SPEC.md explicitly says otherwise.
* Keep business logic in Services.
* Keep Livewire focused on UI state and actions.
* Keep ImportExport.php as a thin orchestrator.
* Use transactions for multi-record writes.
* Add authorization checks for mutating actions.
* Add validation before persistence.
* Prevent N+1 queries.

Implementation order:

1. List all files that will be changed or created.
2. Explain the change plan briefly.
3. Implement P0 items first.
4. Then implement P1 items.
5. Ignore P2 unless safe and clearly isolated.
6. Generate or update tests where possible.
7. Generate:

docs/modules/<Module_Name>/IMPLEMENTATION_SUMMARY.md

Include:

* Files changed
* What was implemented
* Remaining risks
* Tests added or recommended
* Manual verification checklist

-------------------
Use:
Cấp độ 1 — 

composer.json
        ↓
ModuleServiceProvider.php
        ↓
PROJECT_BOOTSTRAP.md
        ↓
ROADMAP.md
        ↓
ANALYSIS.md
        ↓
REFACTOR_PLAN.md
        ↓
REBUILD_SPEC.md
        ↓
FULL CODE

Cấp độ 2 — 
Rewrite Module: Modules/<Module_Name>
Không sửa code cũ.
Thiết kế lại hoàn toàn Module <Module_Name> theo kiến trúc chuẩn.

Cấp độ 3 — Generate Full Module From Specification
Bạn đã có:
ANALYSIS.md
ROADMAP.md
REFACTOR_PLAN.md
REBUILD_SPEC.md
Sau đó yêu cầu Codex:

Read:
docs/modules/<Module_Name>/ANALYSIS.md
docs/modules/<Module_Name>/REFACTOR_PLAN.md
docs/modules/<Module_Name>/REBUILD_SPEC.md
docs/modules/<Module_Name>/INFORMATION.md if exists
ROADMAP.md

Generate a completely new version of:

Modules/<Module_Name>

Do not modify existing code.

Create a new implementation following:

Laravel 12
Livewire 3
Bootstrap/AdminLTE
Module Architecture

Required Structure:

Modules/<Module_Name>/

├── Routes/
├── Livewire/
├── Services/
├── Import/
├── Export/
├── Models/
├── Database/
├── Resources/views/

Architecture:

Route
→ Controller
→ Page Blade
→ Livewire PHP
→ Livewire Blade
→ Shared UI Panel
→ Service
→ Import
→ Export
→ Model
→ Database

Requirements:

1. Apply all P0 fixes.
2. Apply all P1 improvements.
3. Ignore P2 unless easy.
4. Business logic only in Services.
5. ImportExport.php must be thin.
6. Use Shared ImportExport foundation.
7. Use transactions.
8. Prevent N+1 queries.
9. Add validation.
10. Add authorization.
11. Generate complete code.

Output:

A. Folder structure
B. Migration
C. Model
D. Service
E. Import
F. Export
G. Livewire PHP
H. Livewire Blade
I. Routes
J. Tests

Generate files one by one.


-------------------------

# MASTER PROMPT — REWRITE / REFACTOR EXISTING MODULE v1.0

Module_Name=<Module_Name>

You are a Senior Laravel 12 Developer and Refactoring Architect.

Before doing anything, read these files in order:

1. docs/CODEX_BOOTSTRAP.md
2. docs/AI_PROJECT_CONTEXT.md
3. docs/PROJECT_BOOTSTRAP.md
4. ROADMAP.md
5. docs/modules/<Module_Name>/ANALYSIS.md
6. docs/modules/<Module_Name>/REFACTOR_PLAN.md
7. docs/modules/<Module_Name>/REBUILD_SPEC.md
8. docs/modules/<Module_Name>/INFORMATION.md if exists

Target module:

Modules/<Module_Name>

Goal:

Rewrite/refactor the existing <Module_Name> module according to REBUILD_SPEC.md and INFORMATION.md if it exists.

Important rules:

* Do not start coding before listing the files that will be changed or created.
* Do not modify unrelated modules.
* Do not remove existing features unless REBUILD_SPEC.md explicitly requires it.
* Preserve existing database compatibility unless REBUILD_SPEC.md explicitly says otherwise.
* Preserve existing routes unless REBUILD_SPEC.md explicitly says otherwise.
* Preserve existing Livewire aliases unless REBUILD_SPEC.md explicitly says otherwise.
* Follow the actual module autoload architecture from docs/PROJECT_BOOTSTRAP.md.
* Follow the coding standards from docs/AI_PROJECT_CONTEXT.md.
* Follow the implementation priorities from ROADMAP.md.
* Follow module-specific issues from ANALYSIS.md.
* Follow REFACTOR_PLAN.md for priority and risk control.
* Follow REBUILD_SPEC.md for target architecture.
* If INFORMATION.md exists, treat it as the final architecture review.
* Do not create a new ServiceProvider unless PROJECT_BOOTSTRAP.md explicitly requires it.
* Do not change composer.json unless absolutely required.
* If composer.json must be changed, explain why before changing it.
* Keep business logic in Services.
* Keep Livewire focused on UI state, validation, events, pagination, filters, and actions.
* Keep ImportExport.php as a thin orchestrator.
* Split large Import/Export logic into dedicated Import and Export classes when needed.
* Use DB transactions for multi-record writes.
* Add authorization checks for mutating actions.
* Add validation before persistence.
* Prevent N+1 queries with eager loading where needed.
* Avoid large unrelated rewrites.
* Prefer small, safe, reviewable changes.

Implementation order:

1. Read all required documents.
2. Summarize the current module goal.
3. List all files that will be changed or created.
4. Explain the change plan briefly.
5. Implement P0 Critical items first.
6. Implement P1 Important items next.
7. Ignore P2 unless safe, isolated, and low-risk.
8. Update or generate tests where possible.
9. Run or suggest verification commands.
10. Generate:

docs/modules/<Module_Name>/IMPLEMENTATION_SUMMARY.md

IMPLEMENTATION_SUMMARY.md must include:

* Files changed
* Files created
* What was implemented
* What was intentionally not changed
* Remaining risks
* Tests added
* Tests recommended
* Manual verification checklist
* Migration notes if any
* Rollback notes if any

Safety rules:

* If there is conflict between documents, priority order is:

1. INFORMATION.md
2. REBUILD_SPEC.md
3. REFACTOR_PLAN.md
4. ANALYSIS.md
5. ROADMAP.md
6. PROJECT_BOOTSTRAP.md
7. AI_PROJECT_CONTEXT.md
8. CODEX_BOOTSTRAP.md

* If a change is risky or uncertain, do not guess.
* Mark it as:

NEEDS CONFIRMATION BEFORE CODING

* Do not modify unrelated modules to solve local module problems.
* Do not introduce a new package unless explicitly approved.
* Do not rename public routes, Livewire aliases, database tables, or columns unless explicitly approved.

Final output after implementation:

* Brief summary
* Files changed
* Tests run or recommended
* Any remaining manual checks


---------------

<Module_Name>="System"

@architect overview <Module_Name>

Before doing anything, read these files in order:

1. docs/CODEX_BOOTSTRAP.md
2. docs/AI_PROJECT_CONTEXT.md
3. docs/PROJECT_BOOTSTRAP.md
4. ROADMAP.md
5. Modules/ModuleServiceProvider.php
6. composer.json

Then read existing module documents:

7. docs/modules/<Module_Name>/ANALYSIS.md
8. docs/modules/<Module_Name>/REFACTOR_PLAN.md
9. docs/modules/<Module_Name>/REBUILD_SPEC.md
10. docs/modules/<Module_Name>/INFORMATION.md if exists

Then inspect the actual module source code:

Modules/<Module_Name>

Goal:

Create a high-level overview and final decision for the <Module_Name> module.

Do not change any code.

Compare:

- Existing ANALYSIS.md
- Existing REFACTOR_PLAN.md
- Existing REBUILD_SPEC.md
- Existing INFORMATION.md if exists
- Actual source code in Modules/<Module_Name>

Generate:

docs/modules/<Module_Name>/OVERVIEW.md
docs/modules/<Module_Name>/REBUILD_DECISION.md

The overview must answer:

1. What is the <Module_Name> module responsible for?
2. Is the current documentation still consistent with the actual source code?
3. Are ANALYSIS.md, REFACTOR_PLAN.md and REBUILD_SPEC.md still valid?
4. What parts of the module are stable and should be preserved?
5. What parts should be refactored?
6. What parts should be rebuilt?
7. What parts should be rewritten from scratch?
8. What are the security risks?
9. What are the performance risks?
10. What are the maintainability risks?
11. Should this module be kept, refactored, safely rebuilt, or rewritten?

Final decision format:

## Final Recommendation

Decision:
- Keep as-is / Partial refactor / Safe rebuild / Rewrite from scratch

Reason:

Risk level:
- Low / Medium / High

Suggested next step:

Strict rules:

- Do not modify application code.
- Do not delete files.
- Do not generate implementation code.
- Only generate markdown documentation.
- If existing documentation conflicts with actual code, trust actual code.
- If something is unclear, mark it as "Needs verification".
