# ClientPortal Module — Collaboration Handoff

## Current delivery — ClientPortal Feature Page Content & PWA AI Workflow

- Last updated: 2026-09-29
- Active branch: `feat/clientportal-feature-page-content`
- Base branch: `main`
- Base checkpoint: `9027f08542c964028088cb340519d6f36fbe7c89`
- Branch comparison before this handoff commit: **ahead 15 / behind 0**
- Status: **IMPLEMENTED — FOCUSED PASS — CLIENTAPPS REGRESSION PASS — REAL UI PASS — READY FOR PR**

### Objective

Make routable PWA feature hero/page copy manageable from `/admin/client-apps` without making routes, permissions or business logic configurable, connect the first runtime consumer (Pharma Commercial Workspace), and establish a mandatory AI workflow gate for future ClientPortal/PWA work.

### Delivered contract

Generic feature presentation now supports:

```text
eyebrow
page_title
page_description
```

Resolution path:

```text
application manifest defaults
    -> ApplicationRegistry
    -> ClientPortalSettingsService::featurePresentation()
    -> controller
    -> Blade
```

Admin editing is exposed through:

```text
/admin/client-apps
-> Giao diện Application & Feature
-> Nội dung trang PWA
```

The Admin form edits presentation text only. Route names, permission names, source-module ownership and business behavior remain source-controlled.

### Pharma Commercial runtime integration

The Pharma Commercial feature manifest defines the current defaults:

```text
Commercial Workspace
Công việc bệnh viện của tôi
Chọn Chủ đầu tư / kết quả trúng thầu để xem đúng phạm vi bệnh viện được phân công.
```

`PharmaApplicationController::commercial()` resolves the Commercial feature and passes its resolved presentation to the view. The Commercial Blade consumes managed eyebrow/title/description instead of duplicating those configurable literals.

When a bid-result scope is selected, the existing contextual `Đang xem ...` description remains dynamic; the managed description is the initial/no-selected-result page description.

No migration is required. Existing `client_portal_settings` storage and the generic feature presentation group are reused.

### PWA AI/workflow governance

Added:

```text
docs/modules/ClientPortal/PWA_AI_WORKFLOW.md
```

and linked it as a mandatory gate from:

```text
docs/GITHUB_COLLABORATION_WORKFLOW.md
```

Any new chat/task touching `/my-apps`, `/apps/*`, `Modules/ClientPortal`, installed PWA/mobile Client UX or `/admin/client-apps` must read the PWA AI workflow before code analysis/implementation. The gate defines mandatory ClientPortal docs, conditional file/export/debug docs, architecture/security boundaries, managed page-content rules and test/UI/PR gates.

For PWA file download/open/share/export work, `docs/PWA_EXTERNAL_FILE_HANDOFF.md` remains an additional mandatory gate.

### Automated coverage added/updated

`ClientPortalPwaSettingsTest` covers:

- Commercial feature presentation defaults;
- persisted Admin override resolution;
- updater identity persistence;
- Admin/controller validation for safe page-copy fields;
- no editable route/permission fields in the presentation form.

`PharmaCommercialCapabilityTest` guards:

- controller resolution through `ClientPortalSettingsService::featurePresentation()`;
- managed eyebrow/title/description consumption in the Commercial view.

### Validation evidence

Focused ClientPortal PWA settings + Pharma Commercial capability tests: **PASS**.

Latest ClientApps regression reported by the user:

```text
Tests: 134 passed (1454 assertions)
Duration: 24.37s
```

Real UI acceptance: **PASS**.

Verified through `/admin/client-apps` that Commercial eyebrow/title/description can be edited and persisted, and the Pharma PWA Commercial Workspace renders the managed values while preserving the existing scoped workflow.

The later documentation/workflow additions do not alter rendered UI. Automated and manual UI gates required for this delivery are satisfied.

### Scope / known boundaries

