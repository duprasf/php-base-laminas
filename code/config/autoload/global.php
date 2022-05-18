<?php

/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceManager;

return [
    'router' => [
        'router_class' => Laminas\Mvc\I18n\Router\TranslatorAwareTreeRouteStack::class,
    ],
    'service_manager' => [
        'initializers' => [
        ],
        'services'=>[
            'supportedLang'=>['en'=>'English', 'fr'=>'French'],

            // The default metadata if the page did not provide some/all of it
            'default-metadata' => [
                'title'=>'Health Canada',
                'description'=>'Health Canada',
                'author'=>'Health Canada, Government of Canada',
                'issued'=>date("Y-m-d"),
            ],

            // this can be overwriten in local configuration if needed
            'contentSecurityPolicy' => "default-src 'self' ".implode(' ', [
                "*.canada.ca",
                "healthycanadians.gc.ca",
                "canadiaensensante.gc.ca",
                "code.jquery.com",
                "ajax.googleapis.com",
                "www.google-analytics.com",
                "ssl.google-analytics.com",
                "www.googletagmanager.com",
                "*.googleapis.com",
                "fonts.gstatic.com",
                "use.fontawesome.com",
                "'unsafe-eval'",
                "'unsafe-inline'",
            ]),
        ],
    ],
    'translator' => [
        'locale' => 'en_CA',
        'translation_file_patterns' => [
            [
                'type'     => 'gettext',
                'base_dir' => __DIR__.'/../../language',
                'pattern'  => 'layout-%s.mo',
            ],
        ],
    ],
];
