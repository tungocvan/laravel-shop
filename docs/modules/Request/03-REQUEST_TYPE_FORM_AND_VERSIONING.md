# Request Type, Form, and Versioning

## 1. Request catalog model

Groups organize discovery; types carry stable business meaning; versions carry behavior. A group or type can be archived/retired without deleting history. Catalog visibility is the intersection of active availability, version audience, module state, and record policy.

Required type metadata:

- Stable code, localized name, concise summary, detailed guidance.
- Group, icon/color token from allowlisted design tokens, display order.
- Availability window and active/retired state.
- Expected completion/help copy; no unenforced SLA promise in v1.
- Audience rules for discovery/creation.
- Data classification and offline-draft eligibility policy.

## 2. Draft and publication workflow

1. Designer creates or clones a draft.
2. Designer edits metadata, audience, schema, presentation, policies, and stages.
3. Validator returns structured errors/warnings with section, path, code, and remediation.
4. Publisher reviews a diff from current published version.
5. Publisher confirms and publication repeats validation under lock.
6. Canonical JSON is checksummed and the version becomes immutable atomically.

A browser preview is never publication evidence. The server's canonical validation result is authoritative.

## 3. Form schema envelope

Illustrative shape; exact JSON Schema/OpenAPI artifacts belong in implementation:

```json
{
  "schema_version": 1,
  "sections": [
    {
      "key": "request_details",
      "label": "Request details",
      "fields": [
        {
          "key": "reason",
          "type": "textarea",
          "label": "Reason",
          "required": true,
          "validation": {"min_length": 10, "max_length": 2000},
          "classification": "internal",
          "offline_draft": true
        }
      ]
    }
  ]
}
```

Unknown schema versions, field types, validator keys, classification values, or expression operators are rejected. Schema documents have hard byte/depth/count bounds.

## 4. Field contract

Every field has:

- stable `key` using a constrained identifier grammar;
- type, localized label/help/placeholder;
- required/read-only state;
- typed default, validation, and option source;
- classification (`public_internal`, `internal`, `confidential` or repository-approved equivalent);
- offline draft eligibility explicitly derived from type/classification, never from browser choice;
- visibility rule and safe display formatting;
- optional export/report flag subject to authorization and classification.

Published field keys cannot be repurposed for a different semantic/type. A new semantic requires a new key in a new version.

## 5. Initial field types

| Type | Canonical payload | Critical rules |
|---|---|---|
| `text` | string | trim/canonical Unicode, length, pattern from safe precompiled set |
| `textarea` | string | bounded length; approved plain/limited markdown rendering only |
| `integer` | integer | min/max; no locale-formatted string persisted |
| `decimal` | canonical decimal string | scale/precision; never binary float |
| `currency` | `{amount, currency}` | decimal/minor-safe amount and allowlisted ISO-like code |
| `date` | `YYYY-MM-DD` | timezone-free calendar value |
| `datetime` | ISO timestamp | normalized to UTC with original zone only if required |
| `boolean` | boolean | no truthy string coercion |
| `select` | stable option key | option exists in pinned schema |
| `multiselect` | unique option-key array | bounded count and canonical order policy |
| `user` | stable user ID/public ID | active/visible Shell User validated server-side |
| `role` | stable role ID/public ID | allowlisted visible Shell Role validated server-side |
| `attachment` | attachment public-ID array | binary stored privately outside payload; ownership validated |
| `computed_display` | server-generated display value | read-only, no business mutation/expression execution |

Repeating groups, tables, rich text, external data lookup, formula builder, and domain entity selectors are out of scope unless separately approved.

## 6. Validation rules

- Both draft save and submit validate shape/limits; submit applies all required and business rules.
- Server ignores or rejects browser-supplied read-only/system fields.
- Required hidden fields follow a declared policy: either the visibility condition makes them inapplicable and the server strips them, or submission fails. The behavior is fixed per validator and covered by tests.
- Option labels are display metadata; stable keys are persisted.
- Cross-field rules use a small typed allowlist for presence/comparison/date ordering only. They cannot call services, SQL, models, HTTP, filesystem, environment, or code.
- User/role fields revalidate authorization and current existence at submit.
- Normalization precedes checksum, storage, resolver use, audit delta, and export.
- Validation errors use stable code/path/message-key and a localized display message.

## 7. Visibility and computation grammar

Allowed v1 visibility operands are fields in the same schema and safe contextual constants such as current operation (`draft|submit|view`). Allowed operators are explicit: equality/inequality, presence/absence, boolean `all|any|not`, numeric/date comparison, and membership against pinned options.

No user-authored calculation is required in v1. `computed_display` values are produced by registered Request-owned calculators chosen from an allowlisted key, or omitted. Browser-submitted computed values are discarded.

## 8. Audience rules

- Audience entries use only fixed Shell users/roles.
- No audience means deny creation unless an explicit `all_authenticated_users` policy is represented by a dedicated safe flag.
- Discover and create are rechecked server-side; hiding a catalog card is not authorization.
- Existing requests remain visible under record policy even after the type audience changes.
- Role membership is evaluated for current creation; the request stores requester/type snapshots for history.

## 9. Version pinning and compatibility

- A new draft may add/remove/reorder fields and stages, subject to validation.
- Existing draft requests created before publication changes must be handled explicitly on open/submit: if never submitted, the product may offer a reviewed migration to current version or require duplication. It must never silently reinterpret payload.
- First submission pins a version permanently for that internal request, including later return/resubmit.
- Historical rendering uses the pinned schema and display snapshot.
- Readers support known older schema versions through Request-owned upcasters that do not rewrite historical checksums.

`CREATE_PLAN.md` must settle pre-submission draft pin behavior as an explicit UI/service rule and test it. Recommended v1: pin the version at draft creation; if retired before submit, permit submit only under a documented grace policy, otherwise offer duplication into a current version.

## 10. Definition package

A portable Request Type package may include sanitized group/type metadata, version schema, stages, policies, translations, and checksum manifest. It must not include environment IDs for fixed users/roles as blindly importable values, request instances, decisions, attachments, secrets, emails, or private payloads.

Import is administration-only and follows:

1. Upload privately with size/type limits.
2. Parse without executable content or unsafe archive paths.
3. Validate schema version/checksums.
4. Map unresolved users/roles explicitly.
5. Show dry-run errors, warnings, and diff.
6. Create a draft only after confirmation.
7. Never publish automatically.
8. Audit upload, validation, mapping, draft creation, and publication separately.

Live request import is not supported.

## 11. Builder UX requirements

- Left navigation for metadata/form/approval/audience/policies; central editor; validation panel/preview.
- Reordering is keyboard accessible and has explicit move controls in addition to drag-and-drop.
- Autosave shows saved/saving/conflict/error state and never loses a newer server revision.
- Field configuration uses visible bordered controls and concise inline help.
- Preview supports phone/tablet/desktop widths but is clearly labeled non-authoritative.
- Publish shows blocking errors, warnings, exact version diff, and irreversible immutability notice.
- Topology editing is not shown: approval is an ordered stage list with clear mode and resolved-source summary.
