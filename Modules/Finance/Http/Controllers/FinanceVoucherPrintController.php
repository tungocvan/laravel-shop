<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Finance\Services\FinancePrintSettings;
use Modules\Finance\Support\VietnameseMoney;

final class FinanceVoucherPrintController extends Controller
{
    public function __invoke(int $voucher, FinancePrintSettings $settings): View
    {
        $document = DB::table('finance_vouchers as v')
            ->join('finance_accounts as account', 'account.id', '=', 'v.account_id')
            ->leftJoin('finance_allocations as allocation', 'allocation.voucher_id', '=', 'v.id')
            ->leftJoin('finance_source_documents as source', 'source.id', '=', 'allocation.source_document_id')
            ->leftJoin('finance_manual_categories as category', function ($join): void {
                $join->on('category.code', '=', 'v.manual_category')
                    ->on('category.direction', '=', 'v.direction');
            })
            ->where('v.id', $voucher)
            ->select('v.*', 'account.name as account_name', 'account.code as account_code',
                'account.kind as account_kind', 'account.bank_name', 'account.bank_account_number',
                'account.bank_account_holder', 'account.cash_custodian',
                'source.source_document_code', 'source.counterparty as source_counterparty',
                'category.name as category_name')
            ->first();

        abort_if($document === null, 404);
        abort_unless(in_array($document->direction, ['receipt', 'payment'], true), 404);

        return view('Finance::vouchers.print-a5', [
            'voucher' => $document,
            'amountInWords' => VietnameseMoney::spell((string) $document->amount),
            'printSettings' => $settings->get(),
            'pdfMode' => false,
        ]);
    }
    public function pdf(int $voucher, FinancePrintSettings $settings): Response
    {
        $view = $this->__invoke($voucher, $settings);
        $options = $settings->get();
        $pdf = Pdf::loadHTML($view->with('pdfMode', true)->render())
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isFontSubsettingEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
            ])
            ->setPaper(strtolower($options['paper']), $options['orientation']);
        $filename = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $view->getData()['voucher']->number);
        return $pdf->download('finance-'.$filename.'.pdf');
    }

}
