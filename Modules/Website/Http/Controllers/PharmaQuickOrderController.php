<?php

namespace Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\PriceList;
use Modules\Website\Models\PharmaQuickOrderRequest;
use Illuminate\View\View;
use Modules\Pharma\Models\PriceListItem;
use Modules\Product\Models\Product;
use Modules\System\Services\SettingsService;
use Modules\Website\Services\PharmaQuickOrderPriceListService;
use Modules\Website\Services\PharmaQuickOrderSettingsService;

final class PharmaQuickOrderController extends Controller
{
    public function index(
        Request $request,
        SettingsService $websiteSettings,
        PharmaQuickOrderSettingsService $quickOrderSettings,
        PharmaQuickOrderPriceListService $priceLists
    ): View|JsonResponse {
        abort_unless($websiteSettings->get('website.public_template', 'default') === 'pharma', 404);

        $configuration = $quickOrderSettings->get();
        abort_unless($configuration['enabled'], 404);

        $user = $request->user();
        abort_unless($user && $user->is_active, 403);

        // New Website customers may browse eligible globally scoped medicines.
        // Customer-specific price lists require a verified partner relationship.
        // Do not disclose negotiated prices or accept orders at this checkpoint.
        $globalListIds = $priceLists->eligibleForCustomer(
            0,
            (int) $user->id,
            $configuration['price_list_ids'],
            now()->toDateString()
        )->where('type', \Modules\Pharma\Models\PriceList::TYPE_GLOBAL)->pluck('id');

        $search = mb_substr(trim((string) $request->query('search', '')), 0, 120);
        $items = Product::query()->active()
            ->whereHas('pharmaLink', function ($link) use ($globalListIds): void {
                $link->whereIn('medicine_id', PriceListItem::query()
                    ->whereIn('price_list_id', $globalListIds)
                    ->where(function ($item): void {
                        $item->whereNull('effective_from')->orWhereDate('effective_from', '<=', now()->toDateString());
                    })
                    ->where(function ($item): void {
                        $item->whereNull('effective_to')->orWhereDate('effective_to', '>=', now()->toDateString());
                    })
                    ->select('medicine_id'));
            })
            ->whereHas('categories', fn ($category) => $category->where('type', 'medicine'))
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.$search.'%'))
            ->with('pharmaLink')
            ->select(['id', 'title', 'slug', 'image', 'short_description'])
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();


        // Price must match the exact Pharma variant and package linked to the Website product.
        $links = $items->getCollection()->pluck('pharmaLink')->filter();
        $prices = PriceListItem::query()
            ->whereIn('price_list_id', $globalListIds)
            ->whereIn('medicine_variant_id', $links->pluck('medicine_variant_id')->filter()->unique())
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', now()->toDateString()))
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', now()->toDateString()))
            ->get(['medicine_variant_id', 'medicine_package_id', 'company_sale_price']);

        $productPayload = $items->getCollection()->map(function ($product) use ($prices): array {
            $link = $product->pharmaLink;
            $matches = $prices->filter(fn ($item) => $link
                && (int) $item->medicine_variant_id === (int) $link->medicine_variant_id
                && (int) ($item->medicine_package_id ?? 0) === (int) ($link->medicine_package_id ?? 0)
            )->pluck('company_sale_price')->filter(fn ($value) => $value !== null)->unique()->values();

            return [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'image' => $product->image,
                'company_sale_price' => $matches->count() === 1 ? (float) $matches->first() : null,
            ];
        });

        if ($request->expectsJson()) {
            return response()->json([
                'products' => $productPayload->values(),
                'next_page' => $items->hasMorePages() ? $items->currentPage() + 1 : null,
            ]);
        }

        return view('Website::pages.pharma-quick-order', [
            'items' => $items,
            'search' => $search,
            'quickOrderProducts' => $productPayload,
        ]);
    }
    public function store(
        Request $request,
        SettingsService $websiteSettings,
        PharmaQuickOrderSettingsService $quickOrderSettings,
        PharmaQuickOrderPriceListService $priceLists
    ): JsonResponse {
        abort_unless($websiteSettings->get('website.public_template', 'default') === 'pharma', 404);
        $configuration = $quickOrderSettings->get();
        abort_unless($configuration['enabled'], 404);
        $user = $request->user();
        abort_unless($user && $user->is_active, 403);

        $data = $request->validate([
            'submission_key' => ['required', 'uuid'],
            'buyer_type' => ['required', 'in:company,individual'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'tax_code' => ['nullable', 'string', 'max:20'],
            'recipient_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.product_id' => ['required', 'integer', 'min:1', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'between:1,9999'],
        ]);

        $existing = PharmaQuickOrderRequest::query()->where('submission_key', $data['submission_key'])->first();
        if ($existing) {
            abort_unless((int) $existing->user_id === (int) $user->id, 409);
            return response()->json(['id' => $existing->id, 'status' => $existing->status], 200);
        }

        $listIds = $priceLists->eligibleForCustomer(
            0, (int) $user->id, $configuration['price_list_ids'], now()->toDateString()
        )->where('type', PriceList::TYPE_GLOBAL)->pluck('id');

        $requestedIds = collect($data['items'])->pluck('product_id');
        $eligible = Product::query()->active()
            ->whereIn('id', $requestedIds)
            ->whereHas('categories', fn ($category) => $category->where('type', 'medicine'))
            ->whereHas('pharmaLink', function ($link) use ($listIds): void {
                $link->whereIn('medicine_id', PriceListItem::query()
                    ->whereIn('price_list_id', $listIds)
                    ->where(fn ($q) => $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', now()->toDateString()))
                    ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', now()->toDateString()))
                    ->select('medicine_id'));
            })->pluck('id');

        if ($eligible->count() !== $requestedIds->count()) {
            throw ValidationException::withMessages(['items' => 'Có sản phẩm không còn được phép gửi yêu cầu. Vui lòng kiểm tra giỏ hàng.']);
        }

        // Submission is only an unpriced buyer request. No Partner is assigned,
        // no price/VAT is trusted, and no inventory issue or stock movement occurs.
        $record = DB::transaction(function () use ($user, $data): PharmaQuickOrderRequest {
            return PharmaQuickOrderRequest::query()->firstOrCreate(
                ['submission_key' => $data['submission_key']],
                [
                    'user_id' => $user->id,
                    'status' => PharmaQuickOrderRequest::PENDING_REVIEW,
                    'buyer_type' => $data['buyer_type'],
                    'company_name' => $data['company_name'] ?? null,
                    'tax_code' => $data['tax_code'] ?? null,
                    'recipient_name' => $data['recipient_name'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'shipping_address' => $data['shipping_address'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'requested_items' => $data['items'],
                    'submitted_at' => now(),
                ]
            );
        });

        abort_unless((int) $record->user_id === (int) $user->id, 409);

        $notificationEmail = $configuration['notification_email'] ?? '';
        if ($record->wasRecentlyCreated && $notificationEmail !== '' && filter_var($notificationEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::raw(
                    "Yêu cầu đặt hàng nhanh Pharma #{$record->id} vừa được gửi.\n"
                    ."Trạng thái: Chờ xét duyệt.\n"
                    ."Loại người mua: {$record->buyer_type}\n"
                    ."Số mặt hàng: ".count($record->requested_items ?? [])."\n"
                    ."Vui lòng đăng nhập hệ thống để kiểm tra và xác minh điều kiện mua thuốc.\n"
                    ."Chưa có phiếu xuất kho được tạo.",
                    static function ($message) use ($notificationEmail, $record): void {
                        $message->to($notificationEmail)->subject("Yêu cầu đặt hàng Pharma #{$record->id} - Chờ xét duyệt");
                    }
                );
            } catch (\Throwable $exception) {
                Log::warning('Pharma quick order notification failed', [
                    'request_id' => $record->id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return response()->json(['id' => $record->id, 'status' => $record->status], 201);
    }

}
