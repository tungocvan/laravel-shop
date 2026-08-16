<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class KqlcntService
{
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

        foreach ($contracts as $contract) {
            if (! is_array($contract)) {
                continue;
            }

            $passList = $this->decodeList($contract['contractorPassList'] ?? null);
            $matchedContractors = array_values(array_filter(
                $passList,
                fn (array $item): bool => trim((string) ($item['contractorCode'] ?? '')) === $contractorCode
            ));

            if ($matchedContractors === []) {
                continue;
            }

            $contract['contractorPassListParsed'] = $matchedContractors;
            $matchedContracts[] = $contract;

            foreach ($this->extractVerifiedLots($contract, $contractorCode) as $lot) {
                $key = (string) ($lot['lotNo'] ?? $lot['id'] ?? md5(json_encode($lot)));
                $verifiedLots[$key] = $lot;
            }
        }

        return [
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
            'contractor_code' => $contractorCode,
            'contracts' => $matchedContracts,
            'verified_lots' => array_values($verifiedLots),
            'tbmt_raw' => $tbmt,
            'contracts_raw' => $contracts,
        ];
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

    private function request(): PendingRequest
    {
        return Http::withHeaders([
            'Accept' => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json',
            'Origin' => config('muasamcong.origin'),
            'Referer' => config('muasamcong.referers.kqlcnt'),
            'User-Agent' => config('muasamcong.user_agent'),
            'Cookie' => config('muasamcong.session_cookie'),
        ])->timeout((int) config('muasamcong.timeout', 20))
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
