<?php
namespace UserAuth;

// https://packagist.org/packages/rwoverdijk/assetmanager
return [
    'asset_manager' => [
        'resolver_configs' => [
            'collections' => [
            ],
            'paths' => [
                realpath(dirname(dirname(__DIR__)) . '/public'),
            ],
            'map' => [
            ],
        ],
        'filters' => [
        ],
        'view_helper' => [
            'append_timestamp' => false,                      // optional, if false never append a query param
            'query_string'     => '_',                       // optional
        ],
        'caching' => [
        ],
    ],
];
