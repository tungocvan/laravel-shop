# Task: /create-module-from-docs <SourceModule> <TargetModule> --mode=new-db --with-seeder

# MASTER PROMPT — CREATE MODULE FROM DOCUMENTATION v1.0 FINAL

## Purpose

Create a completely new Laravel module from the documentation of an existing module.

This command MUST NOT perform a direct file clone.

It MUST rebuild the new module using:

```text
docs/modules/<SourceModule>/
```

as the source of truth.

---

## Command Format

```text
/create-module-from-docs <SourceModule> <TargetModule> --mode=new-db
```

Example:

```text
/create-module-from-docs Website WebsiteV2 --mode=new-db
/create-module-from-docs Product ProductV2 --mode=new-db
/create-module-from-docs Website WebsiteV2 --mode=new-db --with-seeder
```

---

# Goal

Generate:

```text
Modules/<TargetModule>/
docs/modules/<TargetModule>/
```

with an architecture similar to:

```text
Modules/<SourceModule>
```

but completely independent.

---

# Rules

## Never

* copy source files directly
* duplicate migration timestamps
* reuse old route names
* reuse old database tables
* reuse old permissions
* reuse old cache keys
* reuse old storage paths
* reuse old view namespaces
* modify SourceModule

The source module is read-only.

---

# Read Source Documentation

Read if exists:

```text
docs/modules/<SourceModule>/ANALYSIS.md
docs/modules/<SourceModule>/INFORMATION.md
docs/modules/<SourceModule>/README.md
docs/modules/<SourceModule>/REFACTOR_PLAN.md
docs/modules/<SourceModule>/REBUILD_SPEC.md
```

Also inspect:

```text
Modules/<SourceModule>/**
```

only if documentation is incomplete.

Documentation is the primary source of truth.

---

# Determine Source Architecture

Identify:

* module structure
* routes
* controllers
* Livewire components
* services
* models
* migrations
* permissions
* imports
* exports
* events
* jobs
* shared dependencies
* storage
* cache usage

---

# Create Target Module

Generate:

```text
Modules/<TargetModule>/
```

with similar architecture.

---

# Required Structure

```text
Modules/<TargetModule>/

├── Config
├── Routes
├── Controllers
├── Livewire
├── Models
├── Services
├── Policies
├── Import
├── Export
├── Events
├── Jobs
├── Resources/views
├── Database/Migrations
├── Database/Seeders
└── config/module.php
```

Only generate folders that are actually required.

---

# Naming Rules

Replace:

```text
<SourceModule>
```

with:

```text
<TargetModule>
```

in:

* namespace
* routes
* config
* view namespace
* policies
* events
* jobs
* services
* imports
* exports

---

# Route Rules

Do NOT reuse:

```text
website.
website::
website
```

Generate:

```text
website-v2.
website-v2::
website-v2
```

using Laravel conventions.

---

# Permission Rules

Never duplicate:

```text
website.view
website.create
website.edit
website.delete
```

Generate:

```text
website-v2.view
website-v2.create
website-v2.edit
website-v2.delete
```

---

# Cache Rules

Never reuse:

```text
website.*
```

Generate:

```text
website_v2.*
```

---

# Storage Rules

Never reuse:

```text
storage/app/websites
```

Generate:

```text
storage/app/website-v2
```

---

# Database Mode

Current mode:

```text
--mode=new-db
```

This means:

The new module MUST have its own database tables.

Never reuse:

```text
websites
website_blocks
website_menus
```

Generate:

```text
website_v2s
website_v2_blocks
website_v2_menus
```

following Laravel naming conventions.

---

# Migration Rules

Generate NEW migrations.

Never copy migration timestamps.

Create new migration filenames.

Review:

* foreign keys
* indexes
* unique constraints
* cascade rules

and adapt them to the new table names.

---

# Model Rules

Generate:

```text
Modules/<TargetModule>/Models
```

using:

* new namespace
* new table names
* new relationships
* new factories if needed

---

# Service Rules

Recreate services from:

```text
REBUILD_SPEC.md
```

Do not blindly copy code.

Rebuild the service architecture.

---

# Import / Export Rules

Generate:

```text
Import/
Export/
Services/ImportExport.php
```

only if the source module uses Import/Export.

Follow the project's shared Import/Export foundation.

---

# Shared Dependency Rules

Detect:

* Shared Services
* Shared Components
* Traits
* Helpers

Reuse them.

Do not duplicate shared code.

---
# Seeder / Sample Data Rules

Always generate sample data support for the target module.

Generate when applicable:

Modules/<TargetModule>/Database/Seeders/<TargetModule>Seeder.php

The seeder must:

- create realistic sample data
- use the new target tables only
- not insert into source module tables
- respect foreign keys and relationships
- be safe to run multiple times when possible
- use `updateOrCreate`, `firstOrCreate`, or clear documented demo records only
- avoid overwriting real production data
- include sample records for parent/child structures if the module has hierarchy
- include sample records for related tables if required
- include demo permissions if the module has permissions
- include demo settings/config records if the module uses settings
- include sample import/export data if the module supports import/export

If the module needs Excel or CSV demo import data, generate:

database/seeders/data/<target-module>/
or
Modules/<TargetModule>/Database/Data/

Examples:

- sample.xlsx
- sample.csv
- import-template.xlsx
- demo-data.json

Seeder must clearly separate:

- required system data
- demo/sample data
- optional test data

Add instructions in:

docs/modules/<TargetModule>/README.md

including:

php artisan db:seed --class=Modules\\<TargetModule>\\Database\\Seeders\\<TargetModule>Seeder
---
# Documentation Rules

Generate:

```text
docs/modules/<TargetModule>/

ANALYSIS.md
INFORMATION.md
README.md
REFACTOR_PLAN.md
REBUILD_SPEC.md
```

The generated documentation must describe the new module.

---

# Validation Checklist

Before finishing verify:

* [ ] module name changed
* [ ] namespaces changed
* [ ] route names changed
* [ ] view namespace changed
* [ ] permissions changed
* [ ] cache keys changed
* [ ] storage paths changed
* [ ] table names changed
* [ ] migrations regenerated
* [ ] documentation generated
* [ ] source module untouched

---

# Final Output

Return:

```md
# /create-module-from-docs Completed

Source Module:
<SourceModule>

Target Module:
<TargetModule>

Mode:
new-db

Generated:

- Modules/<TargetModule>
- docs/modules/<TargetModule>

Warnings:
- migration changes
- relationship changes
- shared dependency notes

Next Commands:

/review-module <TargetModule>
/analyze-project <TargetModule>
```

---

# Uncertainty Rule

If the source documentation is incomplete:

```text
Needs confirmation before coding
```

Do not guess.

Do not invent missing architecture.

Do not silently copy source code.
