# Pharma Price List v2

Date: 2026-09-14
Branch: `feat/pharma-canonical-medicine-catalog`

## Ownership

Pharma owns Medicine Master, Medicine Variant/SKU, Medicine Package, Price Lists, Price List Items, pricing validation and the `PriceResolver` contract. Partner remains the source of truth for customer identity/classification. Inventory does not decide medicine sale prices.

## Source of truth

Price List v2 uses Medicine Master directly. `storage/app/excel/BANG_GIA_TONG_HOP.xlsx` is not a source for the v2 workflow. Existing generic spreadsheet helpers may remain for unrelated uses. Excel is output only.

## Persistence

Tables:

- `pharma_price_lists`
- `pharma_price_list_items`

A price item requires a Medicine Variant/SKU and may optionally target a Medicine Package. `identity_key=variant:{variant_id}:package:{package_id|0}` provides deterministic duplicate protection even when package is null.

`declared_price_snapshot` is copied from `Medicine.declared_price` when a Draft item is saved. Historical lists therefore do not change when the Medicine Master declared price changes later.

## Four prices

Each item stores:

1. `declared_price_snapshot` — readonly ceiling/reference;
2. `company_sale_price` — approved company sale price;
3. `actual_receivable_price` — independent actual receivable price;
4. `invoice_price` — independent invoice price.

All configured prices must be non-negative. `company_sale_price` may not exceed `declared_price_snapshot`. Activation requires every item to have `company_sale_price`.

## Global and customer lists

`global` lists require `partner_id=null`.

`customer` lists require an active Partner whose `partner_types` contains `customer`. Pharma does not create a second customer master.

Statuses: `draft`, `active`, `inactive`, `archived`.

Draft is editable. Active identity is not edited directly; clone to a new Draft for material changes.

## Resolver

Contract: `Modules\Pharma\Contracts\PriceResolver`.
Implementation: `Modules\Pharma\Services\DatabasePriceResolver`.
DTO: `Modules\Pharma\DTOs\ResolvedPrice`.

Resolution order for the requested date and SKU/package:

1. active/effective customer list for the requested Partner;
2. active/effective global list;
3. `null` / NO_PRICE.

There is intentionally no fallback from `Medicine.declared_price` to a sale price. Declared price is a ceiling/reference only.

Within the same resolution tier ordering is deterministic: priority descending, effective-from descending, then list id descending. v1 activation also prevents overlapping active lists in the same global/customer scope.

## Admin workspace

Routes:

- `GET /admin/pharma/price-lists`
- `GET /admin/pharma/price-lists/create`
- `POST /admin/pharma/price-lists`
- `GET /admin/pharma/price-lists/{priceList}`
- `GET /admin/pharma/price-lists/{priceList}/edit`
- `PUT|PATCH /admin/pharma/price-lists/{priceList}`
- `POST /admin/pharma/price-lists/{priceList}/activate`
- `POST /admin/pharma/price-lists/{priceList}/deactivate`
- `POST /admin/pharma/price-lists/{priceList}/clone`
- `GET /admin/pharma/price-lists/{priceList}/export`

The create/edit workspace is a three-step UI: header, Medicine Master SKU/package selection + pricing, review/save Draft. Selection persists across search/filter/manual pagination. Bulk pricing supports discount from declared price and copying company sale price to receivable/invoice price.

## Export

Export reads DB Price List + items, never the old source workbook. Selected item checkboxes export only selected rows; no selection exports all rows of the Price List.

## Verification gate

Targeted tests:

`php artisan test Modules/Pharma/Tests/Unit/PriceListV2ContractTest.php`

Targeted Pint:

`./vendor/bin/pint Modules/Pharma/Models/PriceList.php Modules/Pharma/Models/PriceListItem.php Modules/Pharma/DTOs/ResolvedPrice.php Modules/Pharma/Contracts/PriceResolver.php Modules/Pharma/Services/DatabasePriceResolver.php Modules/Pharma/Services/PriceListManager.php Modules/Pharma/Http/Controllers/PriceListController.php Modules/Pharma/Livewire/PriceList/Index.php Modules/Pharma/Livewire/PriceList/Create.php Modules/Pharma/Tests/Unit/PriceListV2ContractTest.php`
