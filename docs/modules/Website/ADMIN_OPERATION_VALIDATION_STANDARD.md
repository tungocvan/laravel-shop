# Admin Operation Validation Standard

## Purpose

This document defines the required validation and feedback flow for state-changing or serialization actions in Website Admin screens.

It complements `ADMIN_UI_INPUT_STANDARD.md`: input styling alone is not sufficient; actions must fail safely and must always tell the administrator what happened.

## Required flow

Every Save / Update / Export / Import action must follow this order:

```text
User action
    ↓
Precondition validation
    ↓
Payload validation / sanitization
    ↓
Confirmation modal when the action mutates persisted state
    ↓
Execute service operation
    ↓
Success modal OR failure modal
    ↓
Inline field error when a specific field caused the failure
```

## Save / Update

Before Save or Update:

1. Validate every required field and enum.
2. Reject unsafe or malformed values before persistence.
3. Show a confirmation modal before mutating persisted settings.
4. Persist only after validation passes.
5. On success, show a success feedback modal.
6. On validation failure, preserve inline validation messages and show a failure feedback modal.
7. On unexpected persistence failure, keep the previous persisted state, report/log the exception, and show a friendly failure modal instead of an exception page.

## Export

Export must never assume a resource has been selected.

Required checks:

1. A saved theme/resource must be selected.
2. The selected resource must still exist.
3. The payload must pass the canonical schema validator before serialization.
4. On success, place the serialized JSON in the export field and show a success modal.
5. On failure, show an inline error beside the selector when possible and show a failure modal.

Example invalid state:

```text
selectedTheme = ''
→ reject
→ "Vui lòng chọn một theme đã lưu trước khi export."
→ do not call the export service
```

## Import

Import is treated as untrusted input.

Required checks:

1. Input must not be blank.
2. Input must be valid JSON.
3. JSON root must be an object/associative array.
4. Schema identifier must match the expected schema.
5. Version must be supported.
6. Required top-level fields must exist.
7. Required nested payload groups must exist.
8. Unknown/disallowed fields must be rejected where the schema defines a whitelist.
9. Values must pass the same sanitizer/resolver used by runtime.
10. Nothing is persisted until the entire payload is valid.
11. Failure must appear inline at the JSON field and in the feedback modal.
12. Success must show a success feedback modal.

For Website Design Themes the required contract currently includes:

```text
schema = flexbiz.website-design-theme
version = 1
name
design.typography
design.colors
design.layout
```

## Confirmation vs feedback modal

These are separate concepts:

- **Confirmation modal**: appears before a mutating operation such as Save, Update, Delete, Import, Apply-to-state, Rename, or Restore Defaults.
- **Feedback modal**: appears after the operation and communicates success or failure.

Read-only actions such as Export do not require a confirmation modal, but they still require precondition validation and a success/failure feedback modal.

## Error handling

Expected validation problems must never produce a framework exception page.

Known domain validation errors should be converted into:

```text
inline field error
+
operation feedback modal
```

Unexpected exceptions should be reported/logged internally and converted to a generic administrator-facing failure message. Do not expose stack traces, database details, filesystem paths, or internal exception messages unless they are explicitly classified as safe domain validation messages.

## Website Settings implementation

`/admin/website/settings` currently applies this contract to:

- Save Website settings
- Save design theme
- Apply design theme
- Update design theme
- Rename design theme
- Delete design theme
- Restore default design themes
- Export design theme JSON
- Import design theme JSON

The shared browser event for operation results is:

```text
operation-feedback
```

with payload:

```text
type: success | error
title: short result title
message: administrator-facing explanation
```

## Regression requirement

Any future Website Admin feature adding Save / Update / Export / Import must include tests covering at least:

- missing required precondition
- invalid payload
- successful operation
- friendly failure behavior
- no raw exception page for expected validation failures
