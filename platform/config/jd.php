<?php

return [
    'drafting' => [
        'driver' => env('JD_DRAFTING_DRIVER', 'unavailable'),
        'endpoint' => env('JD_DRAFTING_ENDPOINT', ''),
        'model' => env('JD_DRAFTING_MODEL', ''),
        'api_key' => env('JD_DRAFTING_API_KEY', ''),
        'timeout' => (int) env('JD_DRAFTING_TIMEOUT', 45),
    ],
];
