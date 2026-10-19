# Website Module — Current Status Snapshot (2026-10-19)

> Documentation-only checkpoint. Baseline: `main` at `2cf929cd2` (merge PR #315), as confirmed by local `git log -1` and GitHub repository inspection. This is not a production health check or a declaration that every Website regression test passes.

## Current handoff / verification status

| Gate | Evidence / status |
| --- | --- |
| User UI acceptance | **PASS (user-confirmed, 2026-10-19)**. This is user acceptance, not an independently executed browser test by the assistant. |
| Quick Order focused PHPUnit | **PASS** — 12 tests, 73 assertions; local execution reported earlier in the collaboration. |
| Website module regression | **FAIL** — 309 tests, 38,138 assertions, 3 failures; exit code 1, 54.188 seconds, PHP 8.4.24 / PHPUnit 11.5.56. |
| Impacted / cross-module regression | **NOT RUN** after the failed Website module regression. |
| Production verification | **NOT PERFORMED** for this documentation checkpoint. |
| Git local tracked state | Clean at the last reported checkpoint; two pre-existing untracked files are listed below. |

### Outstanding Website regression failures

All three failures in `Modules/Website/Tests/Feature/FooterSettingsUxContractTest.php`:

1. `test_footer_hub_separates_layout_preview_and_presentation_without_changing_save_contract`: test expects `footerPanel === 'layout'`.
2. `test_column_draft_is_isolated_from_published_layout_and_has_controls`: test expects `wire:click="saveFooterColumns"`.
3. `test_column_draft_supports_ordered_text_and_links_without_publishing`: test expects `Chưa áp dụng ra Footer công khai`.

The observed view/source uses newer Footer Studio conventions (including `studioPanel` and `updateTheme` in the relevant output), but **root cause is not established** for this 309-test run. Do not rewrite working UI solely to satisfy string-based contract assertions. Investigate source/test alignment read-only, rerun focused Footer tests, then Website regression before claiming automated gate PASS.

The existing handoff records a separate PR #315 contract-test closeout with a reported 289-test PASS; that historical result **does not supersede** the newer local 309-test FAIL. Different test counts or environments must be reconciled using source and commands rather than inferred.

## Architecture / ownership boundaries

The approved Website major refactor remains closed for its authorized scope. Refer to `MODULE.md` for the canonical module contract and `COLLABORATION_HANDOFF.md` for historical refactor decisions.

- **Website** owns storefront presentation, shell, Header, Footer, Homepage, themes, and Website-specific UI adapters.
- **System** owns canonical settings persistence. Do not recreate independent Website settings persistence or physically remove legacy `wp_settings` without a separately approved safety plan.
- **Order** owns checkout/order and Affiliate business logic; **Product** owns Wishlist and Review runtime business logic; **User** owns customer/profile/address runtime responsibilities.
- Cart, Coupon/FlashSale, Tag, and MoMo/payment boundaries retain historical quarantine/defer constraints as recorded in the handoff; do not assume they are safe to delete or rehome.

### Pharma Quick Order integration checkpoint

- Website exposes authenticated Quick Order presentation and request intake (GET `/quick-order`, POST `/quick-order/requests`).
- Pharma owns the downstream inventory issue/order authoring business process; Website must not duplicate that domain logic.
- The earlier read-only audit identified submission-key deduplication, nullable inventory issue linkage, soft-delete and review-audit contracts, plus an issue handoff service using transactional locking.
- Admin access and price visibility must continue to respect authorization boundaries. Earlier analysis noted a **price-authorization question requiring verification**, not a proven vulnerability.
- Read `PHARMA_QUICK_ORDER_V1.md` and inspect current source/routes/tests before proposing changes. This snapshot is not a substitute for a new runtime audit.

## Local state and safety

Last reported local branch before GitHub documentation publication: `docs/website-status-handoff-20261019`, created from `main`. Existing untracked files:

```text
.env.backup-before-ngrok-session
public/hot.pharma-mobile
```

Preserve both files. Do not overwrite `.env`, alter production services, or perform database migration/restart/deploy as part of documentation work.

## Next-session entry point

1. Read `docs/GITHUB_COLLABORATION_WORKFLOW.md`, `docs/modules/Website/MODULE.md`, this snapshot, and `docs/modules/Website/COLLABORATION_HANDOFF.md` from the latest `main`.
2. Check latest GitHub `main` and local branch/tracked state; do not infer code state from this dated snapshot.
3. If the next task concerns Quick Order, read `PHARMA_QUICK_ORDER_V1.md`, inspect Website/Pharma boundaries and focused tests first.
4. If the next task concerns Footer, investigate the three named test assertions against current Blade/Livewire implementation without sacrificing accepted UI behavior.
5. Follow focused tests → module regression → impacted regression where applicable; record actual output. The user's UI PASS is accepted for the previously reviewed UI, not for future UI changes.
6. No merge to `main` or production deployment without explicit user approval.

## Documentation update scope

This checkpoint changes documentation only. It does not claim new code fixes, new PHPUnit execution, new UI testing, or production validation.