- only Pharma Commercial is connected as the first runtime consumer in this delivery; the generic Admin/schema contract is reusable by later PWA features;
- no route, permission or source-module contract is made Admin-editable;
- no migration/schema change;
- no file download/open behavior changed, therefore PWA external-file platform acceptance is **NOT APPLICABLE** to this batch;
- focused tests, ClientApps regression and real UI acceptance are PASS;
- PR may now be created for review;
- merge still requires explicit user approval.

### Next step

Create/review the PR into `main`. Do not merge until the user explicitly approves the reviewed PR.


## Current delivery — Pharma PWA Medicine Catalog

- Last updated: 2026-09-27
- Active branch: `feat/clientportal-pharma-products`
- Base branch: `main`
- Branch comparison before this handoff commit: **ahead 44 / behind 0**
- Status: **IMPLEMENTED — FOCUSED TEST PASS — DESKTOP UI PASS — READY FOR PR**

### Objective

Deliver the first Pharma business capability in ClientPortal as a read-only, user-oriented Medicine Catalog while keeping Medicine Master ownership and business queries in `Modules/Pharma`.

### Delivered capability

- `GET /apps/pharma/products` and read-only product detail;
- full Medicine Master catalog for Users authorized with `client.pharma.products.view`;
- live-search UX with 350 ms debounce and 25/50/100 pagination;
- circular-group selector plus default ordering by circular group, circular order, medicine name and SKU;
- status filters for awarded products, HSSP and supplier pricing;
- catalog aggregate counts and SKU total;
- compact business-status language: blue = Trúng thầu, emerald = HSSP, amber = Giá NCC;
- desktop/mobile catalog presentation with Nhóm and Giá kê khai;
- declared price uses variant value first and Medicine fallback;
- detail workspace surfaces basic product information, product dossier, recent bid-award information and supplier information when available;
- supplier commercial pricing is separately protected by `client.pharma.products.supplier-pricing`.

### Canonical ownership / security boundary

```text
Modules/Pharma
  MedicineCatalog + MedicineCatalogItem
  owns Medicine/Variant query and business-data composition
  owns catalog grouping, counts and Product Intelligence payload

Modules/ClientPortal/Applications/Pharma
  owns auth:web + client permission boundary
  owns PWA routes/controller orchestration
  owns responsive catalog/detail presentation
```

ClientPortal does not query Pharma tables directly for this capability and does not reuse Admin controllers, Blade, Livewire or `auth:admin`.

Supplier pricing is treated as sensitive company data. A User without `client.pharma.products.supplier-pricing` does not receive supplier price values, does not see the Giá NCC filter/badge, and cannot access the supplier-priced filter by manually crafting the query string.

### Validation evidence

User reported the focused capability test as **PASS**:

```text
php artisan test tests/Feature/ClientApps/PharmaProductsCapabilityTest.php
PASS
```

Manual Desktop UI acceptance: **PASS**.

Accepted UX includes live search, pagination, circular-group filtering, colored business filters/badges, SKU count, Nhóm, Giá kê khai, detail Product Intelligence and aligned filter toolbar.

Per project testing policy, broad ClientPortal/full-project regression is intentionally deferred until merge-to-main validation.

### Scope deliberately excluded

- no Medicine Master create/edit/delete;
- no Medicine import or master verification;
- no HSSP mutation;
- no bid-award mutation;
- no supplier-tracking mutation;
- no Admin Pharma permission reuse;
- no PWA file/export behavior in this capability.

### PR gate

Review the final branch diff and open a PR into `main`. Do not merge automatically. After merge, run the agreed wider main-branch validation before starting the next Pharma PWA capability.

## Current delivery — Pharma PWA Foundation

- Last updated: 2026-09-27
- Active branch: `feat/clientportal-pharma-pwa-foundation`
- Base branch: `main`
- Branch comparison at closeout: **ahead 8 / behind 0** before this handoff commit
- Status: **IMPLEMENTED — FOCUSED/CLIENTAPPS REGRESSION PASS — DESKTOP UI PASS — READY FOR FINAL PR GATE**

