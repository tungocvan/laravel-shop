# Request v1

Status: Approved analysis specification  
Module: `Request`  
Module type: `domain`  
Target: Laravel 12, PHP 8.3, Livewire 3  
Specification version: `1.0.0`

This directory is the authoritative, implementation-ready specification for an internal Request module inspired by the useful request-and-approval experience of Base Request. It is intentionally smaller and safer than a general workflow engine: employees create internal requests from versioned forms, and authorized people approve them through an ordered approval pipeline.

Request v1 is independent. It depends only on system Shell Modules and owns no integration with other business-domain modules. The broader Workflow v4 specification is retained for future architecture work but is deferred by `ADR-001-REQUEST-FIRST-WORKFLOW-DEFERRED.md`.

The first experience is mobile-first for request creation, personal requests, inbox, and decisions; request-type design is tablet/desktop-first. PWA behavior is online-first: the shell and selected sanitized reads may be cached and non-sensitive drafts may be stored locally, but every authoritative mutation requires an authenticated online connection.

## Read order

1. `REQUIREMENTS.md`
2. `ADR-001-REQUEST-FIRST-WORKFLOW-DEFERRED.md`
3. `00-REQUEST_MASTER_SPEC_V1.md`
4. `01-DOMAIN_MODEL_AND_INVARIANTS.md`
5. `02-DATABASE_ERD_AND_SCHEMA.md`
6. `03-REQUEST_TYPE_FORM_AND_VERSIONING.md`
7. `04-APPROVAL_PIPELINE_AND_DECISIONS.md`
8. `05-ACTOR_RESOLUTION_RBAC.md`
9. `06-UI_UX_RESPONSIVE_PWA.md`
10. `07-API_EVENTS_NOTIFICATIONS.md`
11. `08-SECURITY_AUDIT_FILES.md`
12. `09-REPORTING_EXPORT_AND_TEMPLATES.md`
13. `10-TEST_AND_ACCEPTANCE.md`
14. `11-AI_IMPLEMENTATION_CONTRACT.md`
15. `12-TRACEABILITY_MATRIX.md`
16. `13-REQUEST_TEMPLATE_CATALOG.md`
17. `CREATE_PLAN.md` — approved-spec-to-code execution plan and implementation stop gate

## Authority and conflict order

When instructions conflict, use this order:

1. Current repository source and runtime architecture.
2. `Modules/ModuleServiceProvider.php`, module-state infrastructure, and repository bootstrap documents.
3. `docs/modules/Request/REQUIREMENTS.md` and the accepted ADR.
4. The numbered Request v1 documents.
5. The earlier Workflow v4 documents and the supplied Workflow v3 archive.
6. The shared ChatGPT conversation used as product inspiration.

Repository reality wins over generic examples. Never introduce `nwidart/laravel-modules`, `module.json`, a second module registry, a second PWA shell/service worker, arbitrary expression execution, public sensitive exports, or direct dependencies on domain modules.

## Approval boundary

The analysis documents complete `/analyze-new-module Request`. `/create-module Request` has now produced `CREATE_PLAN.md`; application code is still not authorized until that plan receives explicit approval.

```text
Review and approve docs/modules/Request/CREATE_PLAN.md
```

After approval, Codex must implement the ordered vertical slices and gates in `CREATE_PLAN.md`. Do not run `/create-module Workflow` while the accepted ADR remains active.
