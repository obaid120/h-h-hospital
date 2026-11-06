<?php

return [
    'allow_self_signed_demo_tls' => env('DEMO_TLS_ALLOW_SELF_SIGNED', false),

    'trusted_proxies' => env('TRUSTED_PROXIES') === '*'
        ? '*'
        : array_values(array_filter(array_map(trim(...), explode(',', (string) env('TRUSTED_PROXIES', ''))))),
];
