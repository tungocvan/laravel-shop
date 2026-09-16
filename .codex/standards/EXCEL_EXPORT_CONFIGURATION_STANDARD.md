# EXCEL EXPORT CONFIGURATION STANDARD

Canonical implementation skill and UI/UX standard for configurable Excel export work in this repository.

This document exists so an AI or developer implementing Excel export in another module can reproduce the approved `Modules/Pharma` Excel Designer behavior without rediscovering the architecture, UI rules, persistence contract, media behavior, JSON round-trip rules, PhpSpreadsheet layout rules, or Livewire failure modes.

The approved reference implementation is the Pharma Price List Excel Designer v3.2. Treat it as a reference pattern, not as a reason to create a hard dependency from other modules to Pharma.

---

## 1. When this standard applies

Read and apply this standard whenever a module needs one or more of the following:

- configurable Excel/XLSX export;
- user-selectable export columns;
- custom column order, labels, widths, alignment or data type;
- reusable export profiles;
- a default export profile per user;
- company/header/footer branding;
- logo or signature images in Excel;
- page setup and print layout;
- JSON save/import/portable export configuration;
- a server-side JSON configuration library;
- selected-row versus all-row export;
- an Excel configuration/designer modal or workspace.

For a trivial fixed export with no user configuration, do not reproduce the entire Designer. Use only the relevant spreadsheet/output rules.

This standard complements, and does not replace:

- `.codex/standards/MODULE_STANDARD.md`;
- `.codex/standards/ADMIN_UI_STANDARD.md`;
- `.codex/tasks/create-import-export.md` when the shared Import/Export infrastructure is applicable;
- module-specific business rules and authorization.

---

## 2. Priority and reference implementation

When implementing this capability, use this priority:

1. Current repository reality and shared infrastructure.
2. This standard.
3. `ADMIN_UI_STANDARD.md` and `MODULE_STANDARD.md`.
4. The target module's documented requirements.
5. Pharma reference implementation for concrete behavior.

Approved Pharma reference files:

```text
Modules/Pharma/Livewire/PriceList/ExportConfigurator.php
Modules/Pharma/Models/PriceListExportProfile.php
Modules/Pharma/Services/PriceListExportProfileService.php
Modules/Pharma/Services/PriceListExportJsonLibrary.php
Modules/Pharma/Services/PriceListExcelTypography.php
Modules/Pharma/Services/PriceListExcelDocumentLayout.php
Modules/Pharma/Http/Controllers/PriceListController.php
Modules/Pharma/resources/views/livewire/price-list/export-configurator-v31.blade.php
Modules/Pharma/resources/views/livewire/price-list/export-configurator-v32.blade.php
Modules/Pharma/resources/views/livewire/price-list/export-media-dimensions.blade.php
```

Reference tests:

```text
Modules/Pharma/Tests/Unit/PriceListExcelExportContractTest.php
Modules/Pharma/Tests/Unit/PriceListExcelDesignerV3ContractTest.php
Modules/Pharma/Tests/Unit/PriceListExcelMediaSizingContractTest.php
Modules/Pharma/Tests/Unit/PriceListExcelJsonMediaRoundTripContractTest.php
Modules/Pharma/Tests/Unit/PriceListExportProfileRuntimeRegressionContractTest.php
```

Do not copy Pharma namespace/model names into another module. Recreate the pattern under the owning module, or extract genuinely cross-module infrastructure into `Modules/Shared` only when reuse is proven and the ownership boundary is clear.

---

## 3. Product goal

A configurable Excel export is not merely a download button. It is a small document-design workspace.

The operator should be able to answer these questions before exporting:

1. Which saved profile am I using?
2. Which fields will appear?
3. In what order will they appear?
4. What are their Excel headers?
5. How wide and how aligned are they?
6. Which fields are text, number or date?
7. What company/recipient/signature information appears?
8. Which logo/signature is used and at what size?
9. What paper/orientation/scaling/margins will Excel use?
10. Can this configuration be saved, duplicated, exported to JSON and restored later?
11. Is the export scope all approved records or only selected records?

The Designer must make these answers visible without overwhelming the user.

---

## 4. Required architecture

Preferred flow:

