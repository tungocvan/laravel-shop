# Workflow Enterprise v4.0 Ultimate

Status: **DEFERRED — Request-first**  
Module: `Workflow`  
Module type: `domain`  
Target: Laravel 12, PHP 8.3, Livewire 3  
Specification version: `4.0.0`

This directory preserves the approved future analysis for the Workflow module. It upgrades the supplied v3.0 outline into a deterministic, versioned, auditable workflow engine, but it is **not currently authorized for implementation**.

`docs/modules/Request/ADR-001-REQUEST-FIRST-WORKFLOW-DEFERRED.md` assigns the internal request-and-approval domain to the smaller independent Request v1 module and defers Workflow. While that ADR is active, do not create Workflow runtime code, tables, routes, permissions, queues, or UI, and do not use this specification to duplicate Request ownership.

The module is inspired by enterprise workflow capabilities commonly found in SAP/Odoo-class systems, but it does not claim product or protocol parity. Version 4.0 deliberately avoids full BPMN 2.0, multi-tenancy, PKI signatures, and direct coupling to business-domain modules.

Approved experience profile: requester and approver journeys are mobile-first; the definition designer is tablet/desktop-first. The PWA is online-first: it may cache the application shell and selected sanitized read models and may keep non-sensitive local drafts, but submission, approval, rejection, return, claim, publication, upload, and other business mutations require a live authenticated connection.

## Read order

1. `REQUIREMENTS.md`
2. `00-WORKFLOW_MASTER_SPEC_V4.md`
3. `01-DOMAIN_MODEL_AND_INVARIANTS.md`
4. `02-DATABASE_ERD_AND_SCHEMA.md`
5. `03-WORKFLOW_DEFINITION_AND_VERSIONING.md`
6. `04-EXECUTION_AND_APPROVAL_ENGINE.md`
7. `05-FORM_BUILDER_SCHEMA.md`
8. `06-ACTOR_RESOLUTION_RBAC.md`
9. `07-UI_UX_SCREEN_SPEC.md`
10. `08-API_EVENTS_AND_INTEGRATION.md`
11. `09-SECURITY_AUDIT_AND_COMPLIANCE.md`
12. `10-OPERATIONS_SLA_NOTIFICATIONS.md`
13. `11-TEST_AND_ACCEPTANCE.md`
14. `12-AI_IMPLEMENTATION_CONTRACT.md`
15. `13-TRACEABILITY_MATRIX.md`

## Authority and conflict order

When instructions conflict, use this order:

1. Current repository source and runtime architecture.
2. `Modules/ModuleServiceProvider.php`, module-state infrastructure, and repository bootstrap documents.
3. `docs/modules/Workflow/REQUIREMENTS.md`.
4. The numbered Workflow v4.0 documents.
5. The original v3.0 archive.

Never introduce `nwidart/laravel-modules`, `module.json`, a second module registry, arbitrary expression execution, or direct dependencies on domain modules.

## Deferral and approval boundary

These documents remain useful future analysis, but they no longer pass the create-module gate. The current implementation path is:

```text
/create-module Request
```

That task must create and obtain approval for `docs/modules/Request/CREATE_PLAN.md` before implementation begins. `/create-module Workflow` is prohibited until a superseding ADR defines ownership, coexistence or migration, and receives explicit approval.
