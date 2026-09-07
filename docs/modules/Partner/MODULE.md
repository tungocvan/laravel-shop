# Partner Module Contract

## Purpose

`Modules\Partner` is the canonical ERP owner for partners, businesses, healthcare facilities, suppliers, customers, and other external organizations represented inside the ERP.

The module owns canonical Partner records and their ERP-facing classification. External sources are enrichment/reference sources and do not become canonical merely because data was retrieved from them.

## Core principles

1. **Search is not Sync.** External lookup is read-only until an authorized user explicitly reviews and confirms a sync operation.
2. **External source is not canonical ERP data.** External values must not blindly overwrite local values.
3. **Human review before mutation.** Ambiguous matches and conflicting values require explicit operator choice.
4. **Provenance is preserved.** External identity, source URL, timestamps, source snapshot and related metadata belong to `PartnerSourceReference` rather than a parallel provenance system.
5. **Idempotent sync.** Repeating a sync for the same external identity and unchanged data must not create duplicate Partner or source-reference records.
6. **No automatic bulk enrichment in the initial phase.** No crawler, scheduled scraping, CAPTCHA bypass, OCR CAPTCHA, or background bulk sync.

## Canonical ownership

### `Partner`

Canonical ERP record. Owns ERP-facing fields such as:

- tax code under the current schema contract;
- canonical/display name;
- legal type;
- supplier/customer roles;
- phone/email/contact person;
- canonical ERP address and province code;
- record acquisition source (`manual`, `import`, `system`);
- ERP operational status;
- internal note.

External business/legal status must not be mapped blindly to ERP operational `status`.

### `PartnerSourceReference`

Canonical owner for external-source provenance and identity association. Reuse the existing `(source, external_id)` uniqueness contract. Source-specific metadata may include source URL, lookup/sync timestamps, match information, source snapshot and snapshot hash when appropriate.

An existing external identity attached to one Partner must never be silently reassigned to another Partner.

### `App\Services\MasothueLookupService`

Source-specific read adapter for MaSoThue. It owns HTTP retrieval and HTML parsing only. It must not create/update Partner records and must not contain Partner-domain synchronization rules.

`Modules\Partner` may reuse its public search/detail capabilities and normalize results into Partner-domain data objects.

## Partner workspaces

The module provides these canonical admin capabilities:

- Partner Dashboard;
- Partner Management;
- Business Lookup;
- reviewed conflict-aware Partner synchronization;
- source provenance inspection where useful;
- existing import/export behavior.

Target route namespace is `/admin/partners/...`. Legacy Partner routes may remain temporarily as compatibility adapters until caller/reachability proof permits removal.

## Lookup and synchronization contract

Lookup flow:

`human-triggered search -> candidate selection -> detail retrieval -> Partner matching -> diff preview -> explicit sync`

No database mutation is allowed during lookup/candidate/detail retrieval.

Sync comparison states:

- `new_value`;
- `same`;
- `local_differs`;
- `missing_locally`;
- `source_missing`.

Rules:

- same values cause no canonical mutation;
- missing-local values may be offered as safe enrichment candidates;
- conflicting non-empty local values are not selected for overwrite by default;
- source-missing values never clear local values automatically;
- creation must run duplicate checks before insert;
- MST is an important identity signal, while the current database uniqueness constraint remains authoritative until a separately approved schema review changes it.

## Query and export contract

Partner list filtering has one canonical query owner reused by list, export and dashboard deep links where applicable.

Export semantics remain:

- selected IDs present -> export selected records;
- no selected IDs -> export all records matching the current approved filter scope;
- never silently limit the latter to the current pagination page.

Production-capable list pagination is bounded and follows `.codex/standards/ADMIN_UI_STANDARD.md`; no unbounded `All` page-size option.

## Authorization contract

Partner actions require server-side authorization. Lookup and synchronization are distinct capabilities: permission to inspect external data does not imply permission to mutate canonical Partner data.

Implementation must follow the repository's canonical permission naming/registration conventions while covering dashboard/view/create/update/delete/import/export/lookup/sync capabilities as applicable.

## UI contract

All admin workspaces follow `.codex/standards/ADMIN_UI_STANDARD.md`, including visible input boundaries, filter reset behavior, responsive tables, bounded canonical pagination, loading/empty/error states, mutation disabled/loading states, clear action hierarchy, and a route back to Partner Dashboard from child workspaces.

## Deferred scope

Not part of the initial implementation:

- automated or scheduled MaSoThue crawling;
- CAPTCHA bypass/OCR;
- automatic merge of duplicate Partners;
- bulk source synchronization;
- AI/fuzzy automatic deduplication;
- treating MaSoThue as legal authority or automatic verification authority;
- removal of the current tax-code uniqueness constraint;
- promoting every source-only field into Partner columns;
- multi-source arbitration.