```text
Page/List/Detail
    -> Excel Designer Livewire component
        -> Export Profile Service
            -> Export Profile Model/DB
        -> JSON Library Service
        -> media storage

Export request
    -> Controller/export action
        -> resolve user-owned profile
        -> resolve approved export scope
        -> query/load domain data
        -> map canonical column keys to values
        -> PhpSpreadsheet
            -> Document Layout service
            -> Typography service
            -> page setup
        -> temporary XLSX
        -> controlled download
```

Responsibilities are strict:

### Livewire component

Owns UI state, validation, temporary upload state, profile selection, column draft editing, modal state and calls to services.

It must not become the canonical owner of export business data or complex domain queries.

### Profile service

Owns defaults, canonical column catalog, normalization, profile CRUD, duplication, default-profile semantics, import/export payload validation and collision-safe naming.

### JSON library service

Owns JSON filenames, per-user directory, read/write/list/delete and path sanitization.

### Export profile model

Owns persistence casts and narrowly scoped persistence semantics. Do not hide large export workflows in model events.

### Export controller/service

Owns the actual spreadsheet generation orchestration. Domain value mapping must use the owning module's canonical models/services.

### Document layout service

Owns header/footer/logo/signature placement and dimensions.

### Typography service

Owns workbook/table font, header colors, borders and product/data font sizing.

---

## 5. Profile persistence contract

A configurable export profile should persist at least:

```text
id
user_id
name
is_default
column_order
column_groups
selected_columns
headers
alignments
widths
data_types
decimals
header_footer
page_setup
logo_path
signature_path
created_at
updated_at
```

Array-like settings should use JSON columns and Eloquent array casts.

Recommended uniqueness:

```text
UNIQUE(user_id, name)
```

Profiles are private to the owning admin/user unless the product explicitly introduces shared/team profiles.

Every query/update/delete must scope by `user_id`; never trust a profile ID from the browser without ownership scoping.

### Default profile semantics

- First profile for a user may become default automatically.
- Setting one profile default must unset the user's other defaults.
- Deleting a default profile should deterministically promote another remaining profile if the product requires a default.
- Loading with no explicit profile ID should resolve the default first, then a stable fallback.

### Collision-safe naming

Import and duplicate operations must never blindly reuse a unique name.

Use deterministic names such as:

```text
Báo giá bệnh viện
Báo giá bệnh viện - Import
Báo giá bệnh viện - Import (2)
Báo giá bệnh viện - Import (3)

Báo giá bệnh viện - Bản sao
Báo giá bệnh viện - Bản sao (2)
```

Respect the DB maximum name length while adding suffixes. Do not rely on catching a database unique-constraint exception as normal UX.

---

## 6. Canonical column catalog

Every configurable export must define a canonical catalog independent of the UI.

Recommended shape:

```php
public const COLUMNS = [
    'code' => [
        'label' => 'Mã',
        'group' => 'identity',
        'align' => 'left',
        'width' => 110,
        'type' => 'string',
    ],
    'name' => [
        'label' => 'Tên',
        'group' => 'general',
        'align' => 'left',
        'width' => 190,
        'type' => 'auto',
    ],
    'amount' => [
        'label' => 'Thành tiền',
        'group' => 'financial',
        'align' => 'right',
        'width' => 120,
        'type' => 'number',
    ],
];
```

Also define:

```php
public const GROUPS = [...];
public const DEFAULT_SELECTED = [...];
```

Rules:

- Keys are stable machine contracts. Do not casually rename them.
- Labels are user-facing and may be customized per profile.
- Group is for Designer navigation/filtering.
- Width is a Designer-friendly relative width, not necessarily direct Excel character width.
- Supported types should normally be `auto`, `string`, `number`, `date`.
- Alignment should normally be `left`, `center`, `right`.
- Decimals should be bounded, e.g. 0..6.
- Unknown keys from old/imported JSON must be normalized out rather than trusted.
- New catalog fields should merge safely into old profiles through normalization/default behavior.

The export data map and catalog must stay in contract: every exportable key must have a value mapping, and every value mapping intended for configuration must exist in the catalog.

---

## 7. Column Designer UI/UX

The approved desktop pattern is a three-part workspace:

