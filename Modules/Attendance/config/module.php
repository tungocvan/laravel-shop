<?php

return [
    'name' => 'Attendance',
    'type' => 'domain',
    'enabled' => false,
    'default_enabled' => false,
    'depends' => ['Account'],
    'permissions_required' => true,
    'permissions' => [
        'attendance.dashboard.view',
        'attendance.record.view',
        'attendance.record.adjust',
        'attendance.record.void',
        'attendance.adjustment.view',
        'attendance.adjustment.approve',
        'attendance.shift.view',
        'attendance.shift.manage',
        'attendance.location.view',
        'attendance.location.manage',
        'attendance.export',
        'attendance.audit.view',
    ],
    'permissions_by_guard' => [
        'web' => [
            'client.attendance.access',
            'attendance.record.view-own',
            'attendance.check-in',
            'attendance.check-out',
            'attendance.adjustment.create',
        ],
    ],
    'tables' => [
        'attendance_locations',
        'attendance_shifts',
        'attendance_records',
        'attendance_adjustment_requests',
        'attendance_audit_events',
    ],
];
