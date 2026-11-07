## Traditional drug registration lookup — 2026-11-07 (checkpoint pending)

- User approved Option A (read-only): `https://ydct-dichvucong.moh.gov.vn/congbothuoc`, POST `/api/services/app/soDangKy/GetAllPublicServerPaging`, sample `SoDangKyThuoc.TenThuoc=cold wind`, 15 items/page. Source result `Cold Wind`, id 3886, GPLH `TCT-00158-23`, `446/QĐ-YDCT`, no published expiry. Keep multiline herbal ingredients and packaging; never infer expiry from null.
- Branch `feat/pharma-traditional-drug-registrations-20261107` stacked on `feat/pharma-dav-drug-registration-lookup-20261107` commit `f1c490f` (prior GMP/lookup hub branches not yet merged to main). Review stacked PR dependencies before any merge.
- `TraditionalDrugRegistrationLookup` subclasses shared read-only `DavDrugRegistrationLookup`; source-specific endpoint, 15-item pagination and isolated cache namespace; shared nested response mapping. Existing DAV service behavior remains 10 items/page. Shared `BasePharmaRegistrationController` and Blade listing/detail handle both features; thin source-specific controllers. Routes GET-only, protected by `client.feature:pharma,traditional-drug-registrations`; manifest feature appears only under Tra cứu hub, not dashboard/navigation. No database writes, migrations, scheduler, downloads or Medicine Master sync.
- Focused test: `tests/Feature/ClientApps/PharmaTraditionalDrugRegistrationLookupTest.php`. Test code committed but **not executed**. Existing DAV regression `PharmaDrugRegistrationLookupTest.php` required after focused PASS; impacted lookup hub and GMP contract tests only after focused PASS. Manual UI test: search `cold wind`, detail, no-expiry wording, multiline herbal ingredients/packaging, pagination and responsive.
- Confirmed API field only `SoDangKyThuoc.TenThuoc`. Other filter field names are inherited from DAV and require live verification before claiming they work for MOH. No browser cookies/XSRF tokens persisted.
- No PR, merge or production deploy; do not claim TEST/UI PASS until user provides results.

---

## DAV drug registration public lookup — 2026-11-07

- User approved Option A: independent read-only lookup for `https://dichvucong.dav.gov.vn/congbothuoc/index`, POST `/api/services/app/soDangKy/GetAllPublicServerPaging`, no sync into Medicine Master. Sample user-provided response for Venclexta has three independent registrations 539110203326 (10mg), 539110203226 (100mg), 539110203126 (50mg). Nested `thongTinDangKyThuoc`, `thongTinThuocCoBan`, `congTySanXuat`, `congTyDangKy` are mapped.
- Feature branch `feat/pharma-dav-drug-registration-lookup-20261107` created from current unmerged `feat/pharma-domestic-gmp-crawler-20261107` SHA `32339dbd`, because main remains `36e43665a` and GMP/lookup hub dependencies are not merged. Do not merge child branch before parents; review stacked PR base appropriately.
- `Modules/Pharma/Services/DavDrugRegistrationLookup.php`: read-only public DAV POST, bounded page/keyword, five-minute cache, strict response validation, no browser cookies/XSRF, no DB writes. `PharmaDrugRegistrationController` validates GET input and verifies selected source ID against the same search page for detail. Two GET routes protected by `auth:web`, `client.application:pharma`, `client.feature:pharma,drug-registrations` and existing DAV read permission. Manifest feature only, no top-level dashboard/nav card; lookup hub includes link. Two responsive Blade pages.
- API field `SoDangKyThuoc.TenThuoc` is confirmed from supplied request. Other dropdown field keys (`SoDangKy`, `HoatChatChinh`, `TenCongTyDangKy`, `TenCongTySanXuat`) are inferred and **must be validated against actual DAV network requests** before considering those filters accepted; avoid claiming UI PASS for them without evidence. Embedded PDF paths in `thongTinTaiLieu` are not used because download endpoint/security have not been verified.
- Focused test `tests/Feature/ClientApps/PharmaDrugRegistrationLookupTest.php` added; **not run**. Checkpoint: fetch/switch/pull branch, focused test first; if PASS, impacted `PharmaLookupHubContractTest`, `PharmaForeignGmpLookupTest`, `PharmaDomesticGmpOnDemandTest`; UI search Venclexta and three registration details, empty/error/page, mobile. Do not merge/deploy without approval.

---

## On-demand GMP focused test correction — 2026-11-07

- DELL Test 1: 2 passed, 2 failed (18 assertions). Both failures occurred after calling `fakeDav('second')` within the same PHPUnit method: expected corrupted second Excel / changed SHA, but prior `Http::fake` callback continued to match. Root cause is test fixture stacking multiple Laravel HTTP fake registrations; production snapshot service not changed.
- Fixed `PharmaDomesticGmpOnDemandTest::fakeDav()` to register one callback per test, with mutable marker/badPackaging properties read on each request. Consecutive refreshes now receive second-response content; unexpected requests throw. No tests executed by assistant.
- Run focused test again. Only after PASS run impacted crawler/hub/foreign GMP regression; UI acceptance and download checks remain outstanding. No merge/deploy.

---

## Domestic GMP on-demand current-only snapshots — 2026-11-07

- User approved replacing open-page DAV crawl and monthly/scheduled checks with explicit on-demand refresh; no historical Excel versions needed. Continued on existing `feat/pharma-domestic-gmp-crawler-20261107` stacked feature branch, main remained `36e43665a` during audit. No worktree, production changes or migration.
- `Modules/Pharma/Services/DavDomesticGmpSnapshot.php` owns private local current manifest and 3 XLSX snapshot files. `refresh()` obtains exclusive nonblocking file lock, fetches latest DAV publication only on explicit POST, downloads all 3, checks XLSX ZIP structure, hashes each, stages files and atomically renames manifest; only then deletes prior managed version. On any failed DAV fetch or validation, prior manifest/files stay usable. Hash detects replacement of same DAV URL. Repeating unchanged refresh reports unchanged.
- ClientPortal GET `/apps/pharma/domestic-gmp` reads local snapshot only (no DAV requests); POST `/apps/pharma/domestic-gmp/refresh` uses CSRF and existing `client.feature:pharma,domestic-gmp` middleware + web-guard permission; download GET serves only private local current snapshot without calling DAV. Blade shows manual refresh action, status, three stored files or empty state; presentation continues from managed feature manifest.
- `PharmaDomesticGmpOnDemandTest` covers first refresh, unchanged repeat, rollback on corrupt second workbook, replacement and POST route. Tests have **not run**. Focused tests first; impacted crawler/lookup regression only if focused PASS; manual desktop/iPhone/PWA UI and all 3 downloads required.
- Legacy content-addressed files from the previous prototype may remain in the old directory layout; not auto-deleted to avoid removing unknown existing files. Only snapshots managed by new manifest are replaced. No scheduler/queue/history DB.
- Known gates: test ZIP extension availability, actual DAV XLSX validation, PWA download handoff behavior and browser UI; no merge/deploy without approval.

---

## DAV domestic GMP date display fix — 2026-11-07

- User screenshot confirms article n5553 and all three Excel download cards render, but publication date field contains trailing `In function printDiv(id) { var html=...` script text.
- Changed `DavDomesticGmpCrawler::article()` to extract only the first `dd/mm/yyyy` match from DAV `time` node, returning empty date when no date match exists. Added regression fixture containing embedded printDiv text and assertion that `published` is exactly `28/10/2026`.
- The screenshot proves discovery/rendering only; actual XLSX download, file integrity, tests and full UI acceptance still require confirmation. No merge or deploy.

---

## DAV GMP attachment parsing checkpoint — 2026-11-07

- User DELL audit of real article n5553: HTTP 200, 49,822 HTML bytes, 88 anchors, `#DivIdToPrint` and `.description` both present, six attachment links: three PDF and three XLSX, all percent-encoded Vietnamese filenames under `/upload_images/files/`. Previous article-scoped XPath found zero XLSX.
- Updated parser to inspect document-wide anchors, strictly validate HTTPS DAV XLSX paths, deduplicate URLs and map decoded filenames by semantic Vietnamese labels: domestic GMP manufacturers, GMP packaging and business eligibility certificates. Reject missing/ambiguous group rather than assigning by order. Added encoded-name regression fixture. No network/production test executed after fix.
- Next: pull branch, run focused tests, then read-only `latest()` diagnostic, then real UI/download tests. Stop on FAIL; no merge/deploy.

---

## Domestic GMP crawler live DAV parsing fix — 2026-11-07