### Objective

Establish a separate ClientPortal PWA entry point for Pharma Users without moving Pharma domain ownership into ClientPortal and without reusing Admin authentication, permissions, controllers, Blade or Livewire presentation.

### Delivered foundation

- application discovery through `Modules/ClientPortal/Applications/Pharma/manifest.php` with `source_module = Pharma`;
- launcher/application contract for `/my-apps` → Pharma → `/apps/pharma`;
- route boundary: `web`, `auth:web`, `client.application:pharma`, `client.feature:pharma,overview`;
- ClientPortal permissions for overview, products, price lists, bid awards, commercial, inventory receipts/issues and commissions;
- only Overview is routable in this foundation; future capabilities are visible only when authorized and remain marked `Sắp triển khai`;
- dedicated Pharma PWA controller and responsive ClientPortal application view;
- route availability is resolved in the controller, not by Facade calls inside Blade;
- no Pharma migration, schema rewrite, Admin route change or business-data mutation.

### Canonical ownership boundary

```text
Modules/Pharma
  owns Pharma models/schema/business rules
  owns canonical domain services/query contracts
  owns Admin-only master/configuration/destructive operations

Modules/ClientPortal/Applications/Pharma
  owns PWA routes and presentation
  owns web-guard/client application authorization
  owns client-safe orchestration
  consumes Pharma service/query contracts capability by capability
```

Dependency direction remains `ClientPortal Pharma adapter -> Modules/Pharma`; Pharma must not depend on ClientPortal.

### Validation evidence

Focused Foundation test: **PASS**.

ClientPortal application regression reported by user:

```text
Tests: 120 passed (805 assertions)
Duration: 8.21s
```

Manual Desktop UI smoke for `/apps/pharma`: **PASS**. The page renders the ClientPortal PWA shell, Overview navigation and authorized capability cards without the previous 500 error.

A runtime defect found during UI smoke was corrected: the Blade expression had resolved `IlluminateSupportFacadesRoute` as a class name. Route availability now belongs to the controller and the contract test protects against direct `Route::has()` use in this Blade.

### Security / data-scope boundary

- Foundation permissions are `client.pharma.*` Web permissions; Admin Pharma permissions are not reused.
- Permission does not imply unrestricted row-level Pharma access.
- Future commercial, hospital, commission and other user-sensitive capabilities must combine feature permission with assigned business scope.
- Admin-only master/configuration/destructive workflows remain outside the PWA.

### Known issues / deferred work

- Products, price lists, bid awards, commercial, inventory and commissions are intentionally not implemented in this foundation.
- Mobile/tablet acceptance for future data-heavy capabilities is performed when each capability is implemented.
- No PWA file download/open behavior is introduced by this foundation, so the external-file handoff gate is not applicable here.
- Full-project regression is **NOT APPLICABLE — module-scoped ClientPortal regression strategy**.

### Next step

Complete final PR gate for this Foundation (working-tree clean verification and PR review/merge approval). After Foundation is merged, start the first business capability on a new branch. The planned first capability is read-only Pharma product/Medicine catalog, after inspecting current Pharma service/query boundaries and row-scope requirements.

## Current delivery — Invoices GDT Smart Sync PWA

- Last updated: 2026-09-09
- Active branch: `feat/clientportal-invoices-gdt-smart-sync`
- Status: **IMPLEMENTED — FOCUSED TESTS PASS — UI/RUNTIME PASS — FINAL PINT CONFIRMATION PENDING**

### Objective

Expose a secure PWA GDT synchronization workflow while preserving the ownership boundary: ClientPortal owns client UX/orchestration and Invoices owns GDT/business/data persistence.

### Delivered capability

