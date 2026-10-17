<?php

return [
    'cron_token' => env('OPERATIONS_CRON_TOKEN', ''),
    'batch_size' => (int) env('OPERATIONS_BATCH_SIZE', 20),
    'notification_digest_batch_size' => (int) env('NOTIFICATION_DIGEST_BATCH_SIZE', 3),
    'max_seconds' => (int) env('OPERATIONS_MAX_SECONDS', 10),
    'playback_retention_days' => (int) env('OPERATIONS_PLAYBACK_RETENTION_DAYS', 30),
    'upload_stale_hours' => (int) env('OPERATIONS_UPLOAD_STALE_HOURS', 24),
];
