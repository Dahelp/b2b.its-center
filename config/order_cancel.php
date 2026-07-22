<?php

return [
    'host' => (string)getenv('API_1C_HOST'),
    'base' => (string)(getenv('API_1C_BASE') ?: 'trade'),
    'hs' => 'hs',
    'service' => 'site-exchange',
    'method' => 'close-order',
    'auth' => [
        (string)getenv('API_1C_USER'),
        (string)getenv('API_1C_PASSWORD'),
    ],
];
