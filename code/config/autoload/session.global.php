<?php
use Laminas\Session;
return [
    'session_config' => [
        'name' => 'laminas_basic',
    ],
    'session_storage' => [
        'type' => Session\Storage\SessionArrayStorage::class,
    ],
];
