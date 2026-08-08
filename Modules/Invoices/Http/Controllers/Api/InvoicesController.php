<?php

namespace Modules\Invoices\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Invoices\Services\InvoiceService;

class InvoicesController extends Controller
{
    public function __construct(private readonly InvoiceService $invoiceService) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'API Invoices is available.',
        ]);
    }

    public function filter(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lookup_code' => ['nullable', 'string', 'max:255'],
            'symbol' => ['nullable', 'string', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'issued_date_from' => ['nullable', 'date'],
            'issued_date_to' => ['nullable', 'date', 'after_or_equal:issued_date_from'],
            'tax_code' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'invoice_type' => ['nullable', 'in:sold,purchase'],
            'tax_rate_min' => ['nullable', 'numeric'],
            'tax_rate_max' => ['nullable', 'numeric'],
            'vat_amount_min' => ['nullable', 'numeric'],
            'vat_amount_max' => ['nullable', 'numeric'],
            'amount_before_vat_min' => ['nullable', 'numeric'],
            'amount_before_vat_max' => ['nullable', 'numeric'],
            'total_amount_min' => ['nullable', 'numeric'],
            'total_amount_max' => ['nullable', 'numeric'],
            'sort_by' => ['nullable', 'in:issued_date,invoice_number,tax_code,name,tax_rate,vat_amount,amount_before_vat,total_amount'],
            'sort_order' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $query = $this->invoiceService->filter($validated, true);

        foreach (['tax_rate', 'vat_amount', 'amount_before_vat', 'total_amount'] as $field) {
            if (array_key_exists("{$field}_min", $validated)) {
                $query->where($field, '>=', $validated["{$field}_min"]);
            }

            if (array_key_exists("{$field}_max", $validated)) {
                $query->where($field, '<=', $validated["{$field}_max"]);
            }
        }

        $statistics = clone $query;
        $query->orderBy(
            $validated['sort_by'] ?? 'issued_date',
            $validated['sort_order'] ?? 'desc'
        );

        $data = $query->paginate((int) ($validated['per_page'] ?? 20))->withQueryString();

        return response()->json([
            'success' => true,
            'meta' => [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
                'sum_total_amount' => $statistics->sum('total_amount'),
                'issued_date_min' => (clone $statistics)->min('issued_date'),
                'issued_date_max' => (clone $statistics)->max('issued_date'),
            ],
            'data' => $data->items(),
        ]);
    }
}
