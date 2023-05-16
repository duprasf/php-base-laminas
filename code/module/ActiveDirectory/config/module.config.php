<?php
declare(strict_types=1);

namespace ActiveDirectory;

return [
    'service_manager' => [
        'factories' => [
            Model\ActiveDirectory::class => Factory\ActiveDirectoryFactory::class,
        ],
    ],
];