- server-side GDT token readiness gate before queue dispatch;
- CAPTCHA reconnect flow with challenge key kept in server session;
- no GDT username/password/access token exposed to Blade, JavaScript, localStorage, IndexedDB or PWA cache;
- current-year Smart Date readiness split by `purchase` / `sold` using canonical `issued_date` and `invoice_type` fields;
- suggested start remains the latest invoice date itself, not `+1 day`, so same-day late invoices can be rechecked;
- responsive PWA Smart Sync UI with status/log presentation;
- GDT payload persistence into the canonical `invoices` table before Excel export;
- idempotent identity strategy: prefer `invoice_type + lookup_code`, fallback to `invoice_type + symbol + invoice_number + tax_code + issued_date` when lookup code is absent;
- transactional DB persistence with `created / updated / unchanged` statistics in the sync log;
- Excel export remains available after successful DB persistence.

### Validation evidence

Recorded focused automated gate:

```text
14 tests passed (115 assertions)
```

Manual PWA/UI/runtime acceptance reported by user: **PASS**.

The final Pint run previously exposed one style-only issue in `GdtInvoiceService.php`; the affected formatting rules were corrected in commit `24129a52ef532e916ed282811ae7a8553c18a1c7`. A post-fix Pint confirmation remains the final CLI closeout gate.

### Security / ownership boundary

```text
Modules/Invoices
  owns GDT API/auth integration
  owns invoice persistence/schema/model
  owns Excel/file generation
  owns queue/business behavior

Modules/ClientPortal/Applications/Invoices
  owns PWA routes
  owns web-guard/client authorization
  owns CAPTCHA/connect presentation
  owns Smart Sync responsive UX
```

ClientPortal does not copy Invoices models/schema/core services and does not reuse Admin authentication or Admin presentation.

## Previous delivery — Invoices Partner Report PWA

- Last updated: 2026-09-09
- Active branch: `feat/clientportal-invoices-partner-report`
- Base branch: `main`
- Base merge checkpoint: `b0f4d18ee7430e16b302ac43914c3dbac3065e40`
- Status: **IMPLEMENTED — UI PASS — BOUNDED CLI PASS — READY FOR PR**
- Closeout: `docs/modules/ClientPortal/INVOICES_PARTNER_REPORT_CLOSEOUT.md`

### Objective

Expose the Admin partner-report capability as a dedicated executive ClientPortal/PWA experience without moving Invoices business ownership into ClientPortal and without reusing Admin authentication or Admin presentation.

### Canonical ownership

```text
Modules/Invoices
  owns invoice/partner business data
  owns partner aggregation/query rules
  owns InvoicePartnerReportService

Modules/ClientPortal/Applications/Invoices
  owns PWA routes
  owns web-guard/client-feature authorization
  owns executive responsive presentation
  owns client-safe orchestration
```

ClientPortal does not copy invoice models/schema/core services and does not reuse `auth:admin`, Admin Blade or Admin Livewire for this delivery.

### Delivered client capability

```text
GET /apps/invoices/partners
name: client.invoices.partners
permission: client.invoices.partners.view
```

Implemented Partner Report UX:

- executive partner KPIs;
- Top bán ra;
- Top mua vào;
- partner search by name or MST using the shared compact autocomplete pattern;
- relationship filter: all / customer / supplier;
- relationship-aware default sorting: customer => sold descending, supplier => purchase descending;
- partner detail;
- pagination;
- Mobile/Tablet card presentation below `xl`;
- Desktop table presentation from `xl` upward;
- money values use stable single-line presentation where appropriate;
- responsive filter panel for Mobile/Tablet;
- Desktop horizontal filter workspace remains available.

### Invoice List follow-up included in this branch

`/apps/invoices/list` now supports `Tháng = Cả năm`.

Contract:

- empty `month` => full selected year (`01/01` through `31/12`);
- explicit month => selected month only;
- no `month` parameter on a normal initial request keeps the established current-month default;
- header text reflects either `Cả năm YYYY` or `Tháng MM/YYYY`;
- search, pagination and export preserve the selected yearly/monthly scope;
- export contract remains selected rows => selected only, no selection => all filtered.

### Validation evidence

Manual UI acceptance: **PASS**

