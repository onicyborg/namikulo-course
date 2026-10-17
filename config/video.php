<?php

return [
    'disk' => env('VIDEO_DISK', 'video_private'),
    'source_max_size_mb' => (int) env('VIDEO_SOURCE_MAX_SIZE_MB', 200),
    'source_max_duration_seconds' => (int) env('VIDEO_SOURCE_MAX_DURATION_SECONDS', 900),
    'upload_max_size_mb' => (int) env('VIDEO_UPLOAD_MAX_SIZE_MB', 250),
    'segment_max_size_mb' => (int) env('VIDEO_SEGMENT_MAX_SIZE_MB', 10),
    'max_segments' => (int) env('VIDEO_MAX_SEGMENTS', 2000),
    'chunk_max_bytes' => 5 * 1024 * 1024,
    'manifest_max_bytes' => 1024 * 1024,
    'max_width' => 1280,
    'max_height' => 720,
    'segment_seconds' => 6,
    'session_ttl_minutes' => (int) env('VIDEO_SESSION_TTL_MINUTES', 10),
    'heartbeat_seconds' => (int) env('VIDEO_HEARTBEAT_SECONDS', 60),
    'accel_redirect' => (bool) env('VIDEO_ACCEL_REDIRECT', false),
    'ffmpeg_version' => '0.12.15',
    'ffmpeg_core_version' => '0.12.10',
    'hls_js_version' => '1.7.3',
];
