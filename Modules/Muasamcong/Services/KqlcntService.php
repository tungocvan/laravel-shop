<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\Muasamcong\Models\ContractorManualLot;
use Modules\Muasamcong\Models\ContractorSearchItem;
use RuntimeException;

class KqlcntService
{
    public function __construct(private readonly MuaSamCongService $muasamcong) {}

    public function resolveByNotifyNo(string $notifyNo, string $contractorCode): array
    {
        $notifyNo = trim($notifyNo);
        $contractorCode = trim($contractorCode);

        if ($notifyNo === '' || $contractorCode === '') {
            throw new RuntimeException('Thiếu dữ liệu để tra cứu KQLCNT.');
        }

        $year = $this->yearFromNotifyNo($notifyNo);
        $search = $this->muasamcong->searchHsmt(
            $notifyNo,
            $year.'-01-01',
            now()->addYear()->endOfYear()->toDateString()
        );

        if (! ($search['success'] ?? false)) {
            throw new RuntimeException($search['message'] ?? 'Không thể xác định TBMT trên Cổng Mua sắm công.');
        }

        $item = collect($search['data']['items'] ?? [])
            ->first(fn (mixed $row): bool => is_array($row)
                && trim((string) ($row['notifyNo'] ?? '')) === $notifyNo);

        $notifyId = is_array($item) ? trim((string) ($item['id'] ?? '')) : '';

        if ($notifyId === '') {
            throw new RuntimeException('Không xác định được notifyId của TBMT '.$notifyNo.'.');
        }

        return $this->resolve($notifyId, $notifyNo, $contractorCode);
    }

