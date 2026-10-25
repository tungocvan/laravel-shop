<?php

namespace Modules\Finance\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Explicit human-reviewed invoice recognition. No invoice writes or payment posting.
 */
final class InvoiceDebtRecognitionService
{
    public function preview(int $invoiceId): array
    {
        if (! config('modules.registry.Invoices.enabled', false) || ! Schema::hasTable('invoices')) {
            throw ValidationException::withMessages(['invoice' => 'Nguồn Invoices chưa sẵn sàng.']);
        }
        $invoice = DB::table('invoices')->where('id', $invoiceId)->first();
        if (! $invoice) {
            throw ValidationException::withMessages(['invoice' => 'Không tìm thấy hóa đơn.']);
        }
        $issues = [];
        if (! in_array($invoice->invoice_type, ['sold', 'purchase'], true)) {
            $issues[] = 'Loại hóa đơn không hỗ trợ.';
        }
        if (! trim((string) $invoice->invoice_number) || ! trim((string) $invoice->symbol)
            || ! trim((string) $invoice->name) || ! trim((string) $invoice->tax_code)
            || ! $invoice->issued_date) {
            $issues[] = 'Thiếu số, ký hiệu, ngày lập hoặc thông tin đối tác.';
        }
        $amount = (string) $invoice->total_amount;
        if (! preg_match('/^\d{1,16}(?:\.\d{1,2})?$/', $amount)
            || ! preg_match('/[1-9]/', $amount)) {
            $issues[] = 'Tổng tiền không hợp lệ hoặc bằng 0.';
        }
        $type = mb_strtolower((string) $invoice->type);
        if (preg_match('/hủy|huỷ|thay thế|điều chỉnh|cancel|replace|adjust/u', $type)) {
            $issues[] = 'Hóa đơn có dấu hiệu hủy/thay thế/điều chỉnh.';
        }
        if (DB::table('finance_debt_obligations')->where('source_module', 'Invoices')
            ->where('source_type', 'invoice')->where('source_document_id', (string) $invoiceId)->exists()) {
            $issues[] = 'Hóa đơn đã có bản ghi công nợ nguồn.';
        }
        return ['invoice' => $invoice, 'issues' => $issues];
    }

    public function confirm(int $invoiceId, int $actorId, string $recognizedOn, ?string $dueOn, bool $reviewed): int
    {
        if ($actorId <= 0 || ! $reviewed || ! $this->validDate($recognizedOn)
            || ($dueOn !== null && (! $this->validDate($dueOn) || $dueOn < $recognizedOn))) {
            throw ValidationException::withMessages(['invoice' => 'Cần xác nhận kiểm tra hóa đơn và ngày ghi nhận/hạn thanh toán hợp lệ.']);
        }
        try {
            return DB::transaction(function () use ($invoiceId, $actorId, $recognizedOn, $dueOn): int {
                $preview = $this->preview($invoiceId);
                if ($preview['issues'] !== []) {
                    throw ValidationException::withMessages(['invoice' => implode(' ', $preview['issues'])]);
                }
                $invoice = $preview['invoice'];
                return DB::table('finance_debt_obligations')->insertGetId([
                    'source_module' => 'Invoices',
                    'source_type' => 'invoice',
                    'source_document_id' => (string) $invoice->id,
                    'reference_number' => mb_substr(trim($invoice->symbol).'/'.trim($invoice->invoice_number), 0, 100),
                    'direction' => $invoice->invoice_type === 'sold' ? 'receivable' : 'payable',
                    'status' => 'confirmed',
                    'counterparty_name' => trim($invoice->name),
                    'counterparty_tax_code' => trim($invoice->tax_code),
                    'recognized_on' => $recognizedOn,
                    'due_on' => $dueOn,
                    'currency' => 'VND',
                    'original_amount' => $invoice->total_amount,
                    'description' => 'Xác nhận thủ công từ Invoices; người xác nhận đã kiểm tra hiệu lực và thanh toán ngoài hệ thống. Chưa ghi thanh toán.',
                    'created_by' => $actorId,
                    'confirmed_by' => $actorId,
                    'confirmed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }, 3);
        } catch (QueryException $e) {
            if (in_array((string) $e->getCode(), ['23000', '23505', '19'], true)) {
                throw ValidationException::withMessages(['invoice' => 'Không thể ghi nhận: hóa đơn có thể đã được liên kết. Vui lòng tải lại.']);
            }
            throw $e;
        }
    }

    private function validDate(string $date): bool
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        [$year, $month, $day] = array_map('intval', explode('-', $date));
        return checkdate($month, $day, $year);
    }
}
