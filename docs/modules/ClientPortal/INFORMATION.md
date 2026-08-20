# ClientPortal — Information

Assessment source: `agent/price-list-excel-data-types` as of 2026-08-20.

This document records the current implemented behavior and module map. It is descriptive, not a refactor plan.

## 1. Purpose

`Modules/ClientPortal` is the authenticated Client/PWA shell and application-adapter layer of the project.

It currently owns:

- `/my-apps` application launcher;
- `/apps/*` authenticated Client application routes;
- Client application/feature permission discovery and assignment;
- reusable PWA application layout;
- Client workflow records such as queue requests, public shares and price-list exports;
- Client-side adapters to domain modules;
- Client-specific queue jobs for export/PDF/email;
- Admin screens for assigning Client permissions.

The first implemented application adapter is `Muasamcong`.

## 2. Module registration

`Modules/ClientPortal/config/module.php`:

```text
name            ClientPortal
type            support
default_enabled true
depends         Auth
```

`ClientPortalServiceProvider` registers `ApplicationContext` as a singleton.

The repository's `Modules/ModuleServiceProvider.php` owns generic module discovery, route loading, view namespace registration, migration loading and module enablement.

ClientPortal-specific middleware aliases are currently registered in `bootstrap/app.php`:

```text
client.application -> EnsureApplicationAccess
client.feature     -> EnsureFeatureAccess
```

## 3. Directory map

```text
Modules/ClientPortal/
├── Applications/
│   └── Muasamcong/
│       ├── manifest.php
│       ├── routes.php
│       ├── Http/Controllers/
│       │   ├── MuasamcongApplicationController.php
│       │   ├── MuasamcongHistoryController.php
│       │   ├── MuasamcongPriceListController.php
│       │   ├── MuasamcongShareManagementController.php
│       │   ├── MuasamcongWishlistController.php
│       │   └── PublicDrugShareController.php
│       ├── Jobs/
│       │   └── SyncPricingResultsJob.php
│       └── Services/
│           └── ClientPricingSearchService.php
├── Http/
│   ├── Controllers/
│   │   ├── Admin/ApplicationAdminController.php
│   │   └── PortalController.php
│   └── Middleware/
│       ├── EnsureApplicationAccess.php
│       └── EnsureFeatureAccess.php
├── Jobs/
│   ├── GeneratePriceListExport.php
│   ├── GeneratePriceListPdf.php
│   └── SendPriceListExportEmail.php
├── Models/
│   ├── PriceListExport.php
│   ├── PublicShare.php
│   └── SyncRequest.php
├── Providers/
│   └── ClientPortalServiceProvider.php
├── Services/
│   ├── ApplicationPermissionService.php
│   └── ApplicationRegistry.php
├── Support/
│   └── ApplicationContext.php
├── config/module.php
├── database/migrations/
├── resources/views/
├── routes/web.php
└── README.md
```

## 4. Application adapter contract

The module uses:

```text
Modules/ClientPortal/Applications/{Application}/manifest.php
Modules/ClientPortal/Applications/{Application}/routes.php
```

The manifest declares:

- application key;
- source domain module;
- label/description/icon;
- launcher route;
- application permission;
- feature definitions;
- optional feature action permissions.

`ApplicationRegistry` scans adapter directories and exposes only adapters whose `source_module` is currently enabled in `modules.registry`.

Normalized feature structures contain:

```text
key
name
description
route
permission
icon
sort_order
actions[]
```

## 5. Permission conventions

Client permission namespace:

```text
client.{application}.access
client.{application}.{feature}.view
client.{application}.{feature}.{action}
```

Current Muasamcong manifest declares:

```text
client.muasamcong.access
client.muasamcong.drug-pricing.view
client.muasamcong.drug-pricing.sync
client.muasamcong.history.view
client.muasamcong.wishlist.view
client.muasamcong.price-list.view
client.muasamcong.price-list.export
client.muasamcong.contractors.view
client.muasamcong.analytics.view
```

Client permissions use guard `web`.

Admin ClientPortal management routes use guard `admin` and currently reuse:

```text
view_role
edit_role
edit_user
```

`ApplicationPermissionService`:

- derives permission definitions from application manifests;
- creates missing `web` permissions;
- assigns/revokes direct Client permissions for users;
- assigns Client permissions to `web` roles;
- can grant all defined Client permissions directly to users who hold Admin `Super Admin` role.