- DELL read-only diagnostic confirmed DAV category HTTP 200, text/html 50,683 bytes, 20 matching article links, latest n5553. Invoking Laravel `DavDomesticGmpCrawler::latest()` failed with `DAV article contains no recognized Excel attachments` at line 81.
- Root cause in source: Excel discovery searched only direct child elements of `.description` inside `#DivIdToPrint`; nested DAV markup can hide all anchors. Fix searches all anchors in the article subtree, keeps strict DAV HTTPS /upload_images/files/*.xlsx validation, deduplicates and requires exactly three Excel links before mapping the audited article order to manufacturers, packaging, business certificates. Unexpected count fails closed to prevent mislabeled files. Test fixture now nests links inside table/wrapper.
- **Not yet verified on DELL**; run focused tests and real read-only latest() diagnostic before UI/download testing. If real article has more/less than three Excel links or different order, stop and inspect real DOM rather than relax mapping blindly. No merge/deploy.

---

## Domestic GMP DAV Excel crawler — 2026-11-07

- User supplied original HTML `1.html` from DAV article `n5553`, posted 28/10/2026, list updated to 23/10/2026. Audited six links in article description: three PDFs and three XLSX files grouped as domestic manufacturers, GMP pharmaceutical packaging and business eligibility certificates.
- Branch `feat/pharma-domestic-gmp-crawler-20261107` based on `feat/pharma-foreign-gmp-lookup-20261107` (not merged). `Modules/Pharma/Services/DavDomesticGmpCrawler.php` discovers article candidates on official category `cn92` by matching exact domestic-GMP slug and orders by article numeric ID, fetches latest article and classifies three Excel links by heading order. It never synthesizes date/news URLs and restricts outbound HTTPS to `dav.gov.vn`, without cookies, XSRF, disabled TLS or cross-host redirects.
- ClientPortal route `/apps/pharma/domestic-gmp` and download `/apps/pharma/domestic-gmp/download/{group}`, gated by existing DAV read permission, added only to Tra cứu hub and excluded from top-level dashboard. Downloads re-discover official article, reject non-XLSX signatures/oversized files and store content-addressed copies in private local storage; no database migration, no Medicine Master writes.
- **Known limitations**: Only first DAV category page is examined, newest article inferred from news ID, not independently validated as globally newest; HTTP fetch from real DAV has not been tested. Historical metadata/index is not persisted, so there is no automatic scheduled monitoring or automatic fallback when DAV is offline. File check currently uses ZIP header/size, not full XLSX internal integrity. Avoid claiming production-ready or fully automatic change tracking before these gates are addressed.
- Added focused `PharmaDomesticGmpCrawlerTest` and updated `PharmaLookupHubContractTest`; **tests not executed**. Checkpoint `git fetch origin && git switch feat/pharma-domestic-gmp-crawler-20261107 && git pull --ff-only`, then `php artisan test tests/Feature/ClientApps/PharmaDomesticGmpCrawlerTest.php tests/Feature/ClientApps/PharmaLookupHubContractTest.php`. If PASS, impacted PharmaForeignGmpLookupTest and UI desktop/mobile, live DAV downloads for all 3 files. Do not merge/deploy without approval.

---

## Foreign GMP public lookup — 2026-11-07

- User approved implementation after supplying public DAV GMP endpoint and representative Kern Pharma S.L. JSON. Branch `feat/pharma-foreign-gmp-lookup-20261107` is stacked on unmerged `feat/pharma-award-wishlist-detail-sync-20261107`; main was `36e43665a` when work began.
- `Modules/Pharma/Services/DavForeignGmpLookup.php` owns POST `getAllPublicServerPaging` with `filter`, empty object `thongTinCoSoGMPNuocNgoai`, skip/limit 15, cache 5 minutes, no browser cookies/XSRF tokens and TLS verification left enabled. Keeps `id` and `idCC` separate; maps name, address, GMP standard, certificate, authority, dates, publication batch and complete multiline scope. Distinguishes successful empty result from API failure.
- ClientPortal `PharmaGmpLookupController` offers authorized browse and source-verified detail via `/apps/pharma/gmp` and `/apps/pharma/gmp/{sourceId}`, using existing `client.pharma.dav-prices.view` permission and managed manifest presentation. Hub GMP card activated. Both focused screens hide global app header and mobile nav, preserve back route. Detail renders multiline scope safely via escaped Blade and whitespace-pre-wrap.
- Read-only; no DB migration, catalog/HSSP writes, Muasamcong dependency, merge or production changes. No real anonymous server API call verified yet; user must test API on their development environment. Focused `PharmaForeignGmpLookupTest` and updated `PharmaLookupHubContractTest` are committed but **not run**; focused → impacted tests → manual desktop/tablet/iPhone/PWA UI PASS required. If API rejects anonymous calls, stop and inspect read-only diagnostics, do not embed session secrets or disable TLS.
- Checkpoint: `git fetch origin && git switch feat/pharma-foreign-gmp-lookup-20261107 && git pull --ff-only`, then `php artisan test tests/Feature/ClientApps/PharmaForeignGmpLookupTest.php tests/Feature/ClientApps/PharmaLookupHubContractTest.php`. If PASS, run impacted DAV/traditional/award tests. Never merge/deploy without explicit approval.

---

## Wishlist detail and safe sync reuse — 2026-11-07

- Branch: `feat/pharma-award-wishlist-detail-sync-20261107`, based on unmerged `fix/pharma-award-group-filter-20261107`; main remains at `36e43665a` at implementation time.
- Wishlist eye icon now links to `GET /apps/pharma/award-prices/wishlist/{sourceId}`, guarded by existing ClientPortal Pharma auth/feature and view permission. Controller reads saved `AwardWishlist` strictly by authenticated `user_id + source_id`, checks snapshot identity, then reuses the existing Pharma award detail Blade. This view displays the saved snapshot even when public API is unavailable, shows explicit stale-data warning, and returns to Wishlist.
- Existing detail Blade displays the same `POST award-prices/sync/preview` button when `client.pharma.products.create` is granted and match is not `review`. The preview re-fetches selected IDs from the public source and refuses missing/invalid entries, then existing user-bound 10-minute token and confirmed transactional sync apply. No direct writes from Wishlist snapshot. No new migration or dependency on Muasamcong module.
- Focused `PharmaAwardSyncContractTest` extended for owner-scoped route, view reuse and sync revalidation. **Tests not executed by assistant; UI not confirmed.** Run focused then impacted regression; manual verify wishlist eye → detail → preview → confirm, stale-source failure, permissions, another user's wishlist 404, mobile layout. Do not merge/deploy without gates and user approval.

---

## UI filter regression fix — 2026-11-07

- User reported page-local filters do not hide results, requested removal of min/max price and numeric group options. Follow-up branch `fix/pharma-award-group-filter-20261107` from existing unmerged feature branch.
- Keep only name/ingredient/strength text and numeric medicine group filters. Normalize public group variants `N1`, `Nhom1`, `Nhóm 1`, `NHÓM1` into integer 1 (not 10). Group choices reflect groups on the current API page only; no source-wide filtering.
- JavaScript now sets both HTML hidden and explicit `display:none` for filtered table rows, updates visible row selection, preserves already checked sync rows, and uses a versioned JS URL to invalidate browser/PWA cached code. Wishlist and safe sync service unchanged.
- Contract test updated. **Not executed**; focused tests + manual browser UI and cross-module regression still required. No migration, deployment or merge.

---

## Checkpoint — Award price lookup UX and server wishlist — 2026-11-07

- Branch `feat/pharma-award-filters-server-wishlist-20261107` based on `feat/pharma-award-provisional-catalog-sync-20261107` (prior feature work still not merged into main). User approved server-persisted per-user wishlist and Pharma independence.
- Removed 4 summary KPI cards, replaced results columns with **Nơi trúng thầu** (public `tenCdtBmt`) and **Nhóm thuốc** (`nhomThuoc`), replaced detail text with eye icon and added real wishlist heart action. Source TBMT remains available in detail. Focused ClientPortal capability hides app header/mobile navigation.
- Browser-only filters for name + active ingredient + strength, price range and medicine group run over **current 20 API results**. No API search/filter request and no change to source total/pagination. Existing checked sync rows are preserved when filtering; select-all operates on visible rows. The existing bulk sync/preview logic is untouched.
- Independent Pharma persistence: additive `pharma_award_wishlists` table with unique `(user_id, source_id)`, `Modules/Pharma/Models/AwardWishlist.php`, `PublicAwardWishlistService`. Authenticated ClientPortal GET `/apps/pharma/award-prices/wishlist` and POST `/apps/pharma/award-prices/wishlist/toggle` are guarded by Pharma feature and existing view permission. Additions verify selected public API row; removal of saved entries works even when source disappears. Saved snapshot/keyword/page are per-account; never use Muasamcong models/services/tables/routes. Migration must be applied only in authorized local/dev environment, never auto-run on production.
- Focused contract test extended for route, module independence, filters, saved account UI and migration. **TEST NOT RUN; UI NOT VERIFIED**. Check migration with `php artisan migrate:status` and `php artisan migrate --pretend`, obtain approval before applying. Then focused tests, impacted tests, desktop/tablet/iPhone/PWA UI acceptance, wishlist add/remove/reload/user isolation, filters and bulk sync regression. No merge/deploy.

---

## Checkpoint — provisional tender medicine creation — 2026-11-07

- User explicitly approved creating a new Pharma catalog medicine when the public tender record has name and unit but registration is missing/unverified, e.g. Dibulaxan `893100307624`. Only a verified triple (normalized name + registration + unit) can match an unlinked existing medicine; different registration or unit is a new medicine. Multiple exact matches or source-binding conflicts still require manual review.
- `PublicAwardMedicineSync::inspect()` now returns `new` with `registration_verified=false` for unverified registration; missing name/unit remains `review`. Existing `public_tender/award_price/source_record_key` links are checked first for repeat sync and consistency. New catalog medicine is `tan_duoc`, `profile_status=needs_review`, and **official `registration_number` is NULL** when unverified. Original `gdklh_GPNK` is retained in source metadata as `award` and `registration_raw_unverified`, with `requires_catalog_review=true`; price observations remain in MedicineSource, not declared/selling price or stock.
- Detail and preview explain provisional status and require existing explicit POST confirmation; permissions, source verification, DB transaction and per-identity cache lock remain. No new migration, .env or production operation.
- Contract test updated; **NOT RUN**. Focused tests, database integration/idempotency (including repeat source sync), cross-module regression and user UI acceptance pending. Avoid declaring PASS. Branch `feat/pharma-award-provisional-catalog-sync-20261107` stacked on earlier unmerged Pharma award feature branches. No merge/deploy.

---

## Award detail field parity fix — 2026-11-07

- Compared read-only with `Modules/ClientPortal/resources/views/applications/muasamcong/drug-pricing-detail.blade.php` on main. Pharma had wrong keys for investor and dates: now `tenCdtBmt`, `ngayBanHanhQuyetDinh`, `ngayDangTaiKqlcnt`. `winningCode` is an array in source and must be joined to show bidder code instead of `—`.
- Only Pharma detail Blade and focused contract test changed. Source adapter, catalog matching, preview/sync and Muasamcong are unchanged. Await focused test and UI regression confirmation; do not mark PASS yet.

---
## Checkpoint — Pharma award detail and per-record sync — 2026-11-07

- User requested Muasamcong-style dedicated detail page with a sync button inside. Implemented GET `/apps/pharma/award-prices/{sourceId}?keyword=...&page=...` (Pharma feature guard, web auth); source row is re-fetched/verified against keyword and page, not trusted from URL. Search "Xem chi tiết" links to this independent Pharma page.
- Detail displays structured public tender fields, winning organization, registration/GPNK, and explicit matching status. When status existing/new and user has `client.pharma.products.create`, button posts `selected[]` to existing safe preview route, then requires final confirmation. Review-only (missing/invalid registration or ambiguous match) never offers sync. No Muasamcong queue or module dependency.
- Focused contract test updated for detail route and form. Tests and UI not run; verify live source data, preview and safe blocking, mobile, and unchanged bulk sync. No merge or production changes. Branch `feat/pharma-award-detail-sync-20261107` stacked on prior unmerged branches.

---

## Checkpoint — Award lookup UI interaction fixes — 2026-11-06

- User reported: no clear-search X, filter placement, detail button inert, sync preview returns `The selected field is required.` Screenshot verified.
- Root cause: Blade Alpine expressions and dynamically generated hidden selection inputs were not functioning; `selected[]` was not submitted. Replaced with actual named checkbox controls linked to preview form via HTML `form` attribute. Added tiny dependency-free JS for clear X, select-all, count and empty selection validation. Detail is a native `<details>` disclosure, no Alpine runtime dependency. Filter is a native disclosure after successful search; no longer in search form.
- This fixes UI interaction plumbing only. Missing or invalid `gdklh_GPNK` remains **review-only** by Option A; no bypass or silent product creation. Actual DB sync, UI click and JS runtime not yet tested. Need focused tests and user UI confirmation before any merge.
- Branch `fix/pharma-award-interactions-20261106` stacked on unmerged feature parent. No production changes.

---

## Checkpoint — Pharma public tender pricing parity and guarded catalog sync — 2026-11-06

- User chose **Option A**: missing/invalid registration or ambiguous matching is review-only, never automatically matched/created. Source field `gdklh_GPNK` may be GPNK rather than registration; conservative validation intentionally blocks questionable values.
- Pharma independent award page now has table, per-page statistics, collapsible unit filter, 20/page navigation, refresh, details modal, multi-select and confirmed sync preview. No wishlist. Advanced Muasamcong search fallback, global filters and exports **not fully replicated**; do not claim literal 100% parity.
- Pharma-owned `PublicAwardMedicineSync` uses normalized name + registration + unit. Existing medicine: upsert `pharma_medicine_sources` observation (unique public_tender/award_price/source ID), never change master fields. New medicine: only after explicit confirmation, type tan_duoc, needs_review, no declared_price/selling_price writes. Source observations preserve each award and price in JSON metadata. Ambiguous, missing registration, conflicting source binding => validation stop.
- Preview selects only server-fetched IDs (not client payload), stores per-user expiring token for 10 minutes, rechecks identity on commit, wraps batch in DB transaction and uses identity-scoped cache locks. Both POST routes use Pharma feature guard; controller requires `client.pharma.products.create` in addition to view. No Muasamcong module classes or config.
- Focused contract tests written, **not executed**. Must verify PHP syntax, focused tests, actual source registration availability, page statistics and detailed modal, preview + confirmed create/update, rollback on partial failure, repeated sync idempotency, concurrency, mobile/tablet UI, and Muasamcong disabled in isolated test. UI PASS not yet obtained.
- Branch `feat/pharma-award-full-ui-safe-sync-20261106` stacked on earlier unmerged feature branches. No migrations, .env changes, merge or production deploy.

---

## Checkpoint — Independent Pharma tender award lookup — 2026-11-06

- Replaces the earlier cross-app link: `/apps/pharma/award-prices` is owned by Pharma and ClientPortal Pharma. It works without Muasamcong app registration, feature permissions, routes, models, services or configuration; no migration or .env changes.
- `Modules/Pharma/Services/PublicAwardPriceLookup.php` calls the public `muasamcong.mof.gov.vn` pricing endpoint directly with explicit allowlisted URL, SSL verification, 5-minute cache, and fixed 20-row first-page read-only search. The source is the public tender portal; **not** the Muasamcong module. This narrow adapter intentionally does not replicate Muasamcong's extended search, saved snapshots, syncing, wishlist, export, or complex fallback strategies. Source API compatibility and first-page limitation require UI verification.
- `PharmaAwardPriceController`, Pharma feature manifest and guarded route, standalone Blade search/results; Pharma lookup hub now links internally. GMP placeholder unchanged. Existing Muasamcong feature untouched. Existing `client.pharma.dav-prices.view` permission reused.
- Focused tests added, **not executed**. Need check live API shape, permission, responsive UI, and behavior when `modules.registry.Muasamcong.enabled=false` in isolated test context (do not alter production config). No UI PASS claimed. Branch `feat/pharma-independent-award-lookup-20261106` stacked on earlier unmerged branches; do not merge/deploy without approval.

---

## Checkpoint — Pharma hub links Muasamcong award pricing — 2026-11-06

- User reported UI PASS for traditional lookup. Enabled `Tra cứu thuốc trúng thầu` card in `/apps/pharma/lookups` to navigate to the existing `client.muasamcong.drug-pricing` route; no duplicate search/sync implementation or new route.
- Hub checks Muasamcong application and `drug-pricing` feature permissions before rendering an active link; otherwise displays a disabled card. Target route retains its own auth and feature middleware. GMP remains a placeholder.
- Feature branch `feat/pharma-award-lookup-link-20261106` stacked on unmerged traditional-unit branch; parent branch integration required before PR/merge.
- Focused tests updated but **not run**; manual cross-app UI access/denial not yet confirmed. No merge/deploy.

---

## Checkpoint — MOH Đông dược unit-aware identity — 2026-11-06

- User confirmed search UI PASS after scoped MOH TLS workaround; import UI not yet revalidated.
- New `MohTraditionalMedicineMatcher` isolates matching from DAV: normalized name + registration + ĐVT. Same triple existing => existing; duplicate triple => ambiguous; same triple with packaging mismatch or non-traditional type => review. Same name+registration with different ĐVT => eligible as distinct Medicine; missing identity => incomplete. Partial overlaps remain review.
- ClientPortal import uses unit-aware matcher at list/preview/locked store, lock includes ĐVT, only blocks different package/price within the **same** name+registration+unit in current API page. Other pages may require manual source reconciliation; do not silently overwrite existing.
- Import explicitly persists `product_type=Medicine::PRODUCT_TYPE_TRADITIONAL` (`dong_duoc`), and `giaBanBuon` to `declared_price` only; no `selling_price` writes, migrations, or global DAV matcher changes.
- Focused tests added for same-unit conflict/different-unit allowance and static integration contract. Runtime DB persistence, broader regression and UI import confirmation **not yet executed**.
- Branch `feat/pharma-traditional-unit-identity-20261106` stacked on unmerged traditional lookup parent. No merge/deploy.

---

## MOH YDCT TLS incident — 2026-11-06

- Operator reproduced OpenSSL 3.0.13 `dh key too small` with Python and PHP cURL errno 35 (HTTP 0). `curl --ciphers 'DEFAULT@SECLEVEL=1'` returned HTTPS 200 with SSL verify result 0; POST returned JSON with success=true, totalCount=2 for `cold wind` without cookies/XSRF tokens.
- Adapter-only fix: `MohTraditionalPriceLookup` sets Guzzle cURL option `CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1'`, `verify => true` for this MOH request only. **Security tradeoff:** weaker accepted DH parameters for this legacy endpoint. No global OpenSSL changes, no `verify=false`, no credentials. Review removal when remote TLS is fixed.
- Focused test adds TLS source contract; **no runtime PHP tests or UI smoke executed by assistant**. Operator must verify PHP HTTP transport honors cURL option and confirm UI real search; if error persists inspect logs/status before further change.

---

## Traditional medicine public price lookup — checkpoint 2026-11-06

- Branch `feat/pharma-traditional-price-lookup-20261106` is stacked on `feat/pharma-lookup-hub-20261106` (itself stacked on unmerged DAV feature). Resolve parent dependencies before PR/merge; main was `36e43665a` at branch creation.
- Public MOH YDCT POST `/api/services/app/quanLyGiaThuoc/GetListCongBoPublicPaging` via Pharma-owned `MohTraditionalPriceLookup` with 15/page, bounded timeout, five-minute cache, validated response. Never send copied browser session cookies, XSRF token or anti-forgery credentials. **Live server-to-server access is unverified**; HTTP 403/419 or anti-forgery challenge may require a separate safe integration design.
- ClientPortal routes `/apps/pharma/traditional-prices`, `/import` GET/POST use existing DAV lookup permission plus products.create for imports; UI hub activates đông dược card. `Medicine::PRODUCT_TYPE_TRADITIONAL`, `giaBanBuon` -> `declared_price`, `thanhPhan` -> active_ingredients (first 255 chars; needs business review for longer formulae), never writes selling_price.
- Identity check uses existing name+registration matcher and source-id re-fetch. If multiple packaging/unit/price rows for the same name+registration are visible on the current page, auto-import is blocked pending explicit SKU/variant design. This guard is **page-scoped**, not a guarantee against duplicates on other pages; manual verification is required. Existing medicine prices are never overwritten.
- Focused test `php artisan test tests/Feature/ClientApps/PharmaTraditionalPriceLookupTest.php`; then DAV/lookup hub focused tests and impacted ClientPortal/Pharma regression. No tests run by assistant, UI import not verified. No migration, merge or deploy.

---

## DAV price mapping clarification — 2026-11-06

- Business rule confirmed: DAV `giaBanBuon` maps directly to Medicine Master `declared_price` (nullable). Do not substitute `giaBanLe`; do not populate `selling_price`.
- `MedicineService::storeWithDeclaredPriceOnly()` reuses canonical creation/quality/transaction logic but intentionally skips legacy `normalizeSellingPrice()` so a pending `selling_price` schema migration is not required for DAV import. Normal `store()`/`update()` remain unchanged.
- Focused test added for declared-only persistence; tests and UI not yet verified. No migration, production change, or main merge.

---

## DAV → Medicine Master import checkpoint (2026-11-06)

- Added DAV dosage-form/country mapping and visible card fields. Each card inspects normalized medicine name + registration against Medicine Master.
- New candidate: explicit preview → CSRF-protected confirmation → re-fetch source → identity recheck under cache lock → MedicineService::store. Existing/ambiguous/incomplete candidates cannot auto-create. Import requires both `client.pharma.dav-prices.view` and `client.pharma.products.create`.
- No DAV public price is written into internal declared/selling prices. Existing Medicine Master records are not overwritten. Missing catalog metadata must be reviewed after import.
- Known validation gate: check matcher normalization coverage, duplicate concurrency under actual cache backend, existing product routes, permission behavior, source ID stability and real DAV response. Review import field truncation for packaging and complete SKU/package creation; this initial batch creates only a Medicine Master record.
- Focused: `php artisan test tests/Feature/ClientApps/PharmaDavPublicPriceLookupTest.php tests/Feature/ClientApps/PharmaDavImportContractTest.php`; run impacted ClientPortal/Pharma regression only after focused PASS.
- Tests NOT EXECUTED by assistant; UI import NOT VERIFIED; no merge or deployment.

---

## Checkpoint — DAV public price lookup — 2026-11-06

- Branch: `feat/pharma-dav-price-lookup-20261106`. Read-only DAV search in Pharma domain service and thin ClientPortal PWA adapter. No database writes, migrations, .env edits, production operations, or merge.
- GET `/apps/pharma/dav-prices`, web permission `client.pharma.dav-prices.view`, managed feature copy and focused PWA shell. Permission must be granted using existing Client Apps Admin management.
- DAV endpoint/payload are provisional and MUST be validated against real source responses. HTTP failures, XSRF/session requirements, and field mappings must be checked in development before declaring live search successful. Never bypass CAPTCHA or access restrictions.
- Focused: `php artisan test tests/Feature/ClientApps/PharmaDavPublicPriceLookupTest.php`; after PASS, impacted ClientPortal regression: `php artisan test tests/Feature/ClientApps/ClientApplicationAdminTest.php tests/Feature/ClientApps/PharmaProductsCapabilityTest.php`.
- Automated tests NOT RUN by assistant. Real DAV connectivity and UI NOT VERIFIED. UI PASS requires user confirmation on desktop and iPhone. No merge/deploy.

---

## Checkpoint — Issue PDF posted/deferred supply separation — 2026-11-03

- Branch: `feat/pharma-price-list-manual-lots-20261103`; no merge/deployment or runtime tests.
- Issue #24 posted with Anvo lot LC82822 98 + LC82835 102; Nereid item quantity 0 has pending deferred supply 100 (2026-12-01, note "thiếu hàng"). Existing PDF incorrectly printed the zero-quantity item as a posted row.
- Integrated `InventoryIssueDocumentLines` into `InventoryIssueDocumentService` and PDF Blade. Posted section uses matched negative issue inventory movements, excluding zero-quantity items; pending supply is shown separately when `show_deferred_supply` is enabled. Posted totals/count exclude pending supply.
- Integrated `show_deferred_supply` model cast/fillable/default, controller checkbox persistence and admin settings UI. Existing additive migration `2026_11_03_000001_add_deferred_supply_toggle_to_issue_document_settings.php` is required before using updated settings code on a local database. **Do not run on production without the production procedure and explicit approval.**
- PDF hash includes pending supply snapshot and bumped template version to v3; re-generating the PDF through the existing generate action replaces the stale document. Existing share links may be revoked by the service when source hash changes.
- Focused contract test added to `PharmaPriceListManualLotsContractTest`. **NOT RUN**. Run focused tests first, then impacted regression; user must confirm UI PDF PASS.
- Check local migration status read-only with `php artisan migrate:status` before applying any migration; back up database and confirm environment. Do not re-post issue #24. Review tracked working tree and PR; merge only with user approval.

---

## Checkpoint — Shared FEFO capacity across split issue rows — 2026-11-03

- Branch: `feat/pharma-price-list-manual-lots-20261103`. No main merge, deployment, migration or runtime test performed.
- Root cause: ClientPortal order-lots Blade initialized FEFO suggestions separately for each issue item, causing duplicate use of the same available lot (issue #24: LC82822 98 + 98 against stock 98).
- Fix: Blade tracks remaining available units per inventory balance ID across all postable rows; each suggested allocation consumes that shared remainder. Browser-side validation also aggregates quantities per balance ID across rows and disables submit when a lot is oversubscribed. Existing Pharma posting service remains the authoritative transaction/lock/lot-total validator.
- Focused regression: `tests/Feature/ClientApps/PharmaPriceListManualLotsContractTest.php` expanded to cover shared FEFO and aggregate UI validation. Tests **NOT RUN**; UI **PENDING**. Validate issue #24 expected defaults LC82822:98/0, LC82835:0/102 only if order remains approved and unposted.
- Earlier PDF deferred-supply integration remains incomplete. Do not run pending migration. Do not merge main without user approval.

---

## Checkpoint — Repeat approval of lot-split price-list orders — 2026-11-03

- Branch: `feat/pharma-price-list-manual-lots-20261103`; no main merge or deployment.
- Root cause: partial approval previously rejected duplicate medicine IDs after manual lot splitting (issue #24 has two Anvo rows, 98 + 102). Repeated medicine-level pending supply was also counted on every split row in readiness.
- Fix: readiness assigns medicine-level pending deferred quantity only to the first corresponding row. Partial approval validates item IDs and nonnegative integers, groups lines by medicine, enforces cumulative approved quantity against warehouse stock, preserves split item IDs/batch metadata, and writes one pending deferred supply record per medicine. Existing deferred date/note remain as fallback.
- Limitations: manual UI regression still required, especially decreasing a split medicine's aggregate approved quantity and entering new supply metadata; this path may require follow-up UI enhancements. Do not approve/repost issue #24 until tests and state checks pass. No automatic stock mutation or data migration.
- Focused contract test updated in `PharmaOrderApprovalCapabilityTest`; **NOT RUN** on Admin runtime. Run focused tests before impacted regression. UI **PENDING**.
- Earlier PDF deferred-supply integration remains incomplete; do not run its migration until PDF implementation is finished.
- Gate: git pull --ff-only, focused test, impacted regression, user UI PASS, tracked working tree check; no main merge without explicit consent.

---

## Checkpoint — Repeat approval and pending deferred supply — 2026-11-03

- Branch: `feat/pharma-price-list-manual-lots-20261103`; no merge/deployment.
- Issue #24 was reported as pending_approval after prior partial approval: Nereid issue line quantity 0, pending deferred supply 100 (2026-12-01, note "thiếu hàng"); two Anvo lines 98 and 102 are split by lots. Existing readiness treated item quantity 0 as original demand and hid deferred-supply controls.
- Pharma-owned readiness now restores demand from pending deferred supply per medicine/allocation and recomputes shortage/ready flags. Price-list partial approval also uses item quantity plus existing deferred quantity and preserves existing expected date/note when resubmitted.
- **Known guard:** price-list repeat approval currently rejects multiple issue rows for the same medicine; issue #24 has two Anvo lot rows, so it must not be used for a repeat approval until the lot-split reconciliation workflow is designed/tested. Do not delete, consolidate, or re-post lot rows automatically.
- Focused source-contract regression added to `PharmaOrderApprovalCapabilityTest`. Runtime tests **NOT RUN**, UI **NOT VERIFIED**. Run focused test first, then impacted ClientPortal Pharma regression only after PASS.
- Two earlier PDF deferred-supply integration commits exist on this branch but are **incomplete** (model/controller/Blade not integrated). Do not run the pending migration until PDF integration is complete and reviewed.
- Pending: determine safe handling of lot-split rows when undoing approval; validate UI on an unposted single-row price-list order with deferred supply; do not merge main without explicit user approval.

---

## Checkpoint — Pharma stock card + issue fulfillment closeout — 2026-10-05

- Branch: `refactor/clientportal-pharma-inventory-stock-card`; compared with `main` before handoff: ahead 36, behind 0.
- PWA inventory balance detail is now a medicine-level **Thẻ kho** across all tracked lots/expiry dates, with current total stock, lot cards, total inbound/outbound quantities, and a ledger limited to effective stock movements (opening balance and posted receipt/issue movements; reversal noise is not presented as a completed movement).
- Multi-lot issue fulfillment is supported: one requested medicine quantity may be split across multiple lot/expiry lines while preserving the commercial total. Admin posting readiness is exposed only when every issue line has a selected lot/expiry and sufficient canonical stock.
- Stock-card issue movements preserve each posted lot allocation, so a 116-unit issue split 16 + 100 is represented as two ledger rows and reconciles to the medicine total.
- PWA stock-card PDF/print is generated through the Pharma-owned `InventoryStockCardDocumentService`; ClientPortal remains a thin adapter and follows the external-file handoff contract.
- PWA order/issue list now permits permanent deletion only for non-posted `DRAFT` or `REJECTED` documents. The server rechecks status/ownership under lock; related deferred supplies/items are removed without mutating inventory balances or ledger. Posted/pending/approved documents remain protected.
- UI acceptance: **PASS**, including stock-card reconciliation, multi-lot issue posting, stock-card totals/PDF presentation, and Draft/Rejected delete actions.
- Focused impacted regression: **PASS — 15 tests, 300 assertions** across `PharmaInventoryCapabilityTest` + `PharmaOrderApprovalCapabilityTest`.
- Pharma module regression: **PASS — 217 tests, 2493 assertions**.
- Full ClientApps/full-project regression: **NOT APPLICABLE — module-scoped regression strategy**; only the demonstrably impacted ClientPortal Pharma capability tests were run.
- Schema migration: none introduced by this batch.
- Production boundary: normal deployment/cache lifecycle; no new feature flag or runtime enablement step.
- Remaining gate: synchronize this handoff locally, verify tracked working tree clean, then create/review PR. Do not merge without explicit approval.
- Status: **IMPLEMENTATION COMPLETE — TEST PASS + UI PASS — HANDOFF/CLEAN/PR GATE.**

---

## Checkpoint — Inventory sales + commission operations closeout — 2026-09-27

- Branch: `fix/pharma-price-list-activation-identity-overlap`.
- Scope: Pharma Price List activation identity, Inventory issue sales workflow, bid-sale issue workflow, Pharma operations dashboard, and Commission Center UX/exports.
- Inventory issue list now supports manager/customer searchable filters, current-month date scope, selected-row Excel export, stable manager resolution for normal and bid issues, and professional full-width Admin presentation.
- Draft issue edit locks the original Price List and restricts the responsible User to Users actually assigned to that Price List; backend validation prevents request bypass.
- Commission Center supports both Price List and bid sources, resolves customer/hospital from commission partner or posted issue snapshot, uses searchable manager/customer/product filters, selected-row Excel export, full-width detail layout, and date-only posting display.
- Canonical Admin searchable select is `<x-select-search>`. `<x-search-select>` is not a registered project component and must not be used; this rule is now documented in `.codex/standards/ADMIN_UI_STANDARD.md`.
- Operator acceptance: latest focused `Modules/Pharma/Tests/Unit/InventoryContractTest.php` PASS and rendered UI PASS for the completed Inventory/Commission screens.
- No new migration is required by the final UI/contract closeout changes.
- Full-project regression: NOT APPLICABLE by default; changes are Module-scoped. Final Pharma module regression plus any demonstrably impacted tests remain the merge gate.
- Remaining pre-merge gate: pull this documentation closeout, run final Pharma regression, verify working tree clean, then create/review PR. Refresh this handoff if PR review changes implementation before merge.
- Status: **IMPLEMENTATION COMPLETE — FOCUSED TEST PASS + UI PASS — PRE-MERGE REGRESSION/CLEAN/PR GATES REMAIN.**

---

## Checkpoint — Pharma Inventory MVP — 2026-09-23

- Branch: `feat/pharma-inventory`, based on current `main`.
- Scope is owned entirely by `Modules/Pharma`; no inventory Module outside Pharma is referenced.
- Canonical product identity is `pharma_medicines.id`; UI displays Medicine Master `medicine_code`. No duplicate Pharma product master was introduced.
- New route root: `/admin/pharma/inventory`, linked from Pharma Dashboard.
- Persistence: warehouse, receipt/items, issue/items, batch/expiry balance and immutable movement ledger. Composite balance identity is warehouse + medicine + batch + expiry.
- Default warehouse is materialized as `MAIN / Kho chính`; schema remains multi-warehouse-ready.
- Opening balance is an explicit ledger movement and cannot be entered twice after the lot has stock activity.
- Receipt/issue documents are created as DRAFT. Stock changes only when an edit-authorized operator posts the document.
- Posting uses DB transactions + row locks. Issue posting rejects negative stock.
- Issue UI only offers balances with positive stock and orders them by expiry date, supporting FEFO-oriented selection without forcing automatic allocation.
- Receipt captures batch, expiry, quantity, unit price excluding VAT and VAT rate.
- Permissions reuse existing `view_pharma`, `create_pharma`, `edit_pharma` conventions; no new permission namespace was invented.
- Migration: `2026_09_23_110000_create_pharma_inventory_tables.php`; operator must run `php artisan migrate`.
- Added focused contract test: `Modules/Pharma/Tests/Unit/InventoryContractTest.php`.
- Automated PHP tests have NOT been run in the GitHub implementation environment; operator pull/migrate/focused test + Pharma module regression are the next gate.
- Manual UI acceptance remains pending for inventory list, opening balance, receipt draft/post, issue draft/post and negative-stock validation.
- Full-project regression: NOT APPLICABLE at this checkpoint; change is Module-scoped.
- Status: **IMPLEMENTATION COMPLETE — OPERATOR MIGRATION / TEST / UI ACCEPTANCE PENDING.**

---

## Checkpoint — Supplier commercial workspace + Drug Bid Award closeout — 2026-09-22

- Branch: `feat/pharma-supplier-commercial-workspace`.
- Operator acceptance: latest focused tests PASS and UI PASS.
- Drug Bid Award KQLCNT synchronization is scoped to contractor code `vn0314492345` using normalized contractor-code comparison; pagination/has-more uses the same scope.
- TBMT list supports permission-aware, confirmed deletion of a complete result group rather than deleting only a grouped representative row.
- KQLCNT projection identity now preserves each `kqlcnt_award_item` source identity so distinct winning products in the same TBMT/contractor cannot collapse into one Pharma award. This corrects the observed two-source/one-award collision for TBMT `IB2500361132`.
- Drug Bid Award legal/TBMT edit validates only TBMT legal fields; hidden/product-edit fields no longer block `Lưu thông tin TBMT`.
- Drug Bid Award direct Excel export and two-sheet product/allocation XLSX round-trip were accepted earlier in this branch; allocation import continues to pass through the existing allocation service/business rules.
- Shared distribution scope, product/allocation workspace, commercial policy, manager assignment, allocation/detail XLSX and grouped TBMT index/edit UX are included in this branch's accepted implementation.
- UI reliability lessons were promoted to `.codex/standards/ADMIN_UI_STANDARD.md`, especially Livewire checkbox route ownership, stable representative IDs, duplicate desktop/mobile DOM trees, server-state versus native checked state, pagination/filter carry-over diagnosis, page-scoped versus dataset-wide selection and bounded `wire:replace` escalation.
- Schema: no new migration is required by the final checkbox/sync/projection/edit fixes. Existing branch migrations were already applied during implementation.
- Production boundary: normal deployment/cache lifecycle; no new feature flag or runtime enablement step introduced by the closeout fixes.
- Remaining pre-merge gate: synchronize this documentation commit locally, run the final Pharma module-scoped regression selected for this branch, verify working tree clean, then create/review/merge the PR.
- Full-project regression: NOT APPLICABLE — changes are scoped to Pharma/Muasamcong integration and Pharma Admin UI; module/impacted regression is the merge gate.
- Status: **IMPLEMENTATION COMPLETE — FOCUSED TEST PASS + UI PASS — FINAL MODULE REGRESSION / GIT-CLEAN / PR GATE PENDING.**

---

## Checkpoint — Price List manager/time filters + Excel recipient/export refinements — 2026-09-20

- Branch: `feat/pharma-price-list-manager-time-filters`; based on merged `main` from PR #214. Pre-handoff compare: ahead 26, behind 0.
- Price List index now exposes the assigned `Người phụ trách` column and manager filter. Manager relation is eager-loaded and the filter options are bounded to users actually assigned to Price Lists.
- Effective-date filtering is explicit: date inputs default visually to the current date, but querying occurs only after `Áp dụng`. `Xóa bộ lọc` clears active criteria. Effective-from/effective-to sorting supports ascending/descending direction.
- Price List workspace was widened toward the admin sidebar and the filter area was reorganized into a professional two-level desktop layout with responsive wrapping.
- Row actions use direct `Xem` / `Sửa` actions plus a compact `Thêm` menu. The menu uses Alpine `x-teleport="body"` with fixed viewport positioning so it opens downward outside the table's horizontal overflow clipping context.
- Excel Designer group `Bảng giá & khách hàng` now includes `Người phụ trách`.
- Excel `Khách hàng / loại` now resolves the actual customer source: Global → `Bảng giá chung`; Partner → partner name; Official Facility → facility name; missing customer identity → `Chưa chỉ định`.
- Excel footer date text no longer prepends a second `ngày...tháng...năm`; configured date text is emitted exactly once.
- Excel header recipient semantics: configured `Kính gửi` wins when non-empty; when blank, it falls back to the same resolved `Khách hàng / loại` label. The fallback is passed as the fifth `PriceListExcelDocumentLayout::header()` argument, preserving the integer `startRow` argument.
- Operator acceptance: UI PASS for widened layout, manager/time filters, sorting/reset controls, overflow-safe downward action menu, Excel Designer manager field, customer label export, footer date, and configured/fallback recipient behavior.
- Focused Price List regression: PASS — 23 tests / 365 assertions across `PriceListV2ContractTest`, `PriceListExcelExportContractTest`, `PriceListExportProfileContractTest`, and `PriceListBidProfessionalUiContractTest`.
- Operator working tree after acceptance: clean (`git status --short` returned no output).
- Schema migration: none in this batch.
- Full Pharma/project regression: not run for this follow-up; scope is Price List index/export.
- Production boundary: no schema migration or feature flag; normal application deployment/cache lifecycle only.
- Status: **IMPLEMENTATION + FOCUSED REGRESSION + UI/EXPORT ACCEPTANCE COMPLETE — READY FOR PR GATE; DO NOT MERGE UNTIL REVIEWED.**

---

## Checkpoint — Price List customer sources / lifecycle / export UX — 2026-09-20

- Branch: `feature/pharma-price-list-customer-sources`.
- Base: `main` at `2216671aef09b5cf6a0cc85798c9aef4ff557ddb`; final pre-PR compare: ahead 39, behind 0 before this handoff commit.
- Route ownership verified: `/admin/pharma/price-lists/create` renders `pharma.price-list.workspace` / `workspace-bid.blade.php`; contracts now target the runtime workspace rather than the legacy create view.
- Customer Price Lists support exactly one customer source: Partner Master or Pharma Official Facility source. Official Facility identity is persisted separately through `customer_source` + `official_facility_id`; facility IDs are never stored as Partner IDs.
- Customer lookup UI uses an Alpine + Livewire searchable combobox with a visible search input and independent Partner/Official Facility instances. Operator UI acceptance: PASS.
- Reusable Price List purposes can be added, renamed and deleted when unused; referenced purposes are protected. Operator UI acceptance: PASS.
- DRAFT and INACTIVE Price Lists are directly editable; editing INACTIVE preserves INACTIVE status. ACTIVE remains protected and should be cloned for editing. INACTIVE lists can be activated again through the existing guarded manager flow.
- Step 3 includes `Áp dụng tất cả` to enable/disable all selected price rows. Commercial money inputs display vi-VN thousand separators while server normalization persists numeric values.
- Price List index displays the correct Partner or Official Facility customer.
- Excel Designer/configuration is now a shared Price List management concern on `/admin/pharma/price-lists`. Price List detail replaces the configurator button with a `Mẫu bảng báo giá` profile combobox; selected/default profile is passed to the existing Excel export.
- Schema migration: `2026_09_20_124500_add_customer_source_to_price_lists.php`. Operator reported `php artisan migrate`: nothing pending after synchronization.
- Focused Price List acceptance: PASS, including `PriceListV2ContractTest`, `PriceListBidProfessionalUiContractTest`, and updated `PriceListExcelExportContractTest`.
- Manual UI acceptance: PASS for customer source switching/search, purpose management, DRAFT/INACTIVE editing, select-all pricing, formatted commercial prices, INACTIVE reactivation, shared Excel configuration and detail export-profile selection.
- Pharma module regression before final contract alignment: 136 passed / 2 failed (1095 assertions). One failure was an in-scope stale Excel export contract and was fixed; focused Price List regression then PASS. The remaining `MedicineCatalogImportUiContractTest` failure requiring literal `SKU hệ thống tự sinh` is a pre-existing/out-of-scope Medicine contract drift and was intentionally not masked by this Price List branch.
- Operator working tree after acceptance: clean (`git status --short` returned no output).
- Full-project regression: NOT RUN; module-scoped Pharma work.
- Production boundary: run migrations during normal deployment; no runtime feature-enable change is required.
- Status: **PRICE LIST IMPLEMENTATION + FOCUSED TEST + UI ACCEPTED — READY FOR PR REVIEW; DO NOT MERGE UNTIL PR GATE IS APPROVED.**

---

## Checkpoint — Price List pagination selection identity

- Branch: `fix/pharma-price-list-pagination-selection`.
- Base: `main` at `cf06ebca1381f7a1679f6c261479cb2255d7bd17`.
- Route: `/admin/pharma/price-lists/create`, step 2 (`Chọn thuốc`).
- Root cause: the route renders `pharma.price-list.workspace` / `workspace-bid.blade.php`, which contains separate desktop and mobile product checkbox trees without stable Livewire DOM identity. Earlier changes targeted the inherited legacy `create.blade.php` view, which this workspace does not render.
- Fix: both actual desktop and mobile catalog surfaces now replace their page children instead of morphing positional inputs, and use canonical variant/package keys for every row and checkbox. Checked state is rendered from server-owned `selectedRows`.
- Selection semantics remain unchanged: the header checkbox is page-scoped, while `Chọn tất cả kết quả` remains an explicit separate action.
- No schema migration and no Price List business-rule change.
- Static diff/whitespace validation: PASS (`git diff --check`).
- Automated tests were not run in the implementation workspace because PHP is unavailable there; operator focused/module tests and manual UI acceptance remain pending at this checkpoint.
- Operator Test 1 (`PriceListV2ContractTest`): PASS (inferred from progression to Test 2 under the agreed stop-on-failure gate).
- Initial Price List regression: 57 passed / 2 failed (596 assertions). Both failures were stale assertions in `PharmaPriceListPipelineTest`: one still required the removed legacy-workbook `analysisSummary()` helper; the other prohibited the accepted explicit `Chọn tất cả kết quả` action. The contract now asserts the current database-backed paginator and the distinct page/all-result selection controls, including stable catalog row identity. Re-run pending.
- Operator regression after aligning stale contracts: 59 passed (598 assertions).
- First manual UI re-check still reproduced positional checkbox carry-over. Row-level keys alone were insufficient for the checkbox DOM property, so the follow-up fix keys the complete catalog page body by current page + rendered row identities, keys each checkbox directly, and renders its checked state explicitly from `selectedRows`. Follow-up automated and UI acceptance pending.
- CLI/Tinker diagnosis confirmed page 1 and page 2 row identities are disjoint. Browser component inspection then identified the real owner as `pharma.price-list.workspace`, proving the earlier view target was incorrect. The actual desktop `tbody` and mobile list now use Livewire 3's canonical `wire:replace` directive so checkbox children are replaced on page changes instead of retaining positional input state.
- Final operator acceptance for pagination selection: automated tests PASS and UI PASS.
- Follow-up detail-view refinement (`/admin/pharma/price-lists/{priceList}`): removed duplicate page-level horizontal padding so the Admin shell width is used, shows only `circular_group` as `Nhóm thuốc theo thông tư`, shortens the package column, and reserves explicit wider columns for declared/company prices.
- Final operator acceptance: focused Price List tests PASS, Price List regression PASS, pagination-selection UI PASS, and detail-view width/column UI PASS.
- Full-project regression: NOT APPLICABLE — module-scoped Price List UI/Livewire change.
- Schema migration / production enablement: NOT APPLICABLE.
- Reusable lesson captured in `.codex/standards/ADMIN_UI_STANDARD.md`: route/view ownership verification, stable domain keys, duplicate desktop/mobile checkbox trees, state-vs-DOM diagnosis and bounded `wire:replace` escalation for Livewire pagination selection.

---

## Final acceptance — Official Facilities source regions / BHXH / XLSX round-trip

- Operator acceptance: focused tests PASS and UI PASS, including the exported workbook import flow.
- Final Pharma regression after aligning the BHXH source-first contract: **176 passed / 20 failed (1374 assertions)**.
- Compared with the immediately preceding regression (175 passed / 21 failed), the only Official Facilities failure was removed. Remaining 20 failures are documented pre-existing/out-of-scope Pharma baseline groups (Medicine UI/contracts, dashboard contracts, Drug Award SQLite test schema/import-export, Price List contracts).
- Branch review against `main`: branch is 37 commits ahead and 0 behind at closeout review; changes are scoped to Official Facilities/BHXH/source export-import plus this handoff, with the existing dashboard link adjustment.
- Official Facilities regression attributable to this branch at closeout: **0**.
- No schema migration was added by the final XLSX/source-first fixes.
- Ready for PR and merge to `main`.

---

## Regression gate — BHXH source-first contract alignment

- Full Pharma regression reported by operator before this fix: 175 passed / 21 failed (1367 assertions).
- Exactly one reported failure was in the current Official Facilities scope: `Modules/Pharma/Tests/Unit/BhxhFacilityLookupContractTest` still asserted the superseded literal `Không OCR / không bypass CAPTCHA`.
- Updated that contract to assert the new source-first behavior: cached source data can be viewed without CAPTCHA, while a fresh BHXH request still requires a human-entered CAPTCHA before the lookup request is submitted.
- No production behavior changed in this commit; test contract only.
- Remaining 20 reported failures are the pre-existing/out-of-scope Pharma baseline (Medicine/Drug Award/dashboard/Price List/test-schema groups) and must remain documented rather than being mixed into this Official Facilities refactor.
- Awaiting pull + focused BHXH/Official Facilities tests, then final Pharma regression. Expected regression gate if no new issue appears: 176 passed / 20 failed.

---

## Fix — Official Source workbook helper sheets

- Reproduced import incompatibility from the operator-provided workbook: the primary `Worksheet` contains the exported facility rows, while helper sheets such as `Nguon` and `Ma_dia_ban` do not carry the round-trip facility headings.
- `OfficialSourceFacilitiesImport` now implements `WithMultipleSheets` and `SkipsUnknownSheets`, explicitly importing only `Worksheet` and ignoring helper/reference sheets.
- Existing row validation and `(source, external_id)` identity rules remain unchanged.
- No schema migration.
- Added focused contract coverage for sheet scoping. Awaiting pull + focused test + re-import of the operator workbook.

---

## Checkpoint — Official Source smart XLSX export/import

- Source repository Export is always enabled.
- Export precedence: checked rows -> current filter result -> entire source repository when neither selection nor filters constrain the query.
- Current filter values are posted with the export form, so export is not limited to the current pagination page.
- Workbook remains native XLSX and now includes stable round-trip columns `Source`, `External ID`, `Source Province Code`, and `Source District Code` in addition to operator-facing columns.
- Added Import Excel modal for users with `import_pharma_official_facilities` permission; accepts xlsx/xls generated by this screen.
- Import identity is `(source, external_id)`; creates missing records and updates changed records in the Pharma source mirror only. It never writes Partner.
- Import reports created/updated/unchanged/error row counts and rejects workbooks missing required round-trip columns.
- No schema migration.
- Awaiting pull + focused contract test + export-all/filter/selection and import round-trip UI smoke.

---

## Checkpoint — BHXH source-first lookup UX

- Route `/admin/pharma/official-facilities/bhxh` now prefers the Pharma official source mirror before making a new BHXH lookup.
- Selecting a BHXH source partition or district reads active rows from `pharma_official_source_facilities` using source + source province code + optional source district code.
- Existing cached rows render immediately with count and last-sync timestamp; no CAPTCHA is required to view them.
- CAPTCHA is only validated client-side when the operator explicitly submits a fresh BHXH lookup. The action label becomes `Tra cứu lại BHXH` when cached data exists.
- Cached data never enables `Đồng bộ vùng này`; that action is enabled only by a successful fresh BHXH response/snapshot.
- Empty cache explains that CAPTCHA is needed for first lookup.
- No schema migration and no change to source identity or Partner boundary.
- Awaiting pull + focused contract test + UI smoke for cached whole-partition, cached district, and fresh refresh flow.

---

## UI refinement — Official Source compact filters + XLSX + numbered pagination

- Operator UI feedback: source repository layout works, but search should be shorter so reset stays on the filter row; export must be a native multi-column Excel workbook; pagination should expose page numbers.
- Search placeholder/width is compacted and `Xóa bộ lọc` occupies the final desktop grid slot when filters are active.
- Selected-record export now uses `maatwebsite/excel` and downloads a real `.xlsx` workbook with separate columns for source, CSKCB code/name, ERP business region, province/city, BHXH source partition/code, district/code, status and last sync time.
- Export remains selection-only and read-only with respect to Partner.
- Pagination remains white Admin UI and now renders current/nearby page numbers plus first/last page and previous/next controls.
- No schema migration.
- Awaiting focused contract test + UI/export smoke.

---

## Checkpoint — Official Source repository region/filter/export UX

- Route: `/admin/pharma/official-facilities/source`.
- Operator confirmed BHXH lookup UI PASS before this refinement.
- Source repository now displays ERP business region and filters by business region before province/source partition.
- Facility search continues through shared `<x-search>` and searches source ID/name/geography fields.
- Added page-scoped row checkboxes, select-all-current-page, and selected-record CSV export (maximum 500 IDs per request). Export does not write to Partner.
- Province and BHXH source-partition options are constrained by the selected ERP business region.
- Replaced framework pagination rendering with an explicit white Admin UI pagination surface to avoid dark pagination styling.
- No schema migration; business region remains derived from `BhxhProvinceCatalog`.
- Focused contract test extended in `tests/Feature/Pharma/OfficialFacilityRegionContractTest.php`.
- Previous broad Pharma regression baseline on this branch: 174 passed / 20 failed; failures are outside this Official Facilities scope and include pre-existing Medicine/PriceList/DrugAward/dashboard contracts. Focused Official Facilities test before this refinement: 2 passed / 10 assertions.
- Awaiting pull + focused test + source repository UI/export smoke.

---

## Checkpoint — Official Facilities admin integration + ERP business regions

- Branch: `refactor/pharma-official-facilities-regions`
- Base: `main` at `42510073c527ef46398d3143020116560a921d7f`.
- Pharma Dashboard `Cơ sở KCB chính thức` now enters the Pharma-owned source repository instead of dropping operators directly into file import.
- BHXH lookup adds an ERP business-region selector before province/city: Miền Bắc, Bắc Trung Bộ, Nam Trung Bộ, Tây Nguyên, Miền Đông / Đông Nam Bộ, Miền Tây / Tây Nam Bộ.
- Business regions are operator/reporting groupings only. BHXH source partition codes remain durable source identities and are not rewritten.
- Legacy BHXH geography labels remain classified so historical source partitions do not disappear from lookup.
- Successful sync messaging explicitly states the destination is the Pharma official source repository and that records are not written directly to Partner.
- Persistence boundary remains unchanged: `pharma_official_source_sync_batches` tracks sync runs and `pharma_official_source_facilities` stores source facilities; Partner remains a later reviewed/imported canonical boundary.
- Added focused contract coverage: `tests/Feature/Pharma/OfficialFacilityRegionContractTest.php`.
- No schema migration.
- Awaiting operator targeted test + Pharma impacted regression + desktop/mobile UI smoke.

---

## UI refinement — Medicine Catalog filters and columns

- Filter controls are rebalanced into one desktop row. `Xóa bộ lọc` occupies the final slot only when a select/per-page value differs from its default; free-text search remains independent.
- Reset restores HSSP, master quality, circular group, special-control and per-page selectors to defaults without clearing the search field.
- `Variant / SKU` is removed from desktop and mobile catalog presentation; SKU remains searchable and remains part of the underlying Medicine/Variant model.
- `Quy cách` receives additional table width so packaging text is readable after adding `Nhóm thuốc`.
- Existing HSSP delete protection and centered delete confirmation/result modals are retained; the mobile delete action now follows the same HSSP-disabled behavior.
- No schema/data migration.

---

## Checkpoint — Medicine Catalog group + guarded delete UX

- Branch: `fix/pharma-medicine-catalog-delete-ux`
- Based on the HSSP validity mapping branch so the previous approved Medicine/HSSP work is retained.
- Desktop catalog adds `Nhóm thuốc` from `circular_group`; mobile detail also exposes the same field.
- Medicine rows with any HSSP profile keep the Delete action visible but disabled/muted. Backend `MedicineService::delete()` remains the authoritative guard.
- Eligible deletes use an explicit centered confirmation modal; success and failure are surfaced in a centered result modal instead of relying only on flash banners.
- Medicine edit/create form adds a visible `← Quay về Danh mục thuốc chuẩn` action.
- No migration/schema change.

---

## Checkpoint — Medicine Master validity derived from HSSP

- Branch: `fix/pharma-medicine-hssp-validity-sync`
- Medicine edit now treats the current HSSP dossier as the preferred source for the two management validity fields when HSSP values exist.
- Mapping: HSSP `registration.metadata.effective_to` → Medicine Master `visa_validity_date` (Hiệu lực Visa).
- Mapping: HSSP `gmp.metadata.effective_to` → Medicine Master `gmp_certification_date` (GMP cơ sở sản xuất).
- Backward compatibility: if no HSSP dossier/value exists, the existing Medicine values remain the fallback.
- The generic Dossier engine remains unaware of Pharma semantics; mapping is isolated in `HsspMedicineValidityService`.
- No migration/schema change.

---

## HSSP Module Snapshot v2 checkpoint — 2026-09-19
- Fresh Pharma Snapshot v2 runtime capture verified HSSP graph: dossier=1, template=1, template_items=5, items=5, attachments=4. GMP/GPLH metadata and Google Drive attachment metadata are present in the ZIP.

- Pharma HSSP related snapshot graph now points at the actual `pharma_medicine_profiles` owner table.
- Invalid related graph declarations now fail backup instead of silently producing an apparently successful snapshot without Dossier/HSSP data.
- Fresh end-to-end Pharma snapshot verification remains required before PR/merge.

# Pharma Collaboration Handoff

## Current checkpoint — HSSP lifecycle delete + runtime Pharma queue

- Module: `Pharma` with reusable dossier core under `App\\Dossiers`
- Branch: `refactor/pharma-hssp-dossier-engine`
- Status: **IMPLEMENTATION COMPLETE — TEST PASS + UI PASS + QUEUE/DRIVE LIFECYCLE PASS — READY FOR PR/MERGE**
- Date: 2026-09-19

### Current implementation

- Runtime module registry publishes queue metadata only after ModuleStateResolver resolves actual enablement; `run-queue.sh` consumes the resolved registry. Operator verified Pharma is runtime-enabled and PM2 runs `default,admission-documents,pharma` with timeout 600 / tries 3.
- Generic dossier upload job now lives under `App\\Dossiers\\Jobs`; the reusable storage engine no longer imports a Pharma upload job. Pharma selects the named `pharma` queue at its adapter boundary.
- Dossier attachments persist Google Drive `remote_id` for deterministic lifecycle cleanup; legacy attachments without it fall back to scoped remote-path lookup.
- HSSP delete is explicit and queued. It removes managed Google Drive files, managed Local files, dossier/items/attachments and the MedicineProfile, while preserving the Medicine Master record.
- Cleanup is retry-safe: Drive 404/missing legacy paths are treated idempotently; failed cleanup leaves dossier/profile records available for retry instead of silently losing cleanup evidence.
- HSSP index includes a destructive confirmation modal explaining Local/Drive cleanup and Medicine preservation.

### Final acceptance — 2026-09-19

Operator verification:

```text
Pharma tests: 133 passed (1035 assertions)
Pint: PASS after accepted formatting sync
UI: PASS
DeleteHsspDossier: DONE on queue pharma
UploadDossierAttachmentToGoogleDrive: multiple uploads DONE on queue pharma
```

The Justone HSSP lifecycle was exercised end-to-end: delete through the confirmation UI, queued Local/Google Drive cleanup while preserving Medicine Master, recreate the product dossier, and queued Google Drive uploads through the generic dossier job. Runtime ModuleRegistry/PM2 discovery was also verified with Pharma enabled from runtime state and the `pharma` queue active.

---

# Pharma Collaboration Handoff

## Current checkpoint — HSSP reusable dossier engine implementation

- Module: `Pharma` with reusable core under `App\\Dossiers`
- Branch: `refactor/pharma-hssp-dossier-engine`
- Status: **IMPLEMENTED — AWAITING LOCAL MIGRATION / TARGETED TEST / UI ACCEPTANCE**
- Date: 2026-09-19
- Parent checkpoint: Medicine Master/GPLH refactor merged to `main` via PR #204.

### Implemented scope

- Generic dossier templates, template items, dossier instances, items and attachments.
- Reusable Blade component `<x-dossier.editor>` driven by template metadata schema.
- Pharma default HSSP template: GMP, GPLH decision, HDSD, label and product-change sections.
- GMP expiry and registration/GPLH expiry are required; per-item uploads remain optional.
- Operators can append custom dossier items with optional validity date and attachments.
- Optional combined/master files are supported.
- Attachments are local-first and mirror through the existing System Google Drive OAuth connection under the configured `Laravel-Backup` root.
- Attachment records retain checksum, local path, remote path and sync status.
- Existing HSSP MedicineProfile remains the Pharma owner; dossier engine does not create a second Medicine identity.
- HSSP create/edit now composes the reusable dossier editor.

### Acceptance pending

Run the new migration, focused HSSP dossier contract test, impacted System Google Drive upload contract test, then Pharma module regression appropriate to the changed HSSP boundary. Perform desktop/mobile UI smoke for create/edit, required validity validation, optional item upload, custom item, master upload and Drive sync status.

---

# Pharma Collaboration Handoff

## Current checkpoint — Medicine Master / GPLH integrity accepted

- Module: `Pharma`
- Implementation branch: `refactor/pharma-medicine-master-gplh-ui`
- Status: **IMPLEMENTATION COMPLETE — TARGETED TEST PASS + UI PASS — READY FOR PR/MERGE**
- Date: 2026-09-19
- Workflow: `docs/GITHUB_COLLABORATION_WORKFLOW.md`
- UI standard: `.codex/standards/ADMIN_UI_STANDARD.md`

## Accepted scope

Medicine Master now keeps GPLH/registration identity separate from the generated medicine code/SKU, preserves registration display values during edit, searches medicine aliases through the real alias schema, persists the complete owner Excel catalog metadata on import update, and guards canonical identity collisions with actionable feedback.

Price List v2 now supports guarded checkbox bulk deletion for removable DRAFT/INACTIVE lists.

Drug Bid Award review now supports guarded bulk unlink. Both single and bulk unlink clear the canonical match and the direct `pharma_drug_bid_awards.medicine_id / medicine_code` reference transactionally, returning `medicine_match_status` to unresolved. This prevents stale direct bid references from incorrectly blocking Medicine deletion.

Operator acceptance on 2026-09-19:

```text
Targeted tests: PASS
UI: PASS
Pint formatting synchronized on accepted files
```

Historical local stashes may remain on operator machines. Do not bulk-pop or bulk-drop them; inspect individually only if explicitly needed.

## Next authorized work after merge

Create a fresh branch for the approved HSSP refactor. Target architecture: reusable Document/Dossier Set engine with configurable table-of-contents templates, dynamic metadata fields, optional per-item attachments, optional combined/master file, version/history, and local ↔ Google Drive storage synchronization. Pharma owns the HSSP adapter and requires GMP validity plus registration/GPLH validity metadata before saving/confirming the product dossier. The reusable engine must not depend on Pharma and should be suitable for other modules.

---

# Pharma Collaboration Handoff

## Current checkpoint — Price List v2 + Excel Designer v3.2 accepted

- Module: `Pharma`
- Implementation branch: `feat/pharma-price-list-bid-ui-professional`
- Parent implementation branch: `feat/pharma-drug-bid-intelligence`
- Status: **IMPLEMENTATION COMPLETE — TEST PASS + UI PASS + PINT PASS — READY FOR PR/MERGE**
- Date: 2026-09-16
- Workflow: `docs/GITHUB_COLLABORATION_WORKFLOW.md`
- UI standard: `.codex/standards/ADMIN_UI_STANDARD.md`
- Reusable Excel standard: `.codex/standards/EXCEL_EXPORT_CONFIGURATION_STANDARD.md`

## Scope accepted

This branch completes the professional database-backed Pharma Price List workflow and the reusable Excel Designer reference implementation. The canonical product identity remains `Medicine -> MedicineVariant / SKU -> MedicinePackage`; Price List must use deterministic variant/package identity and must not silently merge ambiguous medicine identities.

Price List resolver precedence remains:

1. applicable ACTIVE Customer price;
2. applicable ACTIVE Global price;
3. otherwise `NO_PRICE` / null.

Bid prices are reference evidence only. They must never become a commercial-price fallback or silently mutate `company_sale_price`, `actual_receivable_price` or `invoice_price`.

## Price List v2 invariants

Price List v2 is database-backed through `pharma_price_lists`, `pharma_price_list_items`, reusable purposes and bid-evidence persistence. List types are `global` and `customer`; statuses are `draft`, `active`, `inactive`, `archived`.

Customer lists use Partner as the canonical customer master and may retain `manager_user_id`, `purpose_id` and `source_price_list_id`. Customer creation can initialize from an ACTIVE Global list.

After a Customer Draft is saved, persisted `pharma_price_list_items` are authoritative. The Global source becomes traceability/reference only. Edit must not silently re-add products the operator previously excluded.

Commercial UX accepted:

- company sale defaults from declared price;
- declared-price discount percentage applies to actual receivable price;
- Customer links to `/admin/partners`;
- save uses success feedback and returns through the Price List route;
- Price List delete is available with explicit confirmation;
- bid intelligence stays secondary to commercial pricing inputs.

## Bid evidence

`Muasamcong` owns procurement acquisition/source facts. `Pharma` owns canonical bid matching, bid intelligence and Price List evidence.

For each selected Variant/Package, the default proposal is the latest correctly matched award ordered by `decision_date DESC`, then `published_at DESC`, then `id DESC`. The operator can explicitly inspect history or choose another result.

When no matched award exists, the operator can add a reusable manual Pharma award. Manual confirmed matches must not be silently overwritten by later automatic synchronization.

Price List snapshots the selected bid reference when the item is saved. Editing a Draft preserves its captured evidence unless the operator explicitly selects another historical result. Saving never copies a winning bid price into commercial price fields.

## Excel Designer v3.2 accepted reference implementation

The Price List export uses persistent per-admin profiles and supports:

- profile create, duplicate, set-default and delete;
- server-side JSON library plus local JSON import;
- branding, selected columns and page setup sections;
- searchable/grouped data library;
- explicit Excel column order;
- isolated per-column Inspector draft state;
- custom header, width, alignment, datatype and decimals;
- Times New Roman typography and configurable table styling;
- orientation, paper size, margins, centering and scaling;
- logo/signature preview and configurable dimensions;
- signing location, full `Ngày tháng năm`, signatory title and signatory name.

Export semantics remain: when detail-table checkboxes contain item IDs, export only those items; when none are selected, export every item in the Price List.

### JSON/media contract

Profile JSON is portable configuration and must not blindly embed binary media. Logo may be recovered in the appropriate profile/user scope. Signature recovery is identity-sensitive and must match normalized `signatory_title + signatory_name`; it must never fall back to another person's signature.

An explicit signing date restored from JSON/profile must be preserved. The current date is only the default when that field is empty. Logo/signature custom dimensions remain profile-scoped.

### Runtime regression rules

The final accepted Designer establishes these mandatory rules:

- imported/duplicated profile names are collision-safe under `(user_id, name)` uniqueness;
- destructive actions capture the exact target before confirmation;
- Livewire confirmation state uses `pendingConfirmAction` and `pendingConfirmValue`;
- the execution method is `executeConfirmedAction()`;
- a Livewire public property and public action method must never share the same name;
- profile delete and JSON delete require explicit confirmation;
- static tests alone are insufficient for destructive UI interactions: browser UI acceptance is mandatory.

The reusable implementation standard is `.codex/standards/EXCEL_EXPORT_CONFIGURATION_STANDARD.md`. Other modules should implement that standard within their own ownership boundary. They may use Pharma v3.2 as a reference but must not introduce a hard dependency on Pharma solely to reuse the Designer.

## Final acceptance — 2026-09-16

Operator verification after final Pint formatting:

```text
Tests: 14 passed (126 assertions)
Pint: PASS — 6 files
UI: PASS
Working tree: clean and synchronized with origin before this handoff commit
```

Focused regression suite:

```text
Modules/Pharma/Tests/Unit/PriceListExportProfileRuntimeRegressionContractTest.php
Modules/Pharma/Tests/Unit/PriceListExcelJsonMediaRoundTripContractTest.php
Modules/Pharma/Tests/Unit/PriceListExcelDesignerV3ContractTest.php
Modules/Pharma/Tests/Unit/PriceListExcelMediaSizingContractTest.php
```

Final formatting commit before this handoff: `36b9ee29 style(pharma): finalize price list excel designer formatting`.

Frontend note: an earlier `npm run build` attempt failed because Vite/Rollup could not resolve an import. This closeout does **not** claim frontend build PASS; that issue is outside this targeted Pharma acceptance checkpoint.

## Local stash safety

Historical stashes remain on the operator machine from earlier synchronization checkpoints. Do not bulk-pop or bulk-drop them during this merge. The closeout working tree was clean without restoring those historical patches. Inspect any stash individually only when explicitly needed; unrelated Invoices stashes remain untouched.

## Deferred scope

- automatic downstream Sales/Inventory/Invoice integration beyond existing Pharma contracts;
- silent/automatic Global-source refresh of persisted Customer Draft selections;
- fuzzy/AI medicine identity auto-confirmation;
- advanced bid analytics in the central Price List create UI;
- unrelated Inventory/Invoices/Partner refactors;
- unrelated frontend/Vite import-resolution repair;
- unrelated Pharma runtime enablement changes.

## Previous completed checkpoints

Official Facility Import + BHXH Source Mirror was merged to `main` via PR #166 on 2026-09-06. MaSoThue lookup CLI was merged via PR #168. Drug Award Allocation & Hospital Contract Management was merged earlier via PR #165. Those ownership and safety contracts remain preserved.

### Docker production hardening — 2026-09-19

Production Docker was aligned with the accepted HSSP runtime: PHP accepts 50 MB files / 64 MB POST bodies, Nginx accepts 100 MB request bodies, and the general Docker queue consumes `pharma` by default. `app` and `queue` already share the `app_storage` volume, so staged dossier files remain visible to the asynchronous uploader. `.env.docker.example` documents the same queue default. A focused Docker contract test guards these settings. Production still requires the existing Google Drive credentials/connection and Pharma runtime enablement.

## ClientPortal Commission parity checkpoint — 2026-10-05

Branch: `refactor/clientportal-pharma-commissions-parity`, base `main@1a1b35e28`.

Canonical Pharma boundary retained:

- `UserCommissionWorkspace` remains the ClientPortal-facing canonical commission workspace and now accepts an optional `medicine_id` in browse, summary and export queries;
- `commissionMedicines()` derives distinct Medicine choices from the already-scoped commission query, so user/team/date/source/manager boundaries remain authoritative;
- filtering occurs before issue grouping/aggregation, so when a Product filter is active the issue revenue/commission totals represent only matching commission-ledger entries;
- no commission calculation formula, posting/reversal lifecycle, Inventory Issue lifecycle or schema changed.

Accepted validation:

- ClientPortal Commission capability: **3 passed (155 assertions)**;
- Pharma module regression: **217 passed (2493 assertions)**;
- rendered Commission UI: **PASS**;
- no migration.

Do not reinterpret this checkpoint as an Admin Commission Center rewrite. ClientPortal consumes Pharma-owned scope/business rules and keeps its own PWA controller/view boundary.



## ClientPortal Pharma Hub checkpoint — 2026-10-05

- ClientPortal is refreshing the Pharma Application Hub from Foundation-era copy to capability discovery.
- No `Modules/Pharma` service, model, query, formula, workflow or schema changes are part of this branch.
- Domain ownership remains unchanged: `Modules/Pharma` owns business data/rules; ClientPortal only filters capability visibility through existing web permissions and presents links into existing workspaces.
- No migration and no domain regression is required solely for this presentation-only change unless subsequent implementation expands scope.


## ClientPortal Commission native interaction boundary — 2026-10-06

- Branch `refactor/clientportal-pharma-commissions-native-interactions` is presentation/interaction-only.
- Commission browser behavior moved from inline Blade JavaScript to the existing ClientPortal native interaction asset.
- No `Modules/Pharma` production code, commission formula, ledger scope, export semantics, lifecycle, permission, route or schema changes are part of this batch.
- Existing Pharma service/domain contracts remain canonical and must not be altered to complete this UI technical-debt closeout.


## Checkpoint 2026-10-21 — Official Source full-width workspace / Partners lookup entry

- Branch: `feat/pharma-source-wide-business-lookup-20261021` (base `e3d615851`).
- `source.blade.php` opts into Admin shell's canonical `@section('admin_container', 'full')` rather than changing global sidebar width; source table has bounded horizontal scrolling for narrow viewports.
- Added an explicit navigation action to the existing `admin.partners.lookup` route on each source row. **This is navigation only**: no tax-code/name autofill, API invocation, matching, legal verification, or persistence is claimed. BHXH source fields remain unchanged.
- Pending: audit actual Partners business-lookup controller/services/API contracts before building cross-module enrichment; do not assume name-search support or automatically link legal entities.
- Test status: NOT RUN (requires local checkpoint). UI acceptance: NOT VERIFIED. No PR/merge/deploy.
- Focused test: `php artisan test --filter=OfficialSource` (confirm available test names and output). Then Pharma module regression only after focused PASS; Partners impacted regression if cross-module integration is implemented.


## Checkpoint 2026-10-21 — Per-row queued legal lookup (pending local tests/UI)

- Branch: `feat/pharma-source-wide-business-lookup-20261021`.
- Source page now queues one facility-name lookup via `LookupOfficialSourceLegalJob` and displays status/candidates in an inline modal; no navigation to Partner lookup.
- Reuses `Modules/Partner/Services/DoanhNghiepLookupService`; at most 10 candidate results; errors and empty results are persisted. Existing BHXH source records and canonical Partner records are never overwritten.
- New `pharma_official_source_legal_lookups` table stores facility ID, query snapshot, candidate JSON, status, review selection, reviewer and timestamps. New migration is additive and has NOT been run.
- Reviewer confirms a tax-code candidate in Pharma only. This is **not** canonical Partner synchronization; any future Partner link/upsert needs a separate approved flow.
- New endpoints: GET status requires view permission; POST enqueue/review require `sync_pharma_official_facilities`. Queue worker must be running for asynchronous completion. Do not run a queue worker or migration on production without separate approval.
- Focused tests: `tests/Feature/Pharma/OfficialFacilityRegionContractTest.php`, `tests/Feature/Pharma/OfficialSourceLegalLookupContractTest.php`, `tests/Feature/Partner/DoanhNghiepLookupServiceTest.php`.
- Tests NOT RUN; UI NOT VERIFIED; module regression deferred until pre-merge gate; no PR, merge, or deploy.


## 2026-10-21 — Partner gateway integration (pending tests)

- `LookupOfficialSourceLegalJob` now depends on Partner-owned `BusinessRegistryLookupGateway`, not a hardcoded source provider. Name-only BHXH lookup requires configured Doanhnghiep.vn API key; VietQR supports exact tax code only. Cache reduces repeat quota usage.
- No automatic canonical Partner sync, no BHXH overwrite, no production migration or queue restart authorized. Tests and UI not yet verified.

## 2026-10-21 — MSTCongTy integration correction

- User UI confirmed Partner MSTCongTy name search returns candidates. Pharma gateway now reuses `MultiSourceBusinessLookupService::search(..., MstCongTyProvider::SOURCE)` for names; Doanhnghiep.vn remains an optional authenticated provider in Partner, not a mandatory name lookup dependency. VietQR remains MST-only. Provider failures are surfaced rather than cached as empty matches.
- New focused tests are pending local execution; UI Pharma is not yet confirmed PASS.


## 2026-10-21 — Reviewed legal identity Excel export

- Source Excel now includes confirmed tax code and legal address via eager-loaded legalLookup and confirmedCandidate(). Unreviewed candidates are excluded from these columns. Source BHXH fields remain unchanged.
- Focused test updated in OfficialFacilityRegionContractTest.php; local test and exported workbook UI verification pending. No migration or deployment.


## 2026-10-21 — Facility name category filter (pending tests)

- Source list supports `facility_type` choices hospital/Bệnh viện, medical_center/Trung tâm y tế, clinic/Phòng khám, health_station/Trạm y tế. Matches substring in `facility_name` with SQL LIKE; does not mutate or assign a canonical category. Filters combine with existing search/region/status and persist in export form.
- No migration or queue changes. Focused tests and UI pending operator confirmation. No PR/merge/deploy.


## 2026-10-21 — Source filter compact desktop layout

- Responsive filter form: 9 compact columns on xl desktop (search, facility type, region, source, province, BHXH partition, status, page size, reset), 2 columns on tablet and stacked on mobile. No filter behavior changes. UI confirmation pending; no deploy/merge.


## 2026-10-21 — Prefix filter and Excel parity

- Facility type filter now matches facility_name starting with the selected category phrase (`LIKE 'Bệnh viện%'`, etc.) instead of substring. Both index and unselected Excel export call shared `applyFilters`; explicit selected IDs still take precedence over filters.
- Focused tests and Excel/UI verification pending; no database or queue changes, no merge/deploy.


## 2026-10-21 — Legal identity Excel import (pending focused tests/UI)

- New separate Pharma legal import routes preview/confirm with import permission; existing BHXH source Excel importer untouched. Preview parses header columns `Mã CSKCB`, `Tên cơ sở khám chữa bệnh`, `Mã số thuế đã xác nhận`, `Địa chỉ pháp lý đã xác nhận`, max 500 rows/5 MB; validates BHXH source/external ID, exact normalized name, tax code and address, duplicate IDs and previously reviewed/running records. Preview is session-bound with 30 minute expiry and explicit confirmation.
- Confirm uses DB transaction, row locks, rechecks source/name/status, stores reviewed legal snapshot with `source=excel_import`; never writes BHXH mirror or Partner. No new migration or queue worker required. Original uploaded workbook is not imported automatically.
- Focused test: `tests/Feature/Pharma/OfficialSourceLegalImportContractTest.php`; PHP lint and UI verification pending. No PR/merge/deploy.


## 2026-10-21 — Excel import without legal verification

- User requested import first, external registry verification only on manual lookup. Preview no longer rejects 8/9-digit tax values solely due to lost leading zero in Excel or mismatched facility name; still requires BHXH CSKCB match, nonempty tax/address, safe tax characters, duplicate checks. Imported record status is `imported` (unverified), distinct from `reviewed`.
- Existing reviewed legal results remain protected; lookup preserves imported snapshot when retrieving registry candidates. Original BHXH and Partner untouched. Focused tests/UI pending. Do not claim legal verification from Excel.


## 2026-10-21 — Legal Excel import template

- Added GET legal-import/template under import permission, with Excel download and 4 exact headers matching preview import. A/C columns are Excel text for CSKCB/tax codes. Modal includes download link. No database/queue changes.
- User reported UI PASS for prior import flow, not yet for this new template. Focused test/lint and template download UI pending. No merge/deploy.


## 2026-10-21 — Pre-merge audit checkpoint (regression pending)

- GitHub compare against latest observed main `e3d615851`: feature ahead 69, behind 0; 22 files changed, including Pharma, Partner gateway, tests, migration, and services config. No new branch or worktree.
- User explicitly confirmed focused TEST PASS and UI PASS for legal import template. This does not imply module/impacted regression PASS.
- Read-only audit: legal Excel import uses separate preview/confirm and stores `imported` (unverified) candidate; manual registry lookup uses Partner gateway. Existing reviewed entries protected against Excel overwrite. No PR/merge/deploy authorized.
- Next gates: local tracked tree clean; PHP lint; focused Pharma/Partner tests; Pharma module regression and Partner impacted regression; `git diff --check`; UI regression only if UI code changes further; PR review. Do not assert PASS before operator output.


## 2026-10-21 — PR #321 security review fix

- Draft PR #321 reviewed; discovered that imported Excel candidates were retained in registry results and could be selected in review, improperly upgrading unverified Excel data. Backend review now explicitly rejects candidates whose source is `excel_import`; Excel import/display/export remain unchanged.
- Focused contract guard added. This is a new code change after prior operator TEST/UI PASS, so rerun focused and impacted tests before merging. PR remains draft, no merge/deploy.


## 2026-10-30 — Optional Pharma legal lookup migration boundary

- Root cause: global `database/migrations/2026_10_21_000001_create_pharma_official_source_legal_lookups_table.php` referenced `pharma_official_source_facilities`, whose parent migration belongs to the disabled-by-default Pharma module. Fresh migration on a clone with Pharma disabled failed with MySQL 1824.
- Fix: move the migration unchanged to `Modules/Pharma/database/migrations/` while preserving its filename and migration-history identity. No schema definition, application logic, or runtime module toggle changed.
- Focused test: `tests/Feature/PharmaLegalLookupMigrationBoundaryTest.php` asserts module ownership and parent-before-child ordering.
- Validation evidence from disposable clone `tungocvan/laravel-law`: focused tests 2 passed (5 assertions); operator confirmed `migrate:fresh` and `db:seed` PASS; module bootstrap/catalog/state resolver regression 11 passed (45 assertions). These results are not tests executed on this `laravel-shop` feature branch.
- Next checkpoint: pull this branch, run focused test, then relevant module regression; verify Git tracked changes and PR. Do not run `migrate:fresh` on production or non-disposable databases; no merge/deploy without explicit approval.

- 2026-11-02: Fix inventory opening template HTTP 500 by importing `Rap2hpoutre\\FastExcel\\FastExcel` in `InventoryController`; composer already requires package. Added `PharmaInventoryFastExcelImportContractTest`. Focused test and manual Excel download pending checkpoint; no DB changes.

- Follow-up 2026-11-02: corrected missing Symfony `StreamedResponse` import affecting five inventory Excel download methods; updated focused contract test. Await local test and UI verification.

- 2026-11-02: Admin price list ACTIVE -> INACTIVE via `PriceListDeactivationWorkflow::deactivateDirectly` with required reason, edit authorization and audit fields. Existing DRAFT/INACTIVE deletion rules retained. Focused contract test added; user reported TEST + UI PASS on original feature branch. Reapplied unchanged code on latest main after handoff merge conflict.


## 2026-11-03 — Approved bid manual lot selection (checkpoint pending)

- Branch: `feat/pharma-client-bid-lot-workflow-20261103`, based on `9c06aa3b9755cd8fe00728171bd3cbcb9d52815a`.
- Added Pharma-owned `BidIssueManualLotPostingService` for approved bid orders. It validates the requested per-item lot quantities, current warehouse, unexpired balances and aggregate per-lot stock under transaction/row locks; delegates canonical posting/commission to `InventoryService::postIssue`.
- ClientPortal adds a focused mobile-first lot workspace and GET/POST routes under `/apps/pharma/orders/{issue}/lots`. Existing `client.pharma.orders.post` permission is checked on both endpoints. Bid orders are redirected away from automatic FEFO posting; non-bid flow is unchanged.
- Safety restriction: mixed deferred and fulfilled allocations for the same medicine are rejected; manual posting currently requires the full approved quantity for each non-deferred item. No mutation occurs on failed validation. This is a deliberately scoped first batch, not full Admin edit parity.
- Remaining approved scope: canonical editable approved bid workflow, distinct edit/allocate permissions, audit trail and comprehensive runtime tests, Admin parity review. Do not claim completion or UI PASS yet.
- Focused contract test: `tests/Feature/ClientApps/PharmaBidManualLotCapabilityTest.php`. Local execution, UI validation and regression are pending. No migration, production change, PR or merge.

### 2026-11-03 — Draft bid UI actions / delegated manager follow-up

- Added edit/submit actions on both responsive order list and detail; pending approval orders now link to approval detail. Draft remains distinct from pending approval; no direct approval of drafts.
- `client.pharma.orders.create-for-user` explicitly authorizes cross-user draft edit (alongside create permission) and, together with `client.pharma.orders.submit`, cross-user submission. Pharma `UserOrderAuthoringService` validates draft status and existing business constraints inside transactions; delegation is an explicit parameter, never inferred from UI.
- Updated `PharmaOrderAuthoringCapabilityTest` contract assertions and added draft action coverage to `PharmaBidManualLotCapabilityTest`.
- Local focused and impacted regression not executed by GitHub integration. UI previously reported FAIL for missing edit/approval actions; UI must be retested after pulling. Approved bid content editing and reapproval flow remain future work.

### 2026-11-03 — Quantity formatting and ClientPortal checkpoint

- User-reported UI discrepancy: order quantity 500 appeared as 500.000 in ClientPortal, while Admin correctly showed 500. Changed order form draft hydration, detail/stock readiness and manual lot workspace to show whole units with no decimal digits. Unit prices already use zero-decimal VND formatting; unchanged.
- ClientPortal authoring and manual lot request validation now require integer quantities; no database migration. Fixed an undefined `$canCreateForUser` reference in `submitOrder()` by using the existing authorized `$canManageOrders` capability.
- Manual lot allocation is still a separate workspace for **APPROVED** bid orders; the Admin's combined edit/approve/post screen has **not** been fully ported. Approved-order editing and in-form lot allocation remain outstanding; do not mark Admin parity or UI PASS.
- Focused: `php artisan test tests/Feature/ClientApps/PharmaBidManualLotCapabilityTest.php tests/Feature/ClientApps/PharmaOrderAuthoringCapabilityTest.php`; run impacted approval/inventory tests only after focused PASS. Test execution pending on local checkout.

### 2026-11-03 — Approved bid manual-lot UI enhancement

- UI PASS reported by user for the draft order wizard spacing and integer quantity display.
- Compared Admin `bid-sale-edit` with ClientPortal: Admin combines draft edit and approval/posting; ClientPortal deliberately retains draft → pending → approved → manual lot post. The approved bid `order-lots` workspace now suggests FEFO split per available lot, displays expiry/stock, validates live per-product totals, and preserves integer quantity display.
- Pharma-owned `BidIssueManualLotPostingService` additionally rejects unexpected item IDs and noninteger quantities; existing transaction, stock locks and posting service remain authoritative.
- Still outstanding: full parity with Admin combined edit/approve/post and deferred stock workflow. No claim of UI PASS for lot posting or runtime tests until local validation.
- Focused test: `php artisan test tests/Feature/ClientApps/PharmaBidManualLotCapabilityTest.php`. After PASS run order approval and inventory regression.

### 2026-11-03 — Partial bid approval and multiple-lot posting checkpoint

- User approved partial bid approval: order demand 500, approve actual stock 336, defer remaining 164. ClientPortal pending bid detail now presents whole-number per-item approval inputs and expected-supply notes; approval calls Pharma-owned `UserOrderApprovalService::approveBidPartially` inside a transaction.
- Each product may be allocated across **multiple** unexpired lots in existing approved-order lot workspace; manual lot posting rechecks warehouse/medicine/expiry, aggregated lot availability, and exact per-product approved totals under transaction locks.
- Pharma stock posting and bid commission snapshot now retain explicit batch/expiry rows for bid orders even when a deferred supply exists for the same medicine; non-bid paths keep existing deferred-medicine exclusion. This cross-module accounting change requires impacted regression, especially commission snapshots and reversal.
- Important remaining limitation: partial approval updates issue item quantity and stores remainder in deferred supply. Undo approval currently does not reconstruct original item quantity from deferred; do not approve UI gate until undo/reapproval behaviour is verified and addressed. No automatic migration or production changes.
- Tests not executed by GitHub integration. Focused: `php artisan test tests/Feature/ClientApps/PharmaBidManualLotCapabilityTest.php tests/Feature/ClientApps/PharmaOrderApprovalCapabilityTest.php`. After PASS, impacted: `php artisan test tests/Feature/ClientApps/PharmaInventoryIssuesCapabilityTest.php Modules/Pharma/Tests/Unit/InventoryContractTest.php`; add commission/reversal regression if available. UI PASS for prior wizard only, not for partial approval/multi-lot posting.

### 2026-11-03 — PDF export action visibility follow-up

- User confirmed partial bid approval/stock posting works in local UI, but after generating PDF the list did not expose Print/Download. Existing private PDF routes and hash validation were retained.
- InventoryIssueDocumentService now hashes freshly persisted issue/item data in deterministic ID order and verifies `current()` immediately after creating the document; a failed verification returns HTTP 409 rather than a false success message.
- ClientPortal PDF export redirects to canonical order detail after successful generation, where Print and Download are already present when `current()` passes; avoids stale filtered/PWA list state.
- Regression contract added in `tests/Feature/ClientApps/PharmaInventoryIssuesCapabilityTest.php`. Test and UI are pending on local environment; if 409 occurs, inspect document settings/hash changes rather than bypassing privacy/integrity checks.
- Still pending separately: refinement of partial-approval UI and restoration of original demand on undo approval. No production operations, merge, or migrations.

### 2026-11-03 — Issue document metadata and primary lot action (checkpoint)

- Branch: `feat/pharma-issue-document-meta-actions-latest-20261103`, based on `fix/client-pharma-conditional-deferred-fields-20261103` (preserves the existing ClientPortal order/lot workflow; `main` is behind this work).
- Admin issue document settings now have an independent `show_manager` checkbox (default enabled via additive migration); existing `show_price_list` checkbox remains independent. Both apply to Admin issue detail/PDF and PDF consumed by ClientPortal print/download. PDF bid orders label source as “Theo kết quả trúng thầu”.
- PDF manager name uses the Pharma canonical manager resolver: active bid award management assignments by partner/award, with issue manager fallback. PDF source hash incorporates resolved manager names when displayed so stale artifacts are not reused. Admin bid issue detail uses same resolver.
- ClientPortal approved bid order list promotes “Chọn lô xuất kho” to a prominent primary action on desktop and mobile, leaving undo approval in secondary menu. Existing multi-lot allocation and posting flow unchanged.
- Migration required on local/staging before UI verification: `php artisan migrate` only after reviewing pending migrations; no migration or production change performed by assistant.
- Focused contract: `tests/Feature/ClientApps/PharmaInventoryIssuesCapabilityTest.php`. Regression: `Modules/Pharma/Tests/Unit/InventoryContractTest.php` and ClientPortal bid lot/approval focused suites. TEST PENDING, UI PENDING, no merge/deploy.

- 2026-11-03 UI feedback fix: issue PDF had literal `@else` after bid price source because inline Blade directive was not parsed; branches now use separate lines. PDF source hash template version bumped to require fresh export of previously cached PDFs. ClientPortal desktop lot action now always reads “Chọn lô” with wider actions column instead of truncated “Lô”. UI still pending user recheck; tests not executed by assistant.


### 2026-11-03 — Price-list orders: partial approval and manual lot posting (checkpoint pending)

- Branch: `feat/pharma-price-list-manual-lots-20261103`, based on `feat/pharma-issue-document-meta-actions-latest-20261103` because its unmerged ClientPortal Pharma workflows are required.
- Pharma owns partial approval/deferred supply and transactional multi-lot posting. Price-list orders now share the manual lot selection screen used by bid orders; multiple valid batches per medicine are supported.
- Deferred quantity for price-list issues is recorded per medicine, approved quantity remains in issue items; duplicate medicine rows in a price-list partial approval are rejected to avoid ambiguous deferred accounting.
- Pending gates: focused tests, Pharma and ClientPortal impacted regression, real desktop/mobile UI verification, tracked tree verification. No TEST PASS/UI PASS/merge claimed.