```text
Column Designer
├── Data Library
│   ├── search
│   ├── group filter
│   ├── available fields
│   └── add group / add field
├── Selected Excel Columns
│   ├── actual A/B/C/... position
│   ├── selected count
│   ├── reorder controls
│   ├── remove
│   └── active-column selection
└── Inspector
    ├── header label
    ├── width
    ├── alignment
    ├── data type
    ├── decimals
    └── reset/presets
```

### Data Library

Must support large catalogs without becoming a long unstructured checkbox list.

Use:

- keyword search by label and key;
- group filter;
- group headings;
- `+ Thêm` action for unselected fields;
- visible `Đã chọn` state for selected fields;
- optional `+ Nhóm` action to select a complete group.

### Selected Columns

Show the actual spreadsheet position `A`, `B`, `C`, ... rather than only numeric ordering.

Provide explicit movement controls or another accessible deterministic reorder mechanism. Drag/drop may be added, but must not be the only way to reorder if accessibility/robustness suffers.

Provide:

- reset to canonical order;
- remove one field;
- clear all with appropriate warning/validation;
- selected count.

The final save/export must reject an empty selected-column set.

### Inspector isolation — mandatory

Do not bind the Inspector directly to every underlying map if editing one field can cause Livewire DOM/state bleed between selected columns.

Use isolated draft state:

```php
public array $columnDraft = [];
```

Conceptual flow:

```text
editColumn(key)
    -> commit previous draft
    -> activeColumnKey = key
    -> loadColumnDraft(key)

change inspector
    -> edit columnDraft only

apply/leave/reorder
    -> commitColumnDraft()
        -> headers[key]
        -> widths[key]
        -> alignments[key]
        -> dataTypes[key]
        -> decimals[key]
```

The draft must include its own `key`, and commit must verify that key still matches `activeColumnKey`.

Use stable `wire:key` values for Inspector and list rows.

This is an important runtime lesson from the approved implementation: a Designer can pass static tests while still behaving incorrectly if Livewire reuses DOM/state across columns.

---

## 8. Main Designer workspace

The Designer should normally open as a large modal/workspace rather than a small form dialog.

Approved hierarchy:

```text
Header
├── product/version badge
├── title
├── concise explanation
└── close

Profile toolbar
├── profile selector
├── profile name
├── default checkbox
├── create
├── duplicate
├── save JSON
├── JSON library
├── upload JSON
└── delete

Section navigation
├── 1. Thương hiệu
├── 2. Cột dữ liệu
└── 3. Trang in

Main workspace
└── active section

Footer/action area
├── validation/status
├── save profile
└── export/close action as appropriate
```

Desktop should use most of the viewport, e.g. max height around 90–94vh and a wide max width. The internal workspace should scroll, not the entire background page.

On tablet/mobile:

- avoid three permanently narrow columns;
- collapse navigation or stack sections;
- make actions wrap cleanly;
- maintain minimum touch target sizes;
- do not make the user horizontally scroll the entire modal merely to reach controls.

All ordinary buttons inside forms/modals should use `type="button"` unless they intentionally submit.

---

## 9. Branding and document content

The Branding section should support, when relevant:

```text
Header/Footer enabled
Company name
Tax code
Address
Phone
Email
Document title
Recipient / Kính gửi
Introductory text
Logo
Logo width/height
Signing location
Signing date text
Signatory title
Signatory name
Signature image
Signature width/height
```

Do not hard-code a Pharma company identity into reusable/shared code. Target modules should supply appropriate defaults.

### Signing date

The approved UI uses a human-readable signing-date field, conceptually:

```text
Ngày 16 tháng 09 năm 2026
```

Rules:

- Default to the current date only when the profile/imported field is empty.
- Never overwrite an explicit date restored from a profile or JSON.
- Prefer generating this default server-side in new clean implementations; a browser-side compatibility patch is acceptable only when maintaining legacy markup.
- The UI label should clearly say `Ngày tháng năm`, not an ambiguous `Năm` if the value contains the full date.

---

## 10. Logo and signature media

Media is profile-related but must not be embedded blindly in JSON.

### Storage

Store uploaded logo/signature files in controlled application storage with user ownership in the path or equivalent authorization boundary.

Validate uploads:

```text
image only
bounded size (reference: <= 4 MB)
approved MIME/extensions such as PNG/JPEG/WebP
```

### Dimensions

Expose width and height in centimeters when document layout requires precision.

