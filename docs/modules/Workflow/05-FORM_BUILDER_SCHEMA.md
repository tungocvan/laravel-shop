# Dynamic Form Builder Schema

## 1. Goals

The form builder must let authorized designers create usable internal request forms without creating database columns or executable code. A published schema is immutable, self-describing, server-validatable, and pinned to its workflow version.

## 2. Top-level schema

```json
{
  "schema_version": 1,
  "title": "Purchase request",
  "description": "Internal purchase approval",
  "settings": {
    "draft_autosave": true,
    "offline_draft_mode": "non_sensitive_fields",
    "allow_attachments": true,
    "submit_confirmation": true
  },
  "sections": [
    {
      "key": "general",
      "title": "General information",
      "description": null,
      "columns": 2,
      "fields": []
    }
  ]
}
```

Unknown keys are either rejected or preserved only in a clearly non-executable extension namespace. Schema JSON has configured depth, field-count, string-length, option-count, and total-size limits.

## 3. Field contract

```json
{
  "key": "amount",
  "type": "currency",
  "label": "Amount",
  "help": null,
  "required": true,
  "default": null,
  "validation": {"min": 0, "max": 1000000000, "scale": 2},
  "visibility": null,
  "readonly": false,
  "sensitive": false,
  "offline_storage": "allow",
  "searchable": true,
  "reportable": true,
  "exportable": true,
  "layout": {"column_span": 1}
}
```

Field key rules:

- lower snake_case; unique per schema
- stable after publication
- cannot use reserved keys such as status, requester, actor, permissions, audit, files, instance, definition, version, or internal IDs
- not interpreted as a database column or arbitrary JSON path
- `offline_storage` is `deny` for sensitive fields and attachments; designers cannot weaken that rule

## 4. Initial field catalog

| Type | Stored representation | Required validation |
|---|---|---|
| text | string/null | length, pattern allowlist, normalization |
| textarea | string/null | length, newline policy |
| integer | integer/null | min/max |
| decimal | canonical decimal string/null | precision/scale/min/max |
| currency | object or canonical decimal + currency policy | precision/scale/currency |
| date | `YYYY-MM-DD`/null | range |
| datetime | ISO-8601 normalized to UTC/null | range/timezone |
| boolean | boolean/null | strict boolean |
| select | allowed scalar/null | option membership |
| multiselect | unique scalar array | item/count bounds |
| user | canonical user ID/null | shell identity existence/visibility |
| role | canonical role ID/code/null | shell role existence/visibility |
| attachment | managed attachment references | count/type/size/scan policy |
| computed | server-produced scalar | expression registry/type |

HTML/raw script fields are not supported. Rich text, if later added, requires explicit sanitization and rendering contracts.

## 5. Options and catalogs

Static options are embedded in the published schema with stable values and translatable labels. Dynamic options may use only registered shell-owned providers. Browser input cannot specify a model class, table, query, URL, service, method, or SQL.

Option snapshots must define whether submitted values are validated against current provider state or a publication snapshot. Default for auditable choices is stable ID plus captured label snapshot in request metadata.

## 6. Visibility and requirement conditions

Visibility and conditional requirement use the same safe typed DSL as routing, restricted to current form fields and safe requester context. Hidden fields follow an explicit policy:

- clear_on_hide: remove value server-side
- retain_hidden: retain but do not display; allowed only when approved

Client-side conditions are convenience only. The server recalculates visibility, required rules, and allowed values before save/submit.

## 7. Computed fields

Computed fields use allowlisted operators such as numeric sum/multiply, date difference, concatenation with size limits, coalesce, and conditional selection. They cannot call code or query data. Computed values are recalculated server-side and cannot be trusted from request payload.

Money calculations use decimal arithmetic and explicit rounding. Formula dependency cycles are rejected at publication.

## 8. Payload handling

- Draft saves validate shape and supplied fields but may allow missing required submission values.
- Submission performs full schema validation and canonical normalization.
- Persist canonical payload, payload checksum, and optional presentation snapshot.
- Search/report fields are allowlisted; do not build arbitrary user-selected JSON queries.
- Sensitive fields are redacted from ordinary audit metadata, logs, notifications, and exports.
- Offline drafts store only allowlisted non-sensitive scalar/object values in a versioned, per-user browser record. Attachment binaries, upload tokens, computed trust results, and fields marked `sensitive` or `offline_storage: deny` are never persisted offline.
- `offline_draft_mode` supports `disabled` and `non_sensitive_fields` in version 1. A future all-fields mode requires a separate browser-storage threat review and approval.
- Changes after return create revision evidence; prior submitted payload/checksum remains recoverable in audit/revision history.

## 9. Attachment behavior

- Upload to private temporary storage with opaque server-generated key.
- Validate size, extension, detected MIME, count, checksum, and authorization.
- Promote/associate only after successful command transaction using a safe staged-file workflow.
- Never use original filename as storage path.
- Download through authorized response with safe content disposition.
- Deletion/retention/scan events are audited.

## 10. Builder UI

- Section and field library on the left; selected field settings and preview in the main workspace.
- Drag/drop changes presentation order only and is persisted as explicit keys/sort order.
- Keyboard-accessible move controls are required.
- Validation panel distinguishes errors from warnings and links to offending field.
- Desktop preview and mobile preview modes.
- Offline-draft preview identifies fields that will not be stored locally and warns when reconnecting is required to complete the form.
- Test payload validation without creating production requests.
- Publish confirmation shows schema/graph diff and blocking validation status.

## 11. Example

```json
{
  "schema_version": 1,
  "title": "Expense request",
  "sections": [
    {
      "key": "expense",
      "title": "Expense",
      "columns": 2,
      "fields": [
        {"key": "subject", "type": "text", "label": "Subject", "required": true, "validation": {"max_length": 160}},
        {"key": "amount", "type": "currency", "label": "Amount", "required": true, "validation": {"min": 0, "scale": 2}},
        {"key": "urgent", "type": "boolean", "label": "Urgent", "required": false},
        {"key": "justification", "type": "textarea", "label": "Justification", "required": true, "validation": {"max_length": 4000}},
        {"key": "evidence", "type": "attachment", "label": "Evidence", "required": false, "validation": {"max_files": 5, "max_size_mb": 10}}
      ]
    }
  ]
}
```
