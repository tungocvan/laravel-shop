<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Read-only public tender pricing adapter owned by Pharma.
 * Does not resolve Muasamcong services, models, config, routes or permissions.
 */
final class PublicAwardPriceLookup
{
    private const ENDPOINT = 'https://muasamcong.mof.gov.vn/o/egp-portal-personal-page/services/smart/search_prc';
    private const REFERER = 'https://muasamcong.mof.gov.vn/web/guest/profile-info?menu=bid-pricing';

    public function search(string $keyword, int $page = 1, bool $fresh = false): array
    {
        $keyword = trim($keyword);
        if (mb_strlen($keyword) < 2 || mb_strlen($keyword) > 200) {
            return ['success' => false, 'items' => [], 'total' => 0, 'message' => 'Từ khóa phải có 2–200 ký tự.'];
        }

        $page = max(1, min(100, $page));
        try {
            $cacheKey = 'pharma:award-lookup:'.hash('sha256', mb_strtolower($keyword).'|'.$page);
            if ($fresh) {
                Cache::forget($cacheKey);
            }
            return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($keyword, $page): array {
                $response = Http::acceptJson()
                    ->asJson()
                    ->withHeaders([
                        'Origin' => 'https://muasamcong.mof.gov.vn',
                        'Referer' => self::REFERER,
                    ])
                    ->withOptions(['verify' => true, 'allow_redirects' => false])
                    ->connectTimeout(5)->timeout(20)
                    ->post(self::ENDPOINT, [[
                        'pageSize' => 20,
                        'pageNumber' => $page - 1,
                        'query' => [[
                            'index' => 'es-smart-pricing',
                            'keyWord' => $keyword,
                            'keyWordNotMatch' => '',
                            'matchType' => 'exact',
                            'matchFields' => ['ten_thuoc', 'ten_hoat_chat', 'ma_tbmt', 'winning_name'],
                            'filters' => [
                                ['fieldName' => 'medicines', 'searchType' => 'in', 'fieldValues' => ['0']],
                                ['fieldName' => 'type', 'searchType' => 'in', 'fieldValues' => ['HANG_HOA']],
                                ['fieldName' => 'tab', 'searchType' => 'in', 'fieldValues' => ['THUOC_TAN_DUOC']],
                            ],
                        ]],
                    ]]);

                if (! $response->successful()) {
                    throw new \RuntimeException('HTTP '.$response->status());
                }

                $json = $response->json();
                $page = is_array($json) ? ($json['page'] ?? null) : null;
                if (! is_array($page) || ! is_array($page['content'] ?? null)) {
                    throw new \RuntimeException('Invalid pricing response');
                }

                return [
                    'success' => true,
                    'items' => array_values(array_filter($page['content'], 'is_array')),
                    'total' => max(0, (int) ($page['totalElements'] ?? count($page['content']))),
                    'message' => null,
                ];
            });
        } catch (Throwable $exception) {
            report($exception);

            return ['success' => false, 'items' => [], 'total' => 0, 'message' => 'Không thể tra cứu giá trúng thầu lúc này. Vui lòng thử lại sau.'];
        }
    }
}