Reference validation limits:

```text
logo_width_cm       1..12
logo_height_cm      1..8
signature_width_cm  1..12
signature_height_cm 1..8
```

The exact defaults are document-specific. The Pharma implementation demonstrates profile-scoped custom dimensions and conversion to pixels for PhpSpreadsheet drawings.

Do not let UI defaults and layout-service defaults silently disagree.

### Drawing placement

Use `PhpOffice\PhpSpreadsheet\Worksheet\Drawing`.

When centering a signature across a region, do not assume a fixed X offset. Compute the pixel width of the target columns and center the drawing based on its configured width.

Set row heights so the drawing has enough vertical space and does not overlap following content.

### Media deletion

When replacing/removing media:

- save the new profile successfully before deleting an old file when possible;
- do not delete media still referenced by another profile unless reference semantics explicitly allow it;
- check storage existence before rendering/using paths.

---

## 11. Signature identity rule — mandatory safety rule

A logo may reasonably be reusable across multiple profiles for the same user/company.

A signature must never be silently reused for the wrong person.

If an imported/saved JSON does not contain a binary signature path, signature recovery may occur only when the implementation can match the signer identity exactly enough for the domain.

Approved minimum identity:

```text
normalized signatory_title + normalized signatory_name
```

Normalization may trim, collapse whitespace and compare case-insensitively.

Rules:

- both title and name should be non-empty before automatic signature recovery;
- candidate signature must exist in storage;
- candidate must belong to the same authorized user/owner scope;
- exact normalized title AND exact normalized name must match;
- never fall back to `latest signature` or another person's signature;
- if no exact match exists, leave signature blank and let the user choose/upload it.

This rule is more important than convenience.

---

## 12. JSON profile contract

Every portable configuration payload must have an explicit schema identifier/version, for example:

```json
{
  "schema": "<module>.<feature>-export-profile.v1",
  "profile": {
    "name": "...",
    "column_order": [],
    "selected_columns": [],
    "headers": {},
    "alignments": {},
    "widths": {},
    "data_types": {},
    "decimals": {},
    "header_footer": {},
    "page_setup": {}
  }
}
```

Use the actual service contract as canonical; the example above is illustrative.

### JSON must not contain

- database primary keys as portable identity;
- `user_id`/owner IDs;
- `is_default` unless explicitly part of a safe import policy;
- absolute filesystem paths;
- temporary URLs;
- secrets/tokens;
- binary/base64 logo/signature blobs by default;
- another user's storage paths.

Media should be resolved through approved metadata/reference behavior after import, not treated as portable binary state.

### Import validation

On import:

1. decode JSON safely;
2. verify schema/version;
3. reject malformed payloads with a clear message;
4. normalize column order against current catalog;
5. normalize selected fields;
6. normalize map keys;
7. clamp widths/decimals/page settings;
8. merge safe defaults for missing fields;
9. create a new collision-safe profile unless explicit overwrite semantics exist;
10. resolve logo/signature only through authorized media rules.

Never mass-assign arbitrary imported JSON directly to a model.

---

## 13. Server JSON library

When the product supports reusable server-side JSON files, the library must be private per user/admin.

Recommended directory shape:

```text
<module>/<feature>-export-profiles/<userId>/profile.json
```

The Pharma reference uses Laravel's `local` disk and a per-user directory.

### Filename security

Normalize filenames using `basename` semantics, replace unsafe characters, trim dangerous leading/trailing punctuation, bound length and enforce `.json`.

Never concatenate a raw browser filename into a storage path.

### Library UI

The JSON library modal should show:

- selectable row/radio;
- filename;
- modified date/time;
- approximate size;
- delete action;
- empty state;
- `Import file đã chọn` action disabled until a selection exists.

Use stable `wire:key` for rows.

Prefer native value binding for filenames:

```blade
<input
    type="radio"
    wire:model.live="selectedJsonFile"
    value="{{ $file['name'] }}"
>
```

Avoid embedding arbitrary filenames directly into generated Livewire method-expression strings when a native value/bound ID can be used.

---

## 14. Confirmation modal architecture — mandatory Livewire rule

Do not give a public Livewire property and a public Livewire method the same name.