    public function resolve(string $notifyId, string $notifyNo, string $contractorCode): array
    {
        $notifyId = trim($notifyId);
        $notifyNo = trim($notifyNo);
        $contractorCode = trim($contractorCode);

        if ($notifyId === '' || $notifyNo === '' || $contractorCode === '') {
            throw new RuntimeException('Thiếu dữ liệu để tra cứu KQLCNT.');
        }

        $token = trim((string) config('muasamcong.smart_token'));
        if ($token === '') {
            throw new RuntimeException('Chưa cấu hình MUASAMCONG_SMART_TOKEN để tra cứu KQLCNT.');
        }

        $tbmt = $this->request()
            ->post($this->endpoint('kqlcnt_tbmt_detail', $token), ['id' => $notifyId])
            ->throw()
            ->json();

        if (! is_array($tbmt)) {
            throw new RuntimeException('Cổng Mua sắm công trả về chi tiết TBMT không hợp lệ.');
        }

        $contracts = $this->request()
            ->post($this->endpoint('kqlcnt_contracts', $token), ['notifyNo' => $notifyNo])
            ->throw()
            ->json();

        if (! is_array($contracts)) {
            throw new RuntimeException('Cổng Mua sắm công trả về danh sách hợp đồng không hợp lệ.');
        }

        $matchedContracts = [];
        $verifiedLots = [];
        $allWinners = [];

        foreach ($contracts as $contract) {
            if (! is_array($contract)) {
                continue;
            }

            $contractWinners = $this->contractWinners($contract);
            $contractNo = trim((string) ($contract['contractNo'] ?? ''));

            foreach ($contractWinners as $winner) {
                $winnerCode = trim((string) ($winner['contractorCode'] ?? ''));
                $winnerName = trim((string) ($winner['contractorName'] ?? ''));

                if ($winnerCode === '' && $winnerName === '') {
                    continue;
                }

                $key = $winnerCode !== '' ? $winnerCode : mb_strtoupper($winnerName);
                $allWinners[$key] ??= [
                    'contractorCode' => $winnerCode ?: null,
                    'contractorName' => $winnerName ?: null,
                    'contractorAddress' => $winner['contractorAddress'] ?? null,
                    'contracts' => [],
                ];

                if ($contractNo !== '' && ! in_array($contractNo, $allWinners[$key]['contracts'], true)) {
                    $allWinners[$key]['contracts'][] = $contractNo;
                }
            }

            $matchedContractors = array_values(array_filter(
                $contractWinners,
                fn (array $item): bool => trim((string) ($item['contractorCode'] ?? '')) === $contractorCode
            ));

            if ($matchedContractors === []) {
                continue;
            }

            $contract['contractorPassListParsed'] = $matchedContractors;
            $matchedContracts[] = $contract;

            foreach ($this->extractVerifiedLots($contract, $contractorCode) as $lot) {
                $key = (string) ($lot['lotNo'] ?? $lot['lotCode'] ?? $lot['id'] ?? md5(json_encode($lot)));
                $verifiedLots[$key] = $lot;
            }
        }

        $resolved = [
            'notify_no' => $notifyNo,
            'notify_id' => $notifyId,
            'bid_name' => data_get($tbmt, 'bidoNotifyContractorM.bidName')
                ?? data_get($tbmt, 'bidNoContractorResponse.bidNotification.bidName'),
            'status' => data_get($tbmt, 'bidoBidStatus.status'),
            'published' => (bool) data_get($tbmt, 'bidoBidStatus.published', false),
            'is_medicine' => (bool) (data_get($tbmt, 'bidoNotifyContractorM.isMedicine')
                ?? data_get($tbmt, 'bidNoContractorResponse.bidNotification.isMedicine', false)),
            'bid_id' => data_get($tbmt, 'bidoBidStatus.bidId')
                ?? data_get($tbmt, 'bidoNotifyContractorM.bidId'),
            'investor_code' => data_get($tbmt, 'bidoNotifyContractorM.investorCode')
                ?? data_get($tbmt, 'bidNoContractorResponse.bidNotification.investorCode')
                ?? ($matchedContracts[0]['investorCode'] ?? $contracts[0]['investorCode'] ?? null),
            'investor_name' => data_get($tbmt, 'bidoNotifyContractorM.investorName')
                ?? data_get($tbmt, 'bidNoContractorResponse.bidNotification.investorName')
                ?? ($matchedContracts[0]['investorName'] ?? $contracts[0]['investorName'] ?? null),
            'contractor_code' => $contractorCode,
            'current_contractor_won' => $matchedContracts !== [],
            'contracts' => $matchedContracts,
            'all_winners' => array_values($allWinners),
            'verified_lots' => array_values($verifiedLots),
            'tbmt_raw' => $tbmt,
            'contracts_raw' => $contracts,
        ];

        $this->persistInvestorToHistory($resolved);
        $this->persistVerifiedLots($resolved);

        return $resolved;
    }

    public function normalizeStored(array $record): array
    {
        $contractsRaw = is_array($record['contracts_raw'] ?? null) ? $record['contracts_raw'] : [];
        $allWinners = is_array($record['all_winners'] ?? null) ? $record['all_winners'] : [];

        if ($allWinners === [] && $contractsRaw !== []) {
            $aggregated = [];
            foreach ($contractsRaw as $contract) {
                if (! is_array($contract)) {
                    continue;
                }

                $contractNo = trim((string) ($contract['contractNo'] ?? ''));
                foreach ($this->contractWinners($contract) as $winner) {
                    $code = trim((string) ($winner['contractorCode'] ?? ''));
                    $name = trim((string) ($winner['contractorName'] ?? ''));
                    if ($code === '' && $name === '') {
                        continue;
                    }

                    $key = $code !== '' ? $code : mb_strtoupper($name);
                    $aggregated[$key] ??= [
                        'contractorCode' => $code ?: null,
                        'contractorName' => $name ?: null,
                        'contractorAddress' => $winner['contractorAddress'] ?? null,
                        'contracts' => [],
                    ];
                    if ($contractNo !== '' && ! in_array($contractNo, $aggregated[$key]['contracts'], true)) {
                        $aggregated[$key]['contracts'][] = $contractNo;
                    }
                }
            }
            $allWinners = array_values($aggregated);
        }

        return [
            'notify_no' => $record['notify_no'] ?? null,
            'notify_id' => $record['notify_id'] ?? null,
            'bid_id' => $record['bid_id'] ?? null,
            'bid_name' => $record['bid_name'] ?? null,
            'contractor_code' => $record['contractor_code'] ?? null,
            'investor_code' => $record['investor_code'] ?? null,
            'investor_name' => $record['investor_name'] ?? null,
            'status' => $record['status'] ?? null,
            'published' => (bool) ($record['published'] ?? false),
            'current_contractor_won' => (bool) ($record['current_contractor_won'] ?? false),
            'contracts' => is_array($record['contracts'] ?? null) ? $record['contracts'] : [],
            'all_winners' => $allWinners,
            'verified_lots' => is_array($record['verified_lots'] ?? null) ? $record['verified_lots'] : [],
            'tbmt_raw' => is_array($record['tbmt_raw'] ?? null) ? $record['tbmt_raw'] : [],
            'contracts_raw' => $contractsRaw,
            'source' => 'server',
            'synced_at' => $record['synced_at'] ?? null,
        ];
    }

