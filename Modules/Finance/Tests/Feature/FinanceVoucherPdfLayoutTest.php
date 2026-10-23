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
        // Assert the generated PDF is one physical A5 landscape page.
        // DomPDF may restructure/discard HTML5 element frames during layout,
        // so querying <main> in its internal frame tree is not a stable geometry check.
        $canvas = $pdf->getDomPDF()->getCanvas();
        $this->assertEqualsWithDelta(595.28, $canvas->get_width(), 1.0);
        $this->assertEqualsWithDelta(419.53, $canvas->get_height(), 1.0);

        // The explicit printable area is bounded by 12mm on each side.
        // These are source-layout assertions, NOT a claim of verified rendered coordinates.
        $this->assertStringContainsString('$contentWidth = $paperWidth - 2 * $printMargin;', $html);
        $this->assertStringContainsString('width: 186mm;', $html);
        $this->assertStringContainsString('margin: 12mm 0 0 12mm;', $html);
    }
}