This caused a real runtime regression in the reference implementation: tests passed, the confirmation modal opened, but clicking `Xác nhận` appeared to do nothing because state/action naming collided.

Forbidden pattern:

```php
public string $confirmAction = '';

public function confirmAction(): void
{
    // ...
}
```

Use collision-free state and action names:

```php
public bool $confirmOpen = false;
public string $pendingConfirmAction = '';
public string $pendingConfirmValue = '';

private function openConfirmation(string $title, string $action, string $value = ''): void
{
    $this->noticeTitle = $title;
    $this->pendingConfirmAction = $action;
    $this->pendingConfirmValue = $value;
    $this->confirmOpen = true;
}

public function executeConfirmedAction(): void
{
    $action = $this->pendingConfirmAction;
    $value = $this->pendingConfirmValue;

    $this->confirmOpen = false;
    $this->pendingConfirmAction = '';
    $this->pendingConfirmValue = '';

    // dispatch by action + captured value
}
```

Blade:

```blade
<button
    type="button"
    wire:click="executeConfirmedAction"
    wire:loading.attr="disabled"
    wire:target="executeConfirmedAction"
>
    Xác nhận
</button>
```

Important rules:

- capture the target ID/filename when the user requests deletion;
- do not read a mutable current selection later and assume it is the same target;
- clear pending confirmation state after snapshotting it;
- use loading/disabled state to prevent duplicate execution;
- destructive buttons must be visually distinct;
- modal z-index must be above the Designer and JSON library overlays;
- test the actual browser interaction, not only source-code contracts.

---

## 15. Profile actions

The Designer should support these actions when applicable:

### Create new

Load safe defaults into an unsaved profile state. Do not mutate the current saved profile merely by clicking `Tạo mới`.

### Save

Validate profile name, selected columns, branding/media dimensions and page settings. Persist through the profile service.

### Set default

Default semantics are per user/owner.

### Duplicate

Duplicate into a new profile with collision-safe naming. Do not preserve DB identity.

### Delete

Require confirmation and use the captured profile ID. After delete:

```text
profileId = null
refresh profile list
load default/fallback profile
show success feedback
```

### JSON save

Serialize the portable payload, not the raw Eloquent model.

### JSON import

Create a new normalized profile and show explicit success feedback.

---

## 16. Page setup

The Page/Print section should expose only settings that the export engine truly applies.

Typical settings:

```text
paper_size: A4/A3/LETTER/LEGAL
orientation: landscape/portrait
margins in cm
center_horizontal
center_vertical
scaling: none/fit_width/fit_sheet
fit_width
fit_height
product/data font size
header background
header text color
table border
```

Validation must whitelist enum values and bound numeric values.

PhpSpreadsheet notes:

- convert centimeters to inches for page margins: `cm / 2.54`;
- map paper-size strings explicitly to `PageSetup` constants;
- map orientation explicitly;
- when fitting width, use `setFitToWidth()` and appropriate height semantics;
- freeze panes immediately below the table header when useful;
- do not expose a Designer setting that is ignored during export.

---

## 17. Typography

The reference implementation uses `Times New Roman` for the entire generated workbook/table and explicitly stamps the used range so Excel does not fall back to another default font.

A module may use another approved document font if its domain requires it, but the choice must be deterministic.

Typography service should own:

- workbook/default font family;
- default font size;
- allowed data/product font sizes;
- header font/bold;
- header background color;
- header text color;
- table borders;
- data-row font sizing.

Colors must be normalized to valid 6-digit RGB hex values before use.

Header should normally be centered vertically/horizontally and wrapped.

Data alignment remains column-specific.

---

## 18. Excel data types

Do not let Excel guess identifiers that must remain text.

### `string`

Use explicit string cells for:

- codes with leading zero;
- registration/license numbers;
- tax codes;
- SKUs/barcodes when numeric-looking formatting must be preserved;
- decision/document numbers.

Conceptually:

```php
$sheet->setCellValueExplicit($coordinate, (string) $value, DataType::TYPE_STRING);
```

### `number`

Write numeric values as numeric cells and apply a number format. Bound decimal precision.

### `date`

Convert real dates to Excel serial values and apply a visible date format such as `dd/mm/yyyy`.

If a configured date value cannot be parsed safely, prefer preserving the original value as text rather than throwing away data.

### `auto`

