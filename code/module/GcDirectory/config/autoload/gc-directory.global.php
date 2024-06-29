<?php

namespace GcDirectory;

return array(
    'service_manager' => [
        'services' => [
            'gc-directory-config' => [
                "secret-token" => getenv("GC_DIRECTORY_SECRET_TOKEN"),
                "base-url" => 'https://geds20api-sage20api.itsso.gc.ca/gapi/v2',
            ],
        ],
    ],
    'public_assets' => array(
        __NAMESPACE__ => array(
            'path' => realpath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'public'),
            'whitelist' => array('css','jpg','jpeg','png','gif',),
        ),
    ),
);
