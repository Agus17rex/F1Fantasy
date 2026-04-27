<?php

return [
    'f1_api' => [
        'base_url' => env('F1_API_BASE', 'https://api.jolpi.ca/ergast/f1'),
        'season'   => env('F1_API_SEASON', date('Y')),
    ],
];
