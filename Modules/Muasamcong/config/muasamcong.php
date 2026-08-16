<?php

return [
    'route_middleware' => ['web', 'auth:admin'],
    'view_middleware' => ['permission:view_muasamcong,admin'],
    'config_middleware' => ['permission:muasamcong.config.manage,admin'],
    'api_middleware' => ['api', 'auth:sanctum'],

    'allowed_host' => 'muasamcong.mpi.gov.vn',

    'origin' => env('MUASAMCONG_ORIGIN', 'https://muasamcong.mpi.gov.vn'),
    'verify_ssl' => filter_var(env('MUASAMCONG_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),
    'timeout' => max(1, min(120, (int) env('MUASAMCONG_TIMEOUT', 20))),
    'user_agent' => env('MUASAMCONG_USER_AGENT', 'Mozilla/5.0 (compatible; Laravel Muasamcong Module)'),

    'smart_token' => env('MUASAMCONG_SMART_TOKEN'),
    'session_cookie' => env('MUASAMCONG_SESSION_COOKIE'),

    'endpoints' => [
        'pricing' => env(
            'MUASAMCONG_PRICING_ENDPOINT',
            'https://muasamcong.mpi.gov.vn/o/egp-portal-personal-page/services/smart/search_prc'
        ),
        'contractor_search' => env(
            'MUASAMCONG_CONTRACTOR_ENDPOINT',
            'https://muasamcong.mpi.gov.vn/o/egp-portal-contractor-selection-v2/services/smart/search'
        ),
    ],

    'referers' => [
        'portal' => env('MUASAMCONG_PORTAL_REFERER', 'https://muasamcong.mpi.gov.vn/'),
        'pricing' => env(
            'MUASAMCONG_PRICING_REFERER',
            'https://muasamcong.mpi.gov.vn/web/guest/profile-info?menu=bid-pricing'
        ),
    ],

    'page_size' => max(1, min(100, (int) env('MUASAMCONG_PAGE_SIZE', 20))),
];
