# CHANGE PLAN — Module Column, Module Filter & Multi-file Export

## Goal

Extend `System / Database / TableList` with three related capabilities:

1. Display the owning **Module** for each database table.
2. Filter the table list by Module.
3. Allow selecting multiple tables and exporting them as **separate `.sql` files packaged in one `.zip` download**.

The existing per-table Export, Import, Restore, Truncate and Drop behavior must remain compatible.

## Scope

Expected implementation scope after approval:

```text
Modules/System/Livewire/Database/TableList.php
Modules/System/resources/views/livewire/database/table-list.blade.php
Modules/System/Services/DatabaseService.php
```

A small System config/helper may be added only if needed for explicit ownership overrides.

No database migration is required.

---

## 1. Table → Module Ownership

### Requirement

Each table row should expose:

```text
Table Name | Module | Rows | Size | Actions
```

### Recommended ownership strategy

Do **not** guess ownership from table-name prefixes alone.

Determine ownership primarily from migration source location:

```text
Modules/<ModuleName>/database/migrations/*
    Schema::create('<table>')
        ↓
<table> belongs to <ModuleName>
```

Root application migrations under:

```text
database/migrations/*
```

should be classified as:

```text
Core
```

Tables that cannot be resolved safely should be classified as:

```text
Unknown
```

### Safety / performance

- Never execute migration files to discover ownership.
- Inspect migration source text only.
- Detect the initial `Schema::create(...)` owner; `Schema::table(...)` in another module does not transfer ownership.
- Build the ownership map once per service request and cache it where appropriate.
- Support explicit override mapping if a package/legacy table cannot be inferred correctly.

Conceptually:

```text
DatabaseService::getTableModuleMap()
DatabaseService::getAvailableModules()
```

`getAllTables()` should enrich each row with:

```php
'module' => 'Administrative'
```

or `Core` / `Unknown`.

---

## 2. Module Column

Add a new table column:

```text
Module
```

Recommended display:

- normal module name as a small badge/text;
- `Core` visually distinct but neutral;
- `Unknown` visibly identifiable without implying an error.

The column is informational only and must not alter database operations.

---

## 3. Filter by Module

### Livewire state

Add:

```text
moduleFilter
```

Default:

```text
all
```

### Filter options

Build from discovered ownership values plus:

```text
Tất cả Module
Core
Unknown
```

Only show Module names that actually own at least one current database table.

### Behavior

`search` and `moduleFilter` work together:

```text
Search text
AND
Module filter
```

Example:

```text
moduleFilter = Administrative
search = status
```

returns only tables owned by `Administrative` whose table name matches `status`.

When the module filter changes:

- reset `selectAll`;
- clear or reconcile `selectedTables` so hidden selections are not misleading.

---

## 4. Checkbox Multi-file Export

### Current behavior to change

The current bulk selection export creates one combined SQL dump.

### New behavior

Selected tables should be exported independently:

```text
selected table A → table-a.sql
selected table B → table-b.sql
selected table C → table-c.sql
                     ↓
               one ZIP archive
```

Example output:

```text
db_tables_2026-08-09_19-30-00.zip
├── users_profile.sql
├── administrative_submissions.sql
└── administrative_files.sql
```

### Why ZIP

Prefer one ZIP download rather than triggering many browser downloads because:

- browsers can block multiple automatic downloads;
- one request is easier to authorize and audit;
- each table remains an independent SQL file;
- files can later be imported individually with the existing Import Table feature.

### Authorization

Bulk export requires:

```text
database.backup
```

The permission must remain enforced inside the Livewire action.

### Server validation

Every selected table name must be validated server-side using the existing table identifier/existence checks.

Never trust checkbox/browser state directly.

---

## 5. DatabaseService Changes

Recommended service workflow:

```text
exportTablesAsArchive(array $tableNames): string
```

Responsibilities:

1. reject empty selection;
2. normalize/de-duplicate table names;
3. validate each table against the current database;
4. create a private temporary working directory;
5. run one `mysqldump` per table to create an independent `.sql` file;
6. create a `.zip` archive containing those SQL files;
7. remove temporary individual dump files/work directory;
8. return only the safe archive identifier/filename;
9. log technical failure details server-side.

