<?php

$config['default_prefix'] = env('CACHE_PREFIX', 'api_content_anime_v1');
// $config['redis_ads'] = 'api_content:content:ads:';
$config['redis_cron_v1'] = 'v1:lib:';

return $config;
