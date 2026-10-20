<?php

return [
    'enabled' => (bool) env('POLLINATIONS_ENABLED', false),
    // Same keyed API used by TOOL-MKT. Classic is an explicit, restricted test option.
    'mode' => env('POLLINATIONS_MODE', 'keyed'),
    'api_key' => env('POLLINATIONS_API_KEY', ''),
    'model' => env('POLLINATIONS_IMAGE_MODEL', 'flux'),
    'timeout' => 90,
    'max_bytes' => 12 * 1024 * 1024,
    'daily_limit' => (int) env('POLLINATIONS_DAILY_LIMIT', 10),
    'queue' => 'marketing-images',
];