```text
Partner report Desktop: PASS
Partner report Tablet: PASS
Partner report Mobile/PWA: PASS
Partner autocomplete: PASS
Relationship-aware sort: PASS
Top sold/top purchase amount visibility on Tablet/Mobile: PASS
Invoice List `Cả năm`: PASS
```

Final bounded CLI acceptance: **PASS**

```text
php artisan test tests/Feature/ClientPortal/InvoicesPartnerReportPwaContractTest.php
PASS

vendor/bin/pint --test <changed PHP files for this delivery>
PASS
```

The first focused run exposed only stale responsive assertions (`md` breakpoint) and one Pint style issue in `InvoicePartnerReportService.php`; both were corrected. The contract now protects the current responsive boundary (`xl:hidden` cards / `xl:block` desktop table).

### Validation policy

Previously PASSed Invoices PWA tests/build/UI scopes are not rerun unless later changes invalidate them. Full-repository Pint is not a merge gate because the repository contains unrelated legacy formatting debt. Only changed/invalidated scopes are gates for this delivery.

### Security / operations boundary

- GDT credentials/tokens remain server-side only.
- No GDT token is placed in localStorage, IndexedDB, PWA cache, JS, Blade or public component state.
- Google Drive configuration, backup/restore and destructive operations remain Admin/Invoices-only.
- No migration, schema rewrite or destructive data operation is part of this delivery.

## Previous completed Invoices PWA delivery

The base Invoices PWA delivery was merged through PR #173 and subsequently corrected by selected-month KPI hotfix PR #175.

Canonical Invoices PWA routes already on `main` include:

```text
GET  /apps/invoices
GET  /apps/invoices/list
GET  /apps/invoices/list/{invoice}
POST /apps/invoices/export
GET  /apps/invoices/list/{invoice}/pdf
GET  /apps/invoices/sync
POST /apps/invoices/sync
```

Base PWA validation previously recorded:

```text
Executive Dashboard UI: PASS
Invoice List Desktop: PASS
Invoice List Tablet: PASS
Invoice List Mobile: PASS
Partner autocomplete: PASS
Selected-vs-filtered export UX: PASS
Focused ClientPortal/Invoices tests: PASS
Latest recorded focused batch from base delivery: 62 passed (378 assertions)
npm run build: PASS
```

## Stable ClientPortal architecture

ClientPortal remains an authenticated Client/WebApp platform that can host multiple applications without placing module-specific business logic into ClientPortal core.

Core rule:

> Không được thêm logic đặc thù Module vào ClientPortal core.

Auth owns authentication/session/logout behavior. ClientPortal owns PWA presentation and consumes canonical module/application contracts.

Historical stable checkpoints:

```text
MR-1 Portal Architecture Foundation: MERGED
MR-2 Adaptive Navigation: MERGED
MR-3 Dynamic Portal Home: MERGED
MR-4 Muasamcong reference migration: MERGED
MR-5 PWA External File Download & Return UX: MERGED — PR #64
MR-6 PWA Install UX: MERGED — PR #65
MR-7 PWA Account Registration & Google Authentication: MERGED — PR #67
MR-8 PWA Header Account Menu: MERGED — PR #68
Canonical web logout corrective: MERGED — PR #86
ClientPortal architecture boundaries refactor: MERGED — PR #124
Invoices executive PWA: MERGED — PR #173
Invoices selected-month KPI hotfix: MERGED — PR #175
Invoices Partner Report PWA: READY FOR PR
Invoices GDT Smart Sync PWA: UI/RUNTIME PASS — FINAL PINT CONFIRMATION PENDING
```

## Deferred debt

- relocate/remove legacy root Muasamcong export jobs only with explicit queued-payload proof;
- remove root model aliases only after caller proof;
- avoid speculative consolidation of small ClientPortal resolver/presenter services without concrete duplication or caller evidence;
- keep Invoices Google Drive configuration, backup/restore and destructive recovery outside ClientPortal PWA unless a separate security/operations scope explicitly authorizes them.
