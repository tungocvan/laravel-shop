<?php

return [
    'route_middleware' => ['web', 'auth:admin', 'permission:view_muasamcong,admin'],
    'api_middleware' => ['api', 'auth:sanctum'],

    'origin' => 'https://muasamcong.mpi.gov.vn',
    'verify_ssl' => true,
    'timeout' => 20,
    'user_agent' => 'Mozilla/5.0 (compatible; Laravel Muasamcong Module)',

    'smart_token' => env('MUASAMCONG_SMART_TOKEN'),
    'session_cookie' => env('MUASAMCONG_SESSION_COOKIE'),

    'endpoints' => [
        'pricing' => 'https://muasamcong.mpi.gov.vn/o/egp-portal-personal-page/services/smart/search_prc',
        'contractor_search' => 'https://muasamcong.mpi.gov.vn/o/egp-portal-contractor-selection-v2/services/smart/search',
    ],

    'referers' => [
        'portal' => 'https://muasamcong.mpi.gov.vn/',
        'pricing' => 'https://muasamcong.mpi.gov.vn/web/guest/profile-info?menu=bid-pricing',
    ],

    'page_size' => 20,
];
