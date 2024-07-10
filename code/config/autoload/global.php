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
        'services' => [
            'supportedLang' => ['en' => 'English', 'fr' => 'French'],

            // The default metadata if the page did not provide some/all of it
            'default-metadata' => [
                'title' => 'Health Canada',
                'description' => 'Health Canada',
                'author' => 'Government of Canada, Health Canada',
                'issued' => date("Y-m-d"),
                'subject' => ['en' => 'GV Government and Politics','fr' => 'GV Gouvernement et vie politique'],
            ],
            'JWT_SECRET' => getenv('JWT_SECRET')
                ? getenv('JWT_SECRET')
                : 'Secret key for the JWT. The longer the more secure. Nulla semper eros eget dolor commodo, ut ultrices metus auctor. Mauris arcu odio, suscipit non eros non, egestas porttitor erat. Integer id lectus tristique, dapibus metus luctus, lacinia magna. Nunc urna turpis, tempor ac commodo sit amet, mattis ut elit. Curabitur ut lacus nec orci luctus rhoncus. Cras venenatis fringilla suscipit. Etiam id feugiat risus. Nullam molestie dictum pulvinar. Quisque vehicula varius nisi. Proin placerat augue mi, et molestie magna cursus ut. Integer ullamcorper risus sed erat gravida sollicitudin et sed risus. Suspendisse potenti. Integer lorem neque, vehicula vel dui vitae, fringilla ullamcorper augue. Sed ut diam ipsum. Mauris a massa lobortis, dapibus est quis, tempus massa.'
            ,
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
    'session_config' => [
        'name' => 'laminas_basic',
        'cookie_secure' => true,
        'cookie_lifetime' => getenv('PHP_SESSION_TIME') ?? ini_get('session.gc_maxlifetime') ?? 86400,
        'gc_maxlifetime' => (getenv('PHP_SESSION_TIME') ?? ini_get('session.gc_maxlifetime') ?? 86400) + 3600,
        'remember_me_seconds' => getenv('PHP_SESSION_TIME') ?? ini_get('session.gc_maxlifetime') ?? 86400,
    ],
    'session_storage' => [
        'type' => Session\Storage\SessionArrayStorage::class,
    ],
];
