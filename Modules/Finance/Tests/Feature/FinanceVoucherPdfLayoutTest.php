<?php

namespace Modules\Finance\Tests\Feature;

use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

final class FinanceVoucherPdfLayoutTest extends TestCase
{
    public function test_a5_landscape_receipt_renders_on_one_page_with_long_reference(): void
    {
        $voucher = (object) [
            'id' => 1,
            'direction' => 'receipt',
            'number' => 'FIN-M-20261021162956-80C2AF0D',
            'status' => 'posted',
            'voucher_date' => '2026-10-21',
            'origin' => 'manual',
            'counterparty' => 'Trần Mai Ninh',
            'description' => 'Đặt cọc tiền hàng',
            'category_name' => 'Thu tiền đặt cọc',
            'manual_category' => 'deposit',
            'account_name' => 'Ngân hàng Techcombank',
            'account_code' => '10002000',
            'account_kind' => 'bank',
            'bank_name' => null,
            'bank_account_number' => null,
            'reference_number' => null,
            'source_document_code' => null,
            'amount' => '10000000.00',
            'currency' => 'VND',
        ];
        $settings = [
            'paper' => 'A5', 'orientation' => 'landscape',
            'margin_mm' => 12, 'font_size' => 9,
            'organization' => 'CÔNG TY TNHH INAFO VIỆT NAM',
            'subtitle' => 'Chứng từ quản lý thu / chi nội bộ',
            'show_bank' => true, 'show_reference' => true, 'show_signatures' => true,
            'signature_creator' => 'Người lập phiếu',
            'signature_approver' => 'Thủ quỹ/Kế toán',
        ];

        $html = view('Finance::vouchers.print-a5', [
            'voucher' => $voucher,
            'printSettings' => $settings,
            'amountInWords' => 'Mười triệu đồng chẵn.',
            'pdfMode' => true,
        ])->render();

        $this->assertStringContainsString('Trần Mai Ninh', $html);
        $this->assertStringContainsString('Đặt cọc tiền hàng', $html);
        $this->assertStringContainsString('Bằng chữ: Mười triệu đồng chẵn.', $html);

        $pdf = Pdf::loadHTML($html)->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isFontSubsettingEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
        ])->setPaper('a5', 'landscape');
        $pdf->getDomPDF()->render();

        $this->assertSame(1, $pdf->getDomPDF()->getCanvas()->get_page_count());
    }
}