## 6. Portal routes

### Client launcher

```text
GET /my-apps
name: client.apps.index
middleware: web, auth:web
```

`PortalController` passes `ApplicationRegistry::forUser()` to `ClientPortal::pages.apps`.

### Admin management

Base:

```text
/admin/client-apps
```

Functions:

- view discovered applications/features/permissions;
- explicit permission synchronization;
- synchronize all Client permissions to Super Admin users;
- edit direct Client permissions for a User;
- edit Client permissions for a `web` Role.

## 7. PWA foundation

Client launcher and application layout reference:

```text
/manifest.webmanifest
/service-worker.js
/pwa/icon.svg
```

The service worker currently caches only shell assets and offline fallback. Authenticated navigation uses network-first behavior and is not written to Cache Storage.

The generic application layout provides:

- responsive header;
- back-to-app-launcher navigation;
- optional application dashboard route;
- logout;
- mobile bottom navigation partial by current application;
- service worker registration.

It currently also hosts Muasamcong sync-request polling UI; this is documented as refactor debt in `ANALYSIS.md`.

## 8. Muasamcong adapter

Source module:

```text
Muasamcong
```

The adapter is registered only while `modules.registry.Muasamcong.enabled` is true.

### Current feature manifest

#### Drug pricing

Permission:

```text
client.muasamcong.drug-pricing.view
```

Action permission:

```text
client.muasamcong.drug-pricing.sync
```

Functions:

- keyword search;
- additional filters;
- price sorting;
- detail view;
- selected sync through Queue;
- public drug link creation;
- share management;
- Wishlist toggle integration.

#### History

Permission:

```text
client.muasamcong.history.view
```

Combines recent:

- pricing search snapshots;
- Client sync requests.

#### Wishlist

Permission:

```text
client.muasamcong.wishlist.view
```

Functions:

- list/search user Wishlist;
- store/toggle;
- delete own Wishlist rows.

#### Price List

View permission:

```text
client.muasamcong.price-list.view
```

Export action permission:

```text
client.muasamcong.price-list.export
```

Functions:

- select source from synced data or Wishlist;
- select an Admin export profile;
- create queued XLSX;
- edit selection by creating a new version;
- recreate with current profile;
- delete export;
- poll status;
- private Excel download;
- queued Excel-to-PDF conversion;
- private PDF download;
- create public Excel share URL;
- queue email delivery with Excel/PDF/both;
- show recent delivery/share metadata.

`contractors` and `analytics` are declared in the manifest but current inspected ClientPortal routes do not yet implement those screens.

## 9. Database-first pricing search

`ClientPricingSearchService` order:

```text
1. Muasamcong PricingResult database
2. PricingSearchSnapshot
3. MuaSamCong API
```

`refresh=1` bypasses local data and snapshot and calls the API.

Synced DB search currently:

- searches medicine name, active ingredient, TBMT, medicine group, manufacturer and winning name;
- sorts newest sync first;
- caps result at 500;
- maps domain rows back to the API-like payload expected by Client UI.

A successful API result is persisted through `PricingSearchSnapshotService` when possible.

## 10. Drug-pricing page behavior

`MuasamcongApplicationController::drugPricing()` currently performs:

- request validation;
- database-first search orchestration;
- additional in-memory filtering;
- price sorting;
- result summary (count/min/average/max);
- pagination at 20 rows/page;
- determination of already-synced source IDs;
- determination of current page Wishlist IDs;
- permission checks for sync/Wishlist actions.

UI uses progressive filter disclosure: main search remains compact while secondary filters open in a panel/sheet.

## 11. Queue synchronization

Queue record table:

```text
client_portal_sync_requests
```

Main fields:

```text
id UUID
user_id
application_key
feature_key
keyword
source_ids JSON
selected_count
status
inserted_count
duplicate_count
missing_count
error_message
started_at
finished_at
```

Sync action:

1. validates keyword and up to 100 UUID source IDs;
2. creates a `queued` SyncRequest;
3. dispatches `SyncPricingResultsJob` on default queue;
4. job re-fetches source data;
5. TBMT searches can load full paginated result through the domain pagination service;
6. selected rows are persisted by `PricingResultSyncService`;
7. request becomes completed/failed;
8. owner-scoped status endpoint supplies progress/result to the Client UI.