Use only where Excel's normal value handling is acceptable.

Null/empty values should remain empty cells rather than misleading zeroes unless the domain explicitly defines zero.

---

## 19. Column width conversion

Designer width and PhpSpreadsheet width are not identical units.

The Pharma reference stores a convenient UI-relative width and converts it to Excel column width during export. Other modules may refine the conversion, but it must be deterministic and bounded.

Rules:

- clamp extremely narrow/wide values;
- show the operator that Designer width is relative when it is not a literal Excel unit;
- apply wrap text for long values;
- ensure header text remains readable;
- verify actual XLSX output, not only the browser preview.

---

## 20. Header/footer document layout

A professional business export should separate document layout from table data generation.

Reference header flow:

```text
Rows 1..4-ish
├── logo region on left
└── company information on right

Document title
Recipient
Introductory text

Table header
Table data

Footer/signature block
├── location + signing date
├── signatory title
├── signature drawing
└── signatory name
```

Exact row counts may vary with the target module.

Use merged cells deliberately and calculate the table-header row returned by the layout service. Do not hard-code table data to row 1 when branding is enabled.

If Header/Footer is disabled, the layout service should return the original starting row and skip branding/signature output.

---

## 21. Export scope — selected versus all

Follow repository export semantics:

```text
selected IDs empty     -> export all records in the approved export scope
selected IDs non-empty -> export only selected records
```

Do not interpret empty selection as current page only unless the UI explicitly defines that behavior.

On a checkbox-enabled list/detail screen:

- selected IDs must be validated as integers/IDs;
- deduplicate selected IDs;
- apply ownership/authorization/domain scope before selection filtering;
- export only records the operator is allowed to export;
- do not allow IDs to bypass the base query scope.

The export profile controls columns/layout, not authorization or dataset ownership.

---

## 22. Performance

For small bounded exports, normal eager loading may be sufficient.

For potentially large exports:

- avoid unbounded relation N+1 queries;
- eager-load only required relations;
- consider chunking/cursor iteration;
- consider queued export if generation can exceed a normal request budget;
- write temporary files to controlled private storage;
- delete temporary files after download when appropriate;
- do not keep thousands of full model instances merely because the Designer supports many columns.

A configurable column set may allow query optimization, but correctness and stable domain mapping come first.

---

## 23. Security checklist

Before release verify:

- profile queries scoped to authenticated owner;
- JSON library scoped to authenticated owner;
- JSON filenames sanitized against traversal;
- imported JSON schema validated;
- uploaded media validated and size bounded;
- media paths not trusted from imported JSON;
- signature never falls back to another person;
- export dataset authorization enforced server-side;
- selected IDs cannot bypass base scope;
- temporary XLSX stored outside public web root unless intentionally public;
- destructive actions confirmed;
- no secret/token/internal absolute path in JSON or XLSX metadata;
- no raw HTML/JS from profile text injected unsafely into Blade.

---

## 24. Livewire and UI implementation rules

- Use class-based Livewire 3.1 consistent with repository standards.
- Use `wire:model.live` only where immediate synchronization improves interaction, e.g. profile selector or JSON radio selection.
- Use ordinary deferred `wire:model` for form fields that do not require a network request per keystroke.
- Use stable `wire:key` for dynamic rows and active Inspector surfaces.
- Use native form values/IDs instead of complex JS interpolation when possible.
- Keep Alpine for local presentation behavior; do not move server-owned destructive workflows into Alpine.
- Do not query DB from Blade.
- Do not implement profile persistence in Blade.
- Keep modal overlays layered predictably.
- Every mutation with noticeable latency should expose loading/disabled state.
- Inputs must follow `ADMIN_UI_STANDARD.md` visible-border/focus requirements.

---

## 25. Versioned Designer evolution

When a production Designer needs a UI compatibility patch, a wrapper such as `v32` around an older `v31` can be acceptable temporarily to reduce regression risk.

However, new modules should prefer a clean implementation rather than copying legacy wrapper/DOM-reparenting hacks.

Specifically:

- do not copy the Pharma browser-side `Năm` -> `Ngày tháng năm` DOM text patch into a new module; render the correct label directly;
- do not copy DOM `appendChild` media sizing patches when new markup can place the controls correctly from the start;
- do copy the resulting behavior and UX contract;
- preserve versioned schema compatibility separately from temporary view-version structure.

