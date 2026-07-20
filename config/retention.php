<?php

return [
    'analytics_raw_days' => (int) env('ANALYTICS_RAW_RETENTION_DAYS', 90),
    'analytics_visitor_days' => (int) env('ANALYTICS_VISITOR_RETENTION_DAYS', 760),
    'analytics_rollup_days' => (int) env('ANALYTICS_ROLLUP_RETENTION_DAYS', 1095),
    'store_inquiry_closed_days' => (int) env('STORE_INQUIRY_CLOSED_RETENTION_DAYS', 730),
    'store_inquiry_metadata_days' => (int) env('STORE_INQUIRY_METADATA_RETENTION_DAYS', 90),
    'ai_log_days' => (int) env('AI_LOG_RETENTION_DAYS', 90),
    'activity_log_days' => (int) env('ACTIVITY_LOG_RETENTION_DAYS', 90),
    'resolved_system_error_days' => (int) env('SYSTEM_ERROR_RETENTION_DAYS', 90),
    'media_trash_days' => (int) env('MEDIA_TRASH_RETENTION_DAYS', 30),
];