Job settings:

```text
tries   3
timeout 180s
queue   default
```

## 12. Public drug sharing

Table:

```text
client_portal_public_shares
```

Main fields:

```text
token unique
created_by
application_key
feature_key
source_id
title
payload JSON
views_count
last_viewed_at
expires_at
revoked_at
```

Creation re-resolves the source item and stores an allowlisted public payload.

Public route:

```text
/share/muasamcong/drug/{64-char token}
```

Public availability requires:

- not revoked;
- no expiry or expiry in the future.

Owner can manage expiry and revoke links from the authenticated Client area.

## 13. Wishlist persistence

Wishlist records remain owned by the Muasamcong domain model:

```text
Modules\Muasamcong\Models\PricingWishlist
```

ClientPortal scopes reads/writes to the authenticated web user. When a source is added, the adapter re-resolves the pricing search result and stores selected normalized fields plus snapshot.

Current persistence logic is implemented directly in the Client controller; `ANALYSIS.md` recommends moving canonical persistence semantics into a Muasamcong service.

## 14. Price List export profiles

Client Price List currently reuses:

```text
Modules\Muasamcong\Models\SyncedExportProfile
Modules\Muasamcong\Services\SyncedPricingExportPreferenceService
```

The Admin profile schema includes:

```text
user_id
name
is_default
column_order
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
```

`user_id + name` is unique.

Admin `SyncedPricingList` creates/manages profiles under the authenticated Admin ID.

ClientPortal currently lists all profile rows and accepts any existing profile ID. The intended publication/ownership scope must be made explicit; see `ANALYSIS.md`.

## 15. Price List export record

Table:

```text
client_portal_price_list_exports
```

Key fields:

```text
id UUID
user_id
profile_id
source
selected_ids JSON
status
items_count
file_path
file_name
share_token
error_message
started_at
completed_at
pdf_status
pdf_path
pdf_name
pdf_error_message
delivery_history JSON
```

`PriceListExport` casts:

- `selected_ids` -> array;
- `delivery_history` -> array;
- `started_at`/`completed_at` -> datetime.

## 16. Excel Queue workflow

`GeneratePriceListExport` currently:

1. loads PriceListExport and chosen SyncedExportProfile;
2. loads normalized profile preference;
3. resolves selected columns;
4. loads selected synced/Wishlist records;
5. builds a PhpSpreadsheet workbook;
6. writes configured headers/column widths;
7. optionally renders company header/logo;
8. writes typed values;
9. renders footer/signature;
10. applies page setup;
11. saves XLSX to private local storage;
12. updates export record completed/failed.

Supported configured data types:

```text
string
number
date
auto
```

Current export-specific normalization:

```text
Nhóm thuốc -> first valid integer 1..5 when recognizable
Hạn dùng   -> first integer when recognizable
```

Current Page Setup defaults:

```text
paper              A4
orientation        landscape
left/right margin  0.3 cm
top/bottom margin  0.8 cm
center horizontal  true
center vertical    false
scaling            fit_width
fit width          1 page
fit height         automatic (0)
```

Header behavior includes bold `Kính gửi`.

Signature/footer is currently placed in the final four selected spreadsheet columns.

### Current XLSX storage path

```text
client-portal/price-lists/{user_id}/bang-gia-{Ymd-His}.xlsx
```

This path is documented as a P1 collision risk in `ANALYSIS.md` and should become export-ID/UUID scoped.

## 17. PDF Queue workflow

`GeneratePriceListPdf`:

1. requires a completed Excel artifact;
2. sets PDF status processing;
3. creates a private temp work directory;
4. loads XLSX through PhpSpreadsheet;
5. freezes wrapped table row heights to reduce Excel/LibreOffice layout drift;
6. saves a normalized temporary XLSX;
7. invokes `libreoffice --headless --convert-to pdf` using Laravel Process argument array;
8. saves PDF on private local storage;
9. normalizes filesystem access for the current web runtime;
10. marks PDF completed/failed;
11. removes temp files in `finally`.

Current PDF storage path is export-ID scoped:

```text
client-portal/price-lists/{user_id}/{export_id}/{pdf_name}
```

## 18. Price List email workflow

