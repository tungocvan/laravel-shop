# Task: /merge-modules <ModuleA> <ModuleB> <TargetModule>

# MASTER PROMPT — MERGE MODULES LARAVEL v1.0 FINAL

## Purpose

Analyze two existing Laravel modules and merge them into a new, clean, unified module.

This is NOT a clone operation.

This is:

* analysis
* deduplication
* normalization
* redesign
* rebuild

---

## Command Format

```text
/merge-modules <ModuleA> <ModuleB> <TargetModule>
```

Example:

```text
/merge-modules Account User Identity
/merge-modules Account User AccountUser
```

---

# Input Sources

Read:

```text
ROADMAP.md

docs/modules/<ModuleA>/*
docs/modules/<ModuleB>/*
```

Also inspect:

```text
Modules/<ModuleA>/**
Modules/<ModuleB>/**
```

ONLY when documentation is incomplete.

Documentation is primary source of truth.

---

# Goal

Create a NEW module:

```text
Modules/<TargetModule>
docs/modules/<TargetModule>
```

---

# Absolute Rules

Never:

* copy ModuleA or ModuleB directly
* keep duplicated logic
* keep duplicated tables
* keep duplicated services
* keep duplicated routes
* keep duplicated permissions
* mix inconsistent naming

Always:

* deduplicate
* normalize naming
* unify architecture
* simplify structure

---

# Step 1 — Analyze Modules

Analyze both modules independently:

* purpose
* features
* routes
* services
* models
* tables
* permissions
* imports/exports
* overlaps
* conflicts

---

# Step 2 — Detect Overlap

Identify duplicated concepts:

Examples:

* Account vs User model
* login/auth logic
* profile data
* roles/permissions
* settings
* password/reset
* status/active

Create mapping:

```text
Account → User
AccountProfile → UserProfile
AccountService → UserService
```

---

# Step 3 — Conflict Resolution

For each overlap:

Choose:

* keep A
* keep B
* merge A+B
* redesign new

Document every decision.

---

# Step 4 — Define Target Module

Design:

```text
Modules/<TargetModule>
```

---

## Naming

Standardize:

* model naming
* table naming
* route naming
* permission naming
* service naming

Example:

```text
users
user_profiles
user_settings
```

---

# Step 5 — Database Design

Create NEW database schema.

Never reuse both schemas blindly.

Merge:

* tables
* columns
* constraints
* indexes

Remove:

* duplicated fields
* redundant tables

---

# Step 6 — Architecture Design

Use standard flow:

```text
Route
→ Controller
→ Page Blade
→ Livewire
→ Service
→ Model
→ Database
```

---

# Step 7 — Generate REBUILD_SPEC

Create:

```text
docs/modules/<TargetModule>/REBUILD_SPEC.md
```

Include:

* final architecture
* database design
* service design
* Livewire design
* permission design
* import/export design
* migration strategy
* data migration plan

---

# Step 8 — Data Migration Strategy

VERY IMPORTANT

Define:

* how to move data from ModuleA
* how to move data from ModuleB
* how to resolve duplicates

Example:

```text
accounts.email == users.email → merge
```

---

# Step 9 — Generate Module

Create:

```text
Modules/<TargetModule>
```

with:

* clean architecture
* no duplicated logic
* unified naming
* new migrations
* new routes
* new permissions

---

# Step 10 — Seeder

Generate:

```text
Database/Seeders/<TargetModule>Seeder.php
```

Include:

* sample users
* roles
* permissions
* demo data

---

# Step 11 — Documentation

Generate:

```text
docs/modules/<TargetModule>/

ANALYSIS.md
INFORMATION.md
README.md
REFACTOR_PLAN.md
REBUILD_SPEC.md
MIGRATION_PLAN.md
```

---

# MIGRATION_PLAN.md

Must include:

* old tables → new tables
* field mapping
* data merge logic
* rollback plan

---

# Final Output

```md
# /merge-modules Completed

Modules:
<ModuleA> + <ModuleB> → <TargetModule>

Generated:

- Modules/<TargetModule>
- docs/modules/<TargetModule>

Key Decisions:

- overlap resolved
- database merged
- services unified

Next Steps:

/review-module <TargetModule>
/migrate-data <TargetModule>
```

---

# Uncertainty Rule

If conflict cannot be resolved:

```text
Needs confirmation before coding
```

---

# Quality Standard

The result must be:

* clean
* deduplicated
* scalable
* consistent
* maintainable
* production-ready