    private function persistInvestorToHistory(array $resolved): void
    {
        if (! Schema::hasTable('muasamcong_contractor_search_items')
            || ! Schema::hasTable('muasamcong_contractor_searches')) {
            return;
        }

        $notifyNo = trim((string) ($resolved['notify_no'] ?? ''));
        $contractorCode = trim((string) ($resolved['contractor_code'] ?? ''));
        $investorName = trim((string) ($resolved['investor_name'] ?? ''));
        $investorCode = trim((string) ($resolved['investor_code'] ?? ''));

        if ($notifyNo === '' || $contractorCode === '' || ($investorName === '' && $investorCode === '')) {
            return;
        }

        ContractorSearchItem::query()
            ->where('notify_no', $notifyNo)
            ->whereHas('search', fn ($query) => $query->where('contractor_code', $contractorCode))
            ->get()
            ->each(function (ContractorSearchItem $item) use ($investorName, $investorCode): void {
                $payload = is_array($item->raw_payload) ? $item->raw_payload : [];

                if ($investorName !== '') {
                    $payload['investorName'] = $investorName;
                }
                if ($investorCode !== '') {
                    $payload['investorCode'] = $investorCode;
                }

                $item->raw_payload = $payload;
                $item->save();
            });
    }

    private function persistVerifiedLots(array $resolved): void
    {
        if (! Schema::hasTable('muasamcong_contractor_manual_lots')) {
            return;
        }

        $notifyNo = trim((string) ($resolved['notify_no'] ?? ''));
        $contractorCode = trim((string) ($resolved['contractor_code'] ?? ''));
        $verifiedLots = is_array($resolved['verified_lots'] ?? null) ? $resolved['verified_lots'] : [];

        if ($notifyNo === '' || $contractorCode === '') {
            return;
        }

        $verifiedKeys = [];
        foreach ($verifiedLots as $lot) {
            if (! is_array($lot)) {
                continue;
            }

            $lotNo = trim((string) ($lot['lotNo'] ?? $lot['lotCode'] ?? ''));
            if ($lotNo === '') {
                continue;
            }

            $lotKey = 'lot:'.$lotNo;
            $verifiedKeys[] = $lotKey;
            $quantity = $this->numeric($lot['quantity'] ?? $lot['qty'] ?? null);
            $pricePlan = $this->numeric($lot['pricePlan'] ?? $lot['price_plan'] ?? $lot['unitPrice'] ?? null);
            $lotPrice = $this->numeric($lot['lotPrice'] ?? $lot['bidWinningPrice'] ?? $lot['winningPrice'] ?? null);

            ContractorManualLot::query()->updateOrCreate(
                [
                    'contractor_code' => $contractorCode,
                    'notify_no' => $notifyNo,
                    'lot_key' => $lotKey,
                ],
                [
                    'lot_no' => $lotNo,
                    'lot_name' => $lot['lotName'] ?? $lot['medicineName'] ?? $lot['tenThuoc'] ?? null,
                    'medicine_name' => $lot['medicineName'] ?? $lot['tenThuoc'] ?? $lot['lotName'] ?? null,
                    'active_ingredient' => $lot['activeIngredient'] ?? $lot['tenHoatChat'] ?? null,
                    'quantity' => $quantity,
                    'price_plan' => $pricePlan,
                    'lot_price' => $lotPrice,
                    'plan_amount' => $quantity !== null && $pricePlan !== null ? $quantity * $pricePlan : null,
                    'source' => 'kqlcnt_verified',
                    'confirmed_by' => null,
                    'confirmed_at' => now(),
                    'raw_payload' => $lot,
                ]
            );
        }

        $stale = ContractorManualLot::query()
            ->where('contractor_code', $contractorCode)
            ->where('notify_no', $notifyNo)
            ->where('source', 'kqlcnt_verified');

        if ($verifiedKeys === []) {
            $stale->delete();
        } else {
            $stale->whereNotIn('lot_key', $verifiedKeys)->delete();
        }
    }

