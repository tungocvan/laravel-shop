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

    /**
     * Store account metadata in Finance only; never place bank details in source modules.
     * Existing callers may continue creating accounts without optional metadata.
     */
    public function createDetailedAccount(string $code, string $name, string $kind, array $details = []): int
    {
        $allowed = ['bank_name', 'bank_account_number', 'bank_account_holder', 'cash_custodian', 'cash_location'];
        if (array_diff(array_keys($details), $allowed)) {
            throw ValidationException::withMessages(['account' => 'Unknown account metadata.']);
        }
        $isBank = $kind === 'bank';
        $required = $isBank ? ['bank_name', 'bank_account_number', 'bank_account_holder'] : ['cash_custodian', 'cash_location'];
        if (! in_array($kind, ['bank', 'cash'], true)) {
            throw ValidationException::withMessages(['account' => 'Unsupported account type.']);
        }
        $metadata = [];
        foreach ($allowed as $field) {
            $value = trim((string) ($details[$field] ?? ''));
            if (mb_strlen($value) > 255) {
                throw ValidationException::withMessages([$field => 'Account metadata is too long.']);
            }
            if (in_array($field, $required, true) && $value === '') {
                throw ValidationException::withMessages([$field => 'This field is required.']);
            }
            $metadata[$field] = $value === '' ? null : $value;
        }
        foreach ($isBank ? ['cash_custodian', 'cash_location'] : ['bank_name', 'bank_account_number', 'bank_account_holder'] as $unused) {
            $metadata[$unused] = null;
        }
        return DB::transaction(function () use ($code, $name, $kind, $metadata): int {
            $id = $this->createAccount($code, $name, $kind);
            DB::table('finance_accounts')->where('id', $id)->update($metadata);
            return $id;
        });
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
            $reserved = $source ? $this->reservedCents((int) $source->id) : 0;
            return [
                'reserved' => $this->decimal($reserved),
                'available' => $this->decimal(max(0, $total - $paid - $reserved)),
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

            if ($allocated > $total - $paid - $this->reservedCents((int) $sourceId)) {
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

    /**
     * Manual cash movements have no source allocation. They are not automatic
     * settlements of advances, deposits or Pharma documents.
     */
    public function createManualDraft(
        string $direction,
        string $category,
        string $counterparty,
        string $description,
        int $accountId,
        string $amount,
        string $number,
        string $voucherDate,
        ?int $actorId = null,
        ?string $referenceNumber = null,
    ): int {
        return DB::transaction(function () use ($direction, $category, $counterparty, $description, $accountId, $amount, $number, $voucherDate, $actorId, $referenceNumber): int {
            $this->validateManualFields($direction, $category, $counterparty, $description, $amount, $number, $voucherDate, $referenceNumber);
            $account = DB::table('finance_accounts')->where('id', $accountId)->lockForUpdate()->first();
            if (! $account || ! $account->is_active || $account->currency !== 'VND') {
                throw ValidationException::withMessages(['account' => 'Active VND account is required.']);
            }
            return DB::table('finance_vouchers')->insertGetId([
                'number' => trim($number), 'direction' => $direction, 'origin' => 'manual',
                'manual_category' => trim($category), 'counterparty' => trim($counterparty),
                'description' => trim($description), 'reference_number' => $referenceNumber === null ? null : trim($referenceNumber),
                'status' => 'draft', 'account_id' => $accountId, 'currency' => 'VND',
                'amount' => $amount, 'voucher_date' => $voucherDate, 'created_by' => $actorId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);
    }

    public function updateManualDraft(
        int $voucherId,
        int $accountId,
        string $amount,
        string $category,
        string $counterparty,
        string $description,
        string $voucherDate,
        ?string $referenceNumber = null,
    ): void {
        DB::transaction(function () use ($voucherId, $accountId, $amount, $category, $counterparty, $description, $voucherDate, $referenceNumber): void {
            $voucher = DB::table('finance_vouchers')->where('id', $voucherId)->lockForUpdate()->first();
            if (! $voucher || $voucher->status !== 'draft' || $voucher->origin !== 'manual') {
                throw ValidationException::withMessages(['voucher' => 'Only manual draft vouchers can be edited here.']);
            }
            $this->validateManualFields($voucher->direction, $category, $counterparty, $description, $amount, $voucher->number, $voucherDate, $referenceNumber);
            $account = DB::table('finance_accounts')->where('id', $accountId)->lockForUpdate()->first();
            if (! $account || ! $account->is_active || $account->currency !== $voucher->currency) {
                throw ValidationException::withMessages(['account' => 'Active matching account required.']);
            }
            if (DB::table('finance_allocations')->where('voucher_id', $voucherId)->exists()) {
                throw ValidationException::withMessages(['voucher' => 'Manual vouchers cannot have source allocations.']);
            }
            DB::table('finance_vouchers')->where('id', $voucherId)->update([
                'account_id' => $accountId, 'amount' => $amount,
                'manual_category' => trim($category), 'counterparty' => trim($counterparty),
                'description' => trim($description), 'voucher_date' => $voucherDate,
                'reference_number' => $referenceNumber === null ? null : trim($referenceNumber),
                'updated_at' => now(),
            ]);
        }, 3);
    }

    private function validateManualFields(
        string $direction, string $category, string $counterparty, string $description,
        string $amount, string $number, string $voucherDate, ?string $referenceNumber
    ): void {
        if (! in_array($direction, ['receipt', 'payment'], true)) {
            throw ValidationException::withMessages(['direction' => 'Invalid manual voucher direction.']);
        }
        if (trim($category) === '' || mb_strlen(trim($category)) > 60
            || trim($counterparty) === '' || mb_strlen(trim($counterparty)) > 255
            || trim($description) === '' || mb_strlen(trim($description)) > 5000
            || trim($number) === '' || strlen(trim($number)) > 50
            || ($referenceNumber !== null && mb_strlen(trim($referenceNumber)) > 100)
            || ! preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $voucherDate)
            || ! checkdate((int) substr($voucherDate, 5, 2), (int) substr($voucherDate, 8, 2), (int) substr($voucherDate, 0, 4))
            || $this->cents($amount) <= 0) {
            throw ValidationException::withMessages(['voucher' => 'Invalid manual voucher details.']);
        }
    }

    private function reservedCents(int $sourceId, ?int $exceptVoucherId = null): int
    {
        return DB::table('finance_allocations as a')
            ->join('finance_vouchers as v', 'v.id', '=', 'a.voucher_id')
            ->where('a.source_document_id', $sourceId)
            ->where('v.status', 'draft')
            ->when($exceptVoucherId !== null, fn ($query) => $query->where('v.id', '!=', $exceptVoucherId))
            ->select('a.amount')->get()
            ->sum(fn ($row): int => $this->cents((string) $row->amount));
    }

    public function updateDraft(int $voucherId, int $accountId, string $amount): void
    {
        DB::transaction(function () use ($voucherId, $accountId, $amount): void {
            $voucher = DB::table('finance_vouchers')->where('id', $voucherId)->lockForUpdate()->first();
            if (! $voucher || $voucher->status !== 'draft') {
                throw ValidationException::withMessages(['voucher' => 'Only draft vouchers can be edited.']);
            }
            $account = DB::table('finance_accounts')->where('id', $accountId)->lockForUpdate()->first();
            if (! $account || ! $account->is_active || $account->currency !== $voucher->currency) {
                throw ValidationException::withMessages(['account' => 'Active matching account required.']);
            }
            $allocations = DB::table('finance_allocations')->where('voucher_id', $voucherId)->lockForUpdate()->get();
            if (($voucher->origin ?? 'source') === 'manual' || $allocations->count() !== 1) {
                throw ValidationException::withMessages(['voucher' => 'Only single-source drafts can be edited.']);
            }
            $allocation = $allocations->first();
            $source = DB::table('finance_source_documents')->where('id', $allocation->source_document_id)->lockForUpdate()->first();
            if (! $source) {
                throw ValidationException::withMessages(['source' => 'Source unavailable.']);
            }
            $snapshot = $this->providers->resolve($source->source_module, $source->source_type)
                ->resolveForPosting((string) $source->source_document_id);
            if ($snapshot->documentCode !== $source->source_document_code
                || $snapshot->direction !== $source->direction
                || $snapshot->currency !== $source->currency
                || $this->cents($snapshot->amount) !== $this->cents((string) $source->original_amount)) {
                throw ValidationException::withMessages(['source' => 'Source document has changed.']);
            }
            $allocated = $this->cents($amount);
            $paid = DB::table('finance_allocations as a')
                ->join('finance_vouchers as v', 'v.id', '=', 'a.voucher_id')
                ->where('a.source_document_id', $source->id)->where('v.status', 'posted')
                ->select('a.amount')->get()
                ->sum(fn ($row): int => $this->cents((string) $row->amount));
            if ($allocated <= 0 || $allocated > $this->cents($snapshot->amount) - $paid - $this->reservedCents((int) $source->id, $voucherId)) {
                throw ValidationException::withMessages(['amount' => 'Draft exceeds unreserved outstanding balance.']);
            }
            DB::table('finance_vouchers')->where('id', $voucherId)->update([
                'account_id' => $accountId, 'amount' => $amount, 'updated_at' => now(),
            ]);
            DB::table('finance_allocations')->where('id', $allocation->id)->update([
                'amount' => $amount, 'updated_at' => now(),
            ]);
        }, 3);
    }

    public function cancelDraft(int $voucherId): void
    {
        DB::transaction(function () use ($voucherId): void {
            $voucher = DB::table('finance_vouchers')->where('id', $voucherId)->lockForUpdate()->first();
            if (! $voucher || $voucher->status !== 'draft') {
                throw ValidationException::withMessages(['voucher' => 'Only draft vouchers can be cancelled.']);
            }
            DB::table('finance_vouchers')->where('id', $voucherId)->update([
                'status' => 'cancelled', 'updated_at' => now(),
            ]);
        }, 3);
    }

    /**
     * Destructive cleanup is restricted to vouchers never posted to the ledger.
     * Caller must separately enforce the privileged administrator role.
     */
    public function permanentlyDeleteUnposted(int $voucherId): void
    {
        DB::transaction(function () use ($voucherId): void {
            $voucher = DB::table('finance_vouchers')->where('id', $voucherId)->lockForUpdate()->first();
            if (! $voucher || ! in_array($voucher->status, ['draft', 'cancelled'], true)
                || $voucher->posted_at !== null || $voucher->reversed_at !== null
                || DB::table('finance_ledger_entries')->where('voucher_id', $voucherId)->exists()) {
                throw ValidationException::withMessages(['voucher' => 'Only never-posted drafts may be permanently deleted.']);
            }

            DB::table('finance_allocations')->where('voucher_id', $voucherId)->delete();
            DB::table('finance_vouchers')->where('id', $voucherId)->delete();
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