The archive should be stored under the existing private backup area so the controlled download route can be reused if compatible.

### Download compatibility

The current download resolver/route is SQL-oriented. Implementation must update the safe backup identifier rules to allow the generated `.zip` archive **only for controlled System backup downloads**, without weakening path traversal protections.

Do not allow arbitrary extensions or arbitrary paths.

---

## 6. Livewire Changes

Expected state/action updates:

```text
moduleFilter
selectedTables
selectAll
bulkExportFile
```

Recommended actions:

```text
updatedModuleFilter()
exportSelected()
```

`exportSelected()` should:

```text
Authorize database.backup
    ↓
Validate current selection through DatabaseService
    ↓
Generate ZIP archive
    ↓
Expose controlled download link
    ↓
Keep/clear selection intentionally
```

Recommended: keep selection after export so the admin can verify/re-export, but reset `selectAll` if the visible filtered set changes.

---

## 7. Blade / UI

Header controls should become conceptually:

```text
[ Select all ]        [ Module ▼ ] [ Search tables... ] [ Full Backup ] [ Restore Database ]
```

The exact responsive ordering may adapt to screen size.

Table:

```text
☐ | Table | Module | Rows | Size | Quick actions | Management
```

Bulk action bar:

```text
Đã chọn N bảng
[ Export N bảng (.zip) ]
```

While exporting:

- disable the bulk export button;
- show spinner/indeterminate state;
- prevent duplicate request;
- show safe error message on failure.

After success:

```text
Tải file ZIP
```

through the controlled download route.

---

## 8. Interaction with Import Table

Each `.sql` inside the ZIP must use the same per-table dump format produced by normal table Export.

Therefore the intended round-trip is:

```text
Select multiple tables
    ↓
Export ZIP
    ↓
Extract ZIP locally
    ↓
Choose one table SQL
    ↓
Import Table
```

No bulk import is included in this scope.

---

## 9. Protected Tables

Bulk **export** may include protected tables because export is read-only and existing per-table backup already permits them, subject to `database.backup`.

Existing restrictions remain:

- protected tables cannot be manually truncate/drop;
- protected tables cannot be imported through Import Table unless separately approved.

---

## 10. Tests / Verification

Recommended focused coverage:

```text
[ ] table ownership is resolved from module migration location
[ ] root migrations map to Core
[ ] unresolved tables map to Unknown
[ ] Schema::table in another module does not transfer ownership
[ ] module filter returns only matching tables
[ ] module filter combines correctly with text search
[ ] changing module filter keeps selection state coherent
[ ] unauthorized user cannot bulk export
[ ] empty bulk selection is rejected
[ ] manipulated/nonexistent table names are rejected
[ ] selected tables produce separate SQL files
[ ] ZIP contains exactly the selected validated tables
[ ] temporary SQL files are cleaned after ZIP creation
[ ] generated ZIP can be downloaded through controlled route
[ ] path traversal remains rejected
[ ] existing per-table Export/Import/Restore continues working
```

Manual verification:

1. filter by one known Module;
2. select 2–3 tables;
3. export ZIP;
4. inspect archive and confirm one SQL file per table;
5. use one extracted SQL file with existing Import Table on a non-production test table.

---

## 11. Compatibility

Preserve:

```text
admin.system.database.index
admin.system.database.download
system.database.table-list
```

Preserve existing permissions:

```text
database.view
database.backup
database.restore
database.destroy
database.download
```

Do not add a new permission unless later requested.

No schema migration is required.

---

## Acceptance Criteria

```text
[ ] TableList displays a Module column
[ ] Every current table resolves to Module / Core / Unknown
[ ] Module filter works together with table-name search
[ ] Select All operates on the currently visible filtered result
[ ] Admin can select multiple tables
[ ] Bulk export generates one ZIP
[ ] ZIP contains one independent SQL file per selected table
[ ] Every selected table is server-validated
[ ] ZIP download remains protected against arbitrary paths/extensions
[ ] Temporary export files are cleaned up
[ ] Existing Import Table can consume an extracted per-table SQL dump
[ ] Existing database-manager features remain compatible
```

## Implementation Gate

**Do not implement this change until the user explicitly approves this updated `CHANGE_PLAN.md`.**