A wrapper is a migration technique, not the desired final architecture for new work.

---

## 26. Testing strategy

A Designer is not complete because a download endpoint returns 200.

Test at least these layers.

### A. Column/catalog contract

Verify:

- expected canonical fields exist;
- value mapping covers configured fields;
- groups/default selection exist;
- selected export behavior is respected.

### B. Profile service

Verify:

- defaults normalize correctly;
- user ownership;
- save/update;
- default profile semantics;
- duplicate collision naming;
- import collision naming;
- delete behavior.

### C. JSON library

Verify:

- per-user directory;
- filename sanitization;
- `.json` enforcement;
- list/read/save/delete;
- malformed JSON handling;
- schema validation.

### D. Media round-trip

Verify:

- logo can recover only within authorized intended scope;
- signature recovery requires exact normalized signer identity;
- no fallback to another person;
- missing media remains safe/blank;
- custom dimensions survive save/import.

### E. Layout/typography

Verify:

- header/footer enabled/disabled;
- logo/signature drawing integration;
- font family;
- colors;
- borders;
- page orientation/paper/scaling/margins;
- number/date/string output.

### F. Livewire regression

Explicitly test that confirmation state and action names do not collide:

```text
pendingConfirmAction property exists
pendingConfirmValue property exists
openConfirmation() exists
executeConfirmedAction() exists
confirmAction property/method collision does NOT exist
```

Verify delete requests capture the exact profile ID/JSON filename.

### G. Formatting-tolerant contract tests

Do not write brittle tests that require Pint's exact whitespace.

Bad:

```php
$this->assertStringContainsString("public array $columnDraft=[];", $source);
```

if the test fails only because Pint changes spaces.

When source-contract tests are necessary, normalize whitespace first or test behavior through PHP/Livewire where practical.

Pint must not make valid behavior tests fail.

### H. Browser/UI verification — mandatory

After automated tests pass, manually verify at least:

```text
Open Designer
Switch profile
Create profile
Save profile
Duplicate profile
Delete profile -> modal -> confirm -> disappears
Edit selected columns
Reorder columns
Edit Inspector values
Save and reopen -> values persist
Save JSON
Open JSON library
Select JSON
Import JSON
Delete JSON -> modal -> confirm -> disappears
Upload/import JSON from machine
Logo preview
Signature preview
Media dimensions
Page setup
Generate XLSX
Open XLSX in Excel/LibreOffice
Check actual columns/order/width/font/header/footer/logo/signature/page setup
```

A static contract test cannot prove a Livewire button actually dispatches in the browser. The Pharma confirmation regression is the canonical example.

---

## 27. Targeted quality gate

Follow the repository collaboration workflow: run only the target module and directly impacted tests unless broader regression is justified.

Before closeout:

```text
[ ] targeted tests PASS
[ ] Pint PASS for changed PHP files
[ ] UI PASS desktop
[ ] UI PASS responsive where applicable
[ ] actual XLSX opened and visually checked
[ ] profile save/reload PASS
[ ] duplicate PASS
[ ] delete profile PASS
[ ] JSON save/list/select/import/delete PASS
[ ] JSON upload/import PASS
[ ] media round-trip PASS
[ ] signature identity safety PASS
[ ] selected/all export scope PASS
[ ] handoff documentation updated
```

Do not run `npm run build` merely because Excel export changed unless frontend assets were actually changed or the module's quality gate requires it. If frontend assets were changed, remember that build failures must be resolved independently of PHP/Livewire test success.

---

## 28. Recommended implementation sequence for another module

An AI implementing this capability in `Modules/<Module>` should proceed in this order:

### Phase 1 — Analyze domain export

Determine:

- export route/screen;
- authorized dataset scope;
- selected/all semantics;
- canonical field catalog;
- required relations;
- default columns;
- document branding requirements;
- whether signatures are needed;
- whether JSON portability is needed.

Do not start by copying Blade.

### Phase 2 — Persistence and service contract

Create:

```text
<Feature>ExportProfile model/table
<Feature>ExportProfileService
```

Implement catalog, groups, defaults, normalization, CRUD, unique naming and schema-versioned JSON payload.

### Phase 3 — JSON library

