<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Data\FinancialDocumentSnapshot;

final class FinanceWorkflowService
{
    public function __construct(private readonly FinancialDocumentRegistry $providers) {}

    public function createAccount(string $code, string $name, string $kind): int
    {
        $code = trim($code);
        $name = trim($name);
        if ($code === '' || strlen($code) > 40 || $name === '' || strlen($name) > 255
            || ! in_array($kind, ['cash', 'bank'], true)) {
            throw ValidationException::withMessages(['account' => 'Invalid account information.']);
        }

        return DB::table('finance_accounts')->insertGetId([
            'code' => $code, 'name' => $name, 'kind' => $kind,
            'currency' => 'VND', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function outstanding(string $module, string $type, string $documentId): array
    {
        return DB::transaction(function () use ($module, $type, $documentId): array {
            $snapshot = $this->providers->resolve($module, $type)->resolveForPosting($documentId);
            $source = DB::table('finance_source_documents')
                ->where('source_module', $module)->where('source_type', $type)
                ->where('source_document_id', $documentId)->lockForUpdate()->first();

            $paid = $source ? DB::table('finance_allocations as a')
                ->join('finance_vouchers as v', 'v.id', '=', 'a.voucher_id')
                ->where('a.source_document_id', $source->id)->where('v.status', 'posted')
                ->select('a.amount')->get()
                ->sum(fn ($row): int => $this->cents((string) $row->amount)) : 0;

            $total = $this->cents($snapshot->amount);
            return [
                'snapshot' => $snapshot,
                'total' => $this->decimal($total),
                'paid' => $this->decimal($paid),
                'remaining' => $this->decimal(max(0, $total - $paid)),
            ];
        });
    }

    public function createDraft(
        string $module,
        string $type,
        string $documentId,
        int $accountId,
        string $amount,
        string $number,
        ?int $actorId = null,
    ): int {
        return DB::transaction(function () use ($module, $type, $documentId, $accountId, $amount, $number, $actorId): int {
            $number = trim($number);
            if ($number === '' || strlen($number) > 50) {
                throw ValidationException::withMessages(['number' => 'Invalid voucher number.']);
            }

            $account = DB::table('finance_accounts')->where('id', $accountId)->lockForUpdate()->first();
            if (! $account || ! $account->is_active || $account->currency !== 'VND') {
                throw ValidationException::withMessages(['account' => 'Active VND account is required.']);
            }

            $snapshot = $this->providers->resolve($module, $type)->resolveForPosting($documentId);
            if ($snapshot->currency !== $account->currency) {
                throw ValidationException::withMessages(['currency' => 'Account currency does not match source.']);
            }

            $total = $this->cents($snapshot->amount);
            $allocated = $this->cents($amount);
            if ($allocated <= 0 || $allocated > $total) {
                throw ValidationException::withMessages(['amount' => 'Amount must be positive and not exceed document total.']);
            }

            $source = DB::table('finance_source_documents')
                ->where('source_module', $snapshot->module)
                ->where('source_type', $snapshot->type)
                ->where('source_document_id', $snapshot->documentId)
                ->lockForUpdate()->first();

            if ($source) {
                if ($source->source_document_code !== $snapshot->documentCode
                    || $source->direction !== $snapshot->direction
                    || $source->currency !== $snapshot->currency
                    || $this->cents((string) $source->original_amount) !== $total) {
                    throw ValidationException::withMessages(['source' => 'Source document has changed.']);
                }
                $sourceId = $source->id;
            } else {
                $sourceId = DB::table('finance_source_documents')->insertGetId([
                    'source_module' => $snapshot->module,
                    'source_type' => $snapshot->type,
                    'source_document_id' => $snapshot->documentId,
                    'source_document_code' => $snapshot->documentCode,
                    'direction' => $snapshot->direction,
                    'currency' => $snapshot->currency,
                    'original_amount' => $snapshot->amount,
                    'counterparty' => $snapshot->counterparty,
                    'snapshot' => json_encode([
                        'module' => $snapshot->module, 'type' => $snapshot->type,
                        'document_id' => $snapshot->documentId, 'code' => $snapshot->documentCode,
                    ], JSON_THROW_ON_ERROR),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            $paid = DB::table('finance_allocations as a')
                ->join('finance_vouchers as v', 'v.id', '=', 'a.voucher_id')
                ->where('a.source_document_id', $sourceId)->where('v.status', 'posted')
                ->select('a.amount')->get()
                ->sum(fn ($row): int => $this->cents((string) $row->amount));

            if ($allocated > $total - $paid) {
                throw ValidationException::withMessages(['amount' => 'Amount exceeds outstanding balance.']);
            }

            $voucherId = DB::table('finance_vouchers')->insertGetId([
                'number' => $number,
                'direction' => $snapshot->direction === 'receivable' ? 'receipt' : 'payment',
                'status' => 'draft', 'account_id' => $accountId,
                'currency' => $snapshot->currency, 'amount' => $amount,
                'voucher_date' => now()->toDateString(), 'created_by' => $actorId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('finance_allocations')->insert([
                'voucher_id' => $voucherId, 'source_document_id' => $sourceId,
                'amount' => $amount, 'created_at' => now(), 'updated_at' => now(),
            ]);
            return $voucherId;
        }, 3);
    }

    private function cents(string $amount): int
    {
        if (! preg_match('/^\d{1,15}(?:\.\d{1,2})?$/', $amount)) {
            throw ValidationException::withMessages(['amount' => 'Invalid decimal amount.']);
        }
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function decimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
