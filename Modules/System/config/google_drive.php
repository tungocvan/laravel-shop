<?php

return [
    'client_id' => env('GOOGLE_DRIVE_CLIENT_ID', ''),
    'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET', ''),
    'redirect_uri' => env('GOOGLE_DRIVE_REDIRECT_URI', ''),
    'folder_name' => env('GOOGLE_DRIVE_FOLDER_NAME', 'Laravel-Backup'),
    'scopes' => [
        'openid',
        'email',
        'https://www.googleapis.com/auth/drive.file',
    ],
];
