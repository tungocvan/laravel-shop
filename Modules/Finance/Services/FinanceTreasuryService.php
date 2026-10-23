<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class FinanceTreasuryService
{
    public function balances(): array
    {
        $posted = DB::table('finance_ledger_entries')
            ->select('account_id')->selectRaw('SUM(signed_amount) as net')
            ->groupBy('account_id');

        $outgoing = DB::table('finance_internal_transfers')
            ->select('from_account_id')->selectRaw('SUM(amount) as total')
            ->groupBy('from_account_id');
        $incoming = DB::table('finance_internal_transfers')
            ->select('to_account_id')->selectRaw('SUM(amount) as total')
            ->groupBy('to_account_id');

        return DB::table('finance_accounts as a')
            ->leftJoinSub($posted, 'ledger', fn ($join) => $join->on('ledger.account_id', '=', 'a.id'))
            ->leftJoinSub($outgoing, 'outgoing', fn ($join) => $join->on('outgoing.from_account_id', '=', 'a.id'))
            ->leftJoinSub($incoming, 'incoming', fn ($join) => $join->on('incoming.to_account_id', '=', 'a.id'))
            ->select('a.id', 'a.code', 'a.name', 'a.kind', 'a.currency', 'a.is_active', 'a.opening_balance')
            ->selectRaw('(a.opening_balance + COALESCE(ledger.net, 0) - COALESCE(outgoing.total, 0) + COALESCE(incoming.total, 0)) as balance')
            ->orderBy('a.kind')->orderBy('a.code')->get()->all();
    }

    public function setOpeningBalance(int $accountId, string $amount): void
    {
        $value = $this->validateAmount($amount, true);
        DB::transaction(function () use ($accountId, $value): void {
            $account = DB::table('finance_accounts')->where('id', $accountId)->lockForUpdate()->first();
            if (! $account) {
                throw ValidationException::withMessages(['account' => 'Account not found.']);
            }
            $hasActivity = DB::table('finance_ledger_entries')->where('account_id', $accountId)->exists()
                || DB::table('finance_internal_transfers')->where('from_account_id', $accountId)->orWhere('to_account_id', $accountId)->exists();
            if ($hasActivity) {
                throw ValidationException::withMessages(['account' => 'Opening balance is locked after the first transaction.']);
            }
            DB::table('finance_accounts')->where('id', $accountId)->update(['opening_balance' => $value, 'updated_at' => now()]);
        }, 3);
    }

    public function transfer(int $fromId, int $toId, string $amount, string $date, string $description, ?int $actorId): int
    {
        $value = $this->validateAmount($amount);
        if ($fromId === $toId || trim($description) === '' || mb_strlen($description) > 255
            || ! \DateTimeImmutable::createFromFormat('!Y-m-d', $date)
            || \DateTimeImmutable::createFromFormat('!Y-m-d', $date)->format('Y-m-d') !== $date) {
            throw ValidationException::withMessages(['transfer' => 'Invalid transfer details.']);
        }
        return DB::transaction(function () use ($fromId, $toId, $value, $date, $description, $actorId): int {
            $accounts = DB::table('finance_accounts')->whereIn('id', [$fromId, $toId])->orderBy('id')->lockForUpdate()->get();
            if ($accounts->count() !== 2 || $accounts->contains(fn ($account) => ! $account->is_active)
                || $accounts[0]->currency !== $accounts[1]->currency) {
                throw ValidationException::withMessages(['account' => 'Accounts must be active and use the same currency.']);
            }
            $source = collect($this->balances())->firstWhere('id', $fromId);
            if (! $source || $this->cents((string) $source->balance) < $this->cents($value)) {
                throw ValidationException::withMessages(['amount' => 'Insufficient available account balance.']);
            }
            return DB::table('finance_internal_transfers')->insertGetId([
                'number' => 'CKNB-'.strtoupper((string) \Illuminate\Support\Str::ulid()),
                'from_account_id' => $fromId, 'to_account_id' => $toId,
                'amount' => $value, 'transfer_date' => $date,
                'description' => trim($description), 'created_by' => $actorId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);
    }

    private function validateAmount(string $value, bool $allowZero = false): string
    {
        if (! preg_match('/^\d{1,15}(?:\.\d{1,2})?$/', $value) || (! $allowZero && $this->cents($value) <= 0)) {
            throw ValidationException::withMessages(['amount' => 'Invalid transfer amount.']);
        }
        return number_format($this->cents($value) / 100, 2, '.', '');
    }

    private function cents(string $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