`SendPriceListExportEmail` accepts:

```text
exportId
email
content
attachExcel
attachPdf
```

It resolves available private files, sends a raw-text mail with selected attachments, then appends a delivery-history entry containing:

```text
channel=email
recipient
content
formats
sent_at
```

Current delivery history is a JSON array on the export row, capped to the latest 20 entries.

## 19. Price List share workflow

Authenticated Client creates/reuses a random 64-character `share_token` on the export row.

Public route:

```text
/share/muasamcong/price-list/{token}
```

The route downloads the private Excel file when:

- token resolves;
- export status is completed;
- file exists.

Current Price List share tokens do not have separate expiry/revoke fields. See `ANALYSIS.md`.

## 20. UI implementation

ClientPortal currently uses Controller + Blade rather than Livewire for its own PWA pages. This is acceptable for the current form/request flows; Livewire is not required by the project standard for every page.

Main views:

```text
resources/views/pages/apps.blade.php
resources/views/layouts/application.blade.php
resources/views/admin/index.blade.php
resources/views/admin/user-permissions.blade.php
resources/views/admin/role-permissions.blade.php
resources/views/applications/muasamcong/dashboard.blade.php
resources/views/applications/muasamcong/drug-pricing.blade.php
resources/views/applications/muasamcong/drug-pricing-detail.blade.php
resources/views/applications/muasamcong/history.blade.php
resources/views/applications/muasamcong/wishlist.blade.php
resources/views/applications/muasamcong/shares.blade.php
resources/views/applications/muasamcong/price-list.blade.php
resources/views/public/muasamcong/drug-share.blade.php
```

The current UI is responsive and mobile-first in key workflows, including:

- compact search + secondary filter panel;
- mobile result cards/navigation;
- PWA shell/install prompt;
- queue feedback;
- Price List workspace cards/modals.

## 21. Adjacent domain dependencies

Current Muasamcong adapter imports domain classes including:

```text
Models:
- PricingResult
- PricingWishlist
- PricingSearchSnapshot
- SyncedExportProfile

Services:
- MuaSamCongService
- PricingSearchSnapshotService
- PricingResultSyncService
- PricingTbmtPaginationService
- SyncedPricingExportPreferenceService
```

This dependency direction is valid for an adapter. Domain modules should continue to have no dependency on ClientPortal.

## 22. Current automated tests

Relevant directory:

```text
tests/Feature/ClientApps
```

Observed test files include:

```text
ClientApplicationAdminTest.php
ClientApplicationRegistryTest.php
ClientPortalExtractionTest.php
ClientPwaFoundationTest.php
MuasamcongClientSearchTest.php
MuasamcongHistoryTest.php
MuasamcongPriceListTest.php
MuasamcongPublicSharingTest.php
MuasamcongQueueStatusTest.php
MuasamcongShareManagementTest.php
MuasamcongWishlistTest.php
```

Coverage strengths:

- application discovery/enablement;
- guest/no-permission access;
- middleware route structure;
- PWA caching contract;
- database-first search behavior;
- sync job state transitions;
- owner-scoped sync status;
- public share model availability;
- price-list route/profile/page setup structure.

Major missing behavioral Price List and mutation-authorization tests are listed in `ANALYSIS.md`.

## 23. Operational dependencies

Price List generation requires:

- queue worker for default queue;
- writable/traversable Laravel private storage;
- PhpSpreadsheet;
- LibreOffice CLI for PDF conversion;
- configured Laravel mail transport for delivery.

The project previously experienced queue workers running as `root` while web PHP used `www-data`, producing download permission failures. Current PDF code contains a runtime permission workaround, while the preferred infrastructure state is a consistent application filesystem user/group.

## 24. Public contracts to preserve during refactor

Unless an approved plan changes them, preserve:

- `/my-apps` launcher route/name;
- `/apps/muasamcong/*` route names currently used by UI/tests;
- application/feature manifest discovery;
- source-module enable/disable behavior;
- web-guard Client permission namespace;
- database-first search semantics;
- user ownership isolation for workflow records/files;
- private XLSX/PDF storage and controller-mediated downloads;
- Admin-defined rich Excel profile behavior;
- current responsive PWA UX;
- Queue-based long-running work.

See `ANALYSIS.md` for contracts that need explicit correction before becoming stable.