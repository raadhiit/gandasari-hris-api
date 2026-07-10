<?php

return [
    'attendance' => [
        'start_id' => (int) env('ATTENDANCE_SYNC_START_ID', 0),
        'default_limit' => (int) env('ATTENDANCE_SYNC_DEFAULT_LIMIT', 100),
        'max_limit' => (int) env('ATTENDANCE_SYNC_MAX_LIMIT', 500),
        'timezone' => env('ATTENDANCE_TIMEZONE', 'Asia/Jakarta'),
    ],
];
