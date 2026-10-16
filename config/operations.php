<?php

return [
    'cron_token' => env('OPERATIONS_CRON_TOKEN', ''),
    'batch_size' => (int) env('OPERATIONS_BATCH_SIZE', 20),
    'max_seconds' => (int) env('OPERATIONS_MAX_SECONDS', 10),
];
