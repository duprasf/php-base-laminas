<?php

namespace LegacySupport;

$return = [
    'service_manager' => [
        'aliases' => [
        ],
        'factories' => [
        ],
    ],
    'view_helpers' => [
        'aliases' => [
            'getServiceLocator' => View\Helper\ServiceLocator::class,
        ],
        'factories' => [
            View\Helper\ServiceLocator::class => Factory\View\Helper\ServiceLocatorFactory::class,
        ],
    ],
];
return $return;
