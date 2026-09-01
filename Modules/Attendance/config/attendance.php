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
        'raw_gps_retention_months' => 12,
    ],
];
