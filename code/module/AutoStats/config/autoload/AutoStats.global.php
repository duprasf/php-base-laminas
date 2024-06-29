<?php

return [
    'service_manager' => [
        'services' => [
            'ApmLitePublicKey' => file_exists(dirname(__DIR__).DIRECTORY_SEPARATOR.'apm-lite-public-key.pem')
                ? file_get_contents(dirname(__DIR__).DIRECTORY_SEPARATOR.'apm-lite-public-key.pem')
                : false,
        ],
    ],
];