Create module-owned JSON library service if needed. Make it owner-scoped and path-safe.

### Phase 4 — Excel engine

Implement:

```text
<Feature>ExcelTypography
<Feature>ExcelDocumentLayout
export action/service
```

Map every canonical key to a domain value and support explicit cell types.

### Phase 5 — Designer Livewire

Create state for:

```text
open/section
profiles/profileId/profileName/isDefault
columnOrder/selectedColumns/maps
activeColumnKey/columnDraft
headerFooter/pageSetup
media uploads/paths
JSON modal state
notice state
confirmation state
```

Use collision-free confirmation naming from day one.

### Phase 6 — UI

Build the large Designer workspace with three sections:

```text
Thương hiệu
Cột dữ liệu
Trang in
```

Implement the three-pane column workspace on desktop and responsive fallback.

### Phase 7 — Tests

Add service, JSON, media, export and Livewire interaction contracts. Keep tests Pint-tolerant.

### Phase 8 — UI and XLSX acceptance

Do not close based on tests alone. Perform actual UI actions and open the generated workbook.

---

## 29. Anti-patterns — do not repeat

Do not:

- store Designer configuration only in `localStorage` when profiles must follow the user across machines;
- put user profile JSON in a globally shared unscoped directory;
- use raw filenames as paths;
- import raw Eloquent IDs from JSON;
- embed base64 signatures into portable JSON by default;
- reuse the latest signature when signer identity does not match;
- let a public Livewire property and method share a name;
- depend on Alpine to execute a server-side destructive action when direct `wire:click` is sufficient;
- build deletion around mutable current selection instead of captured target;
- bind an Inspector directly to many maps when isolated draft state is needed;
- create exact-whitespace tests that Pint breaks;
- expose page settings that the XLSX writer ignores;
- let Excel auto-convert identifiers that must remain text;
- assume browser preview equals actual Excel rendering;
- export current page accidentally when empty selection means all approved records;
- copy Pharma business fields/default company identity into unrelated modules;
- copy temporary v3.2 DOM compatibility patches into new clean implementations.

---

## 30. Definition of done

A module's configurable Excel export is complete only when all of the following are true:

1. Export profile is user-owned and persists across sessions/machines.
2. Column catalog is canonical and normalized.
3. User can choose, order and inspect columns.
4. Inspector edits are isolated and persist correctly.
5. Branding/header/footer settings persist.
6. Logo/signature sizing affects the actual workbook.
7. Signature identity cannot cross to another signer.
8. Page/typography settings affect the actual workbook.
9. JSON configuration is versioned, portable and sanitized.
10. Import/duplicate naming cannot violate unique constraints.
11. JSON server library is private and deletable.
12. Destructive confirmation buttons work in the real browser.
13. There is no Livewire property/method name collision.
14. Selected/all export semantics are correct.
15. XLSX preserves strings/numbers/dates correctly.
16. Automated targeted tests pass after Pint formatting.
17. UI acceptance passes.
18. Generated XLSX has been opened and visually checked.
19. Module handoff documents the implementation and verification.

---

## 31. AI execution instruction

When an AI is told something equivalent to:

```text
Áp dụng cấu hình xuất Excel giống Pharma
```

or:

```text
Áp dụng .codex/standards/EXCEL_EXPORT_CONFIGURATION_STANDARD.md
```

it should interpret the request as follows:

1. Read this entire standard first.
2. Read `MODULE_STANDARD.md` and `ADMIN_UI_STANDARD.md`.
3. Inspect the target module's existing export, permissions, models and UI.
4. Inspect the Pharma reference files listed in section 2 for current implementation details.
5. Reuse behavior/architecture, not Pharma domain names.
6. Produce one comprehensive implementation plan if approval is required by the collaboration workflow.
7. Implement profile persistence, Designer UI, JSON library, media/layout, export mapping and tests as one coherent capability.
8. Preserve existing target-module compatibility unless the approved plan says otherwise.
9. Run only targeted/directly impacted tests.
10. Require UI PASS and actual workbook verification before closeout.
11. Update `docs/modules/<Module>/COLLABORATION_HANDOFF.md` before PR/merge.

The goal is that a future AI can reproduce the approved Excel Designer experience without needing the historical Pharma conversation that originally produced it.