    private function contractWinners(array $contract): array
    {
        $directCode = trim((string) ($contract['contractorCode'] ?? ''));
        $directName = trim((string) ($contract['newContractorName'] ?? $contract['contractorName'] ?? ''));

        if ($directCode !== '' || $directName !== '') {
            return [[
                'contractorCode' => $directCode ?: null,
                'contractorName' => $directName ?: null,
                'contractorAddress' => $contract['contractorAddress'] ?? null,
            ]];
        }

        return array_values(array_filter(
            $this->decodeList($contract['contractorPassList'] ?? null),
            fn (mixed $winner): bool => is_array($winner)
        ));
    }

    private function extractVerifiedLots(array $contract, string $contractorCode): array
    {
        $lotResult = $this->decodeList($contract['lotResultDTO'] ?? null);
        $verified = [];

        foreach ($lotResult as $result) {
            if (! is_array($result)) {
                continue;
            }

            foreach (['listTablePrice', 'listTablePrice1', 'listTablePrice2', 'listTablePrice3'] as $field) {
                foreach ((array) ($result[$field] ?? []) as $row) {
                    if (! is_array($row)) {
                        continue;
                    }

                    $rowContractorCode = trim((string) ($row['contractorCode'] ?? $row['winningCode'] ?? ''));
                    $lotNo = trim((string) ($row['lotNo'] ?? $row['lotCode'] ?? ''));

                    if ($rowContractorCode !== $contractorCode || $lotNo === '') {
                        continue;
                    }

                    $verified[] = $row;
                }
            }
        }

        return $verified;
    }

    private function decodeList(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function numeric(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function yearFromNotifyNo(string $notifyNo): int
    {
        if (preg_match('/^[A-Z]{2}(\d{2})/i', $notifyNo, $matches) === 1) {
            return 2000 + (int) $matches[1];
        }

        return 2021;
    }

    private function request(): PendingRequest
    {
        $headers = [
            'Accept' => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json',
            'Origin' => config('muasamcong.origin'),
            'Referer' => config('muasamcong.referers.kqlcnt'),
            'User-Agent' => config('muasamcong.user_agent'),
        ];

        $cookie = trim((string) config('muasamcong.session_cookie'));
        if ($cookie !== '') {
            $headers['Cookie'] = $cookie;
        }

        return Http::withHeaders($headers)
            ->timeout((int) config('muasamcong.timeout', 20))
            ->withOptions(['verify' => (bool) config('muasamcong.verify_ssl', true)]);
    }

    private function endpoint(string $key, string $token): string
    {
        $endpoint = (string) config("muasamcong.endpoints.{$key}");
        $host = parse_url($endpoint, PHP_URL_HOST);
        $scheme = parse_url($endpoint, PHP_URL_SCHEME);

        if ($scheme !== 'https' || ! hash_equals((string) config('muasamcong.allowed_host'), (string) $host)) {
            throw new RuntimeException('Endpoint KQLCNT không được phép.');
        }

        return $endpoint.'?'.http_build_query(['token' => $token], '', '&', PHP_QUERY_RFC3986);
    }
}
