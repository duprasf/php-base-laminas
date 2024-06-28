<?php
use Laminas\Session;
return [
    'session_config'=>[
        'name' => 'laminas_basic',
        'cookie_lifetime'=>ini_get('session.gc_maxlifetime')??getenv('PHP_SESSION_TIME')??86400,
        'cookie_secure'=>true,
        'gc_maxlifetime'=>(ini_get('session.gc_maxlifetime')??getenv('PHP_SESSION_TIME')??86400)+3600,
    ],
    'session_storage' => [
        'type' => Session\Storage\SessionArrayStorage::class,
    ],
];
