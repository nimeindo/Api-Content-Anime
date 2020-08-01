<?php

$config = [
    'query_timeout' => (int) env('TIMEOUT', 500000),
    'environtment' => strtolower(env('APP_ENV', 'local'))
];

return $config;
