<?php

namespace GcDirectory;

return [
    'service_manager' => [
        'factories' => [
            Model\GcDirectory::class => Factory\Model\GcDirectoryFactory::class,
        ],
        "aliases" => [
            "GcDirectory" => Model\GcDirectory::class,
        ],
    ],
    'translator' => [
        'locale' => 'en_CA',
        'translation_file_patterns' => [
            [
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __NAMESPACE__ => __DIR__ . '/../view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
];
