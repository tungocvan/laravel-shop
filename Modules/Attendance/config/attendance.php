<?php

return [
    'shift' => [
        'start_time' => '08:00',
        'end_time' => '17:00',
        'late_grace_minutes' => 5,
        'early_leave_grace_minutes' => 5,
    ],
    'geofence' => [
        'default_radius_meters' => 150,
        'maximum_accuracy_meters' => 100,
    ],
    'privacy' => [
        // Raw employee GPS is short-lived verification evidence, not tracking history.
        'raw_gps_retention_days' => (int) env('ATTENDANCE_RAW_GPS_RETENTION_DAYS', 30),
    ],
    'geocoding' => [
        'enabled' => (bool) env('ATTENDANCE_GEOCODING_ENABLED', true),
        'endpoint' => env('ATTENDANCE_GEOCODING_ENDPOINT', 'https://nominatim.openstreetmap.org/search'),
        'timeout_seconds' => (int) env('ATTENDANCE_GEOCODING_TIMEOUT_SECONDS', 8),
    ],
];
