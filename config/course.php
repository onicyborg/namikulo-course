<?php

return [
    'access_months' => (int) env('COURSE_ACCESS_MONTHS', 6),
    'pending_order_ttl_hours' => (int) env('COURSE_PENDING_ORDER_TTL_HOURS', 24),
];
