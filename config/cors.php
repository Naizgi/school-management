<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'https://mellainnovation.com',
        'https://api.mellainnovation.com',
        'https://lms.mellainnovation.com', // ✅ Add your frontend domain here
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 60 * 60 * 3, // 3 hours

    'supports_credentials' => true,
];
