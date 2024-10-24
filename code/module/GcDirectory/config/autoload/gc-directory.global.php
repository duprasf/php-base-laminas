<?php

namespace GcDirectory;

return [
    'service_manager' => [
        'services' => [
            'gc-directory-config' => [
                "secret-token" => getenv("GCDIRECTORY_SECRET_TOKEN"),
                "base-url" => getenv('GCDIRECTORY_API_URL'),
                "username" => getenv('GCDIRECTORY_USER'),
                "password" => getenv('GCDIRECTORY_PASSWORD'),
            ],
        ],
    ],
    /*
    'public_assets' => [
        __NAMESPACE__ => [
            'path' => realpath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'public'),
            'whitelist' => ['css','jpg','jpeg','png','gif',],
        ],
    ],
    /**/
];
