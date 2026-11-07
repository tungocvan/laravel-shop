<?php

namespace Modules\Pharma\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class DavForeignGmpLookup
{
    private const URL = 'https://dichvucong.dav.gov.vn/api/services/app/danhSachCoSoDuocChungNhanGMPNuocNgoai/getAllPublicServerPaging';

    public const PER_PAGE = 15;

    public function search(string $keyword = '', int $page = 1): array
    {
        $keyword = trim($keyword);
        $page = max(1, min(1000, $page));

        return Cache::remember('pharma:foreign-gmp:v1:'.hash('sha256', $keyword.'|'.$page), now()->addMinutes(5), function () use ($keyword, $page): array {
            try {
                // Public API: do not forward browser cookies, session or XSRF credentials.
                $response = Http::acceptJson()->asJson()->connectTimeout(5)->timeout(15)
                    ->post(self::URL, [
                        'filter' => $keyword,
                        'thongTinCoSoGMPNuocNgoai' => (object) [],
                        'skipCount' => ($page - 1) * self::PER_PAGE,
                        'maxResultCount' => self::PER_PAGE,
                        'sorting' => null,
                    ]);
            } catch (ConnectionException $exception) {
                throw new RuntimeException('Không kết nối được nguồn GMP DAV.', 0, $exception);
            }

            $payload = $response->json();
            if (! $response->successful() || ! is_array($payload)
                || ($payload['success'] ?? false) !== true
                || ! is_array(data_get($payload, 'result.items'))
                || ! is_numeric(data_get($payload, 'result.totalCount'))) {
                throw new RuntimeException('Nguồn GMP DAV không trả về dữ liệu hợp lệ.');
            }

            return [
                'total' => max(0, (int) data_get($payload, 'result.totalCount')),
                'items' => array_values(array_map(static fn (array $row): array => [
                    'source_id' => (string) ($row['id'] ?? ''),
                    'certificate_record_id' => (string) ($row['idCC'] ?? ''),
                    'company_code' => (string) ($row['idCongTy'] ?? ''),
                    'name' => (string) ($row['tenCoSo'] ?? ''),
                    'address' => (string) ($row['diaChiCoSo'] ?? ''),
                    'scope' => (string) ($row['phamViChungNhan'] ?? ''),
                    'standard' => (string) ($row['nguyenTacGMP'] ?? ''),
                    'certificate' => (string) ($row['giayChungNhan'] ?? ''),
                    'authority' => (string) ($row['coQuanCap'] ?? ''),
                    'publication_batch' => (string) ($row['dotCongBo'] ?? ''),
                    'issued_at' => (string) ($row['ngayCap'] ?? ''),
                    'expires_at' => (string) ($row['ngayHetHan'] ?? ''),
                    'active' => ($row['isActive'] ?? false) === true,
                ], array_filter(data_get($payload, 'result.items'), 'is_array'))),
            ];
        });
    }

    public function findOnPage(string $keyword, int $page, string $sourceId): ?array
    {
        foreach ($this->search($keyword, $page)['items'] as $item) {
            if ($item['source_id'] !== '' && hash_equals($item['source_id'], $sourceId)) {
                return $item;
            }
        }

        return null;
    }
}
