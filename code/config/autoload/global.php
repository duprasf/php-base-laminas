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
use Laminas\Session\Storage\SessionArrayStorage;

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

            'useAdobeAnalytics' => getenv('ANALYTICS_USE_ADOBE'),
            'useAdobeAnalyticsWithPersonalInformation' => getenv('ANALYTICS_USE_ADOBE_WITH_PERSONAL_INFORMATION'),
            'googleAnalyticsId' => getenv('ANALYTICS_GA_ID'),

            'cdts-version' => '5_0_1',
            'cdts-path' => 'https://www.canada.ca/etc/designs/canada/cdts/gcweb/v%s',
            'cdts-env' => getenv('PHP_DEV_ENV')?'dev':'prod',
            'cdts-integrity' => [
                '4_0_24' => [
                    '/cdts/compiled/soyutils.js' => 'sha384-32eoaED5PWLqUcm/SmCNYkjyLGbZouGKcA7SqNkg4pw/HO5GQvYe41sFH2Gurff2',
                    '/cdts/compiled/wet-en.js' => 'sha384-IMoKvAYrcEY2IwB/8zd0Yoqd0+lsd6A/k4BOnzo+hyckATtO0m/oHBsws+mtmaIy',
                    '/cdts/compiled/wet-fr.js' => 'sha384-OdCyNY7s3SeuXNta8GdNlMwO2aJ50w3q6ZwJtgyKTe41XmPEZ4atEGwuOykuhrzq',
                    '/css/theme.min.css' => "sha384-+mCV6ccg6gYk9kur8cxYK2voYveX1cdinW3kXSMm8S9edkuahcMwnl0MqIYP8/+V",
                    '/cdts/cdtsfixes.css' => "sha384-ERok7VQ8/970rwlBwd5LpgNtnqqEgDeojLMTvWUwZNFiisyjq8Ple8yZQjlwDuVH",
                    '/css/ie8-theme.min.css' => "sha384-tCH5FgZmxiqF+OAyOtDg+AJ7saXl1vu52+5P7trL/SfzAlGq9RN8SFFxqD0aT3vL",
                    '/css/noscript.min.css' => "sha384-iDjY9PiVbAREQmK01rNW2QQF47k/sbwfsr2DmPtJHTfbRGnuwfAiC67Neo83/Axd",
                ],
                '4_0_29' => [
                    '/cdts/compiled/soyutils.js' => 'sha384-32eoaED5PWLqUcm/SmCNYkjyLGbZouGKcA7SqNkg4pw/HO5GQvYe41sFH2Gurff2',
                    '/cdts/compiled/wet-en.js' => 'sha384-+/1B6Avvu4oIFikIdhYs0BKMT6G+RS1a5LsK2pvE4ZohFPx4NVS+EEKLkDF/8/fY',
                    '/cdts/compiled/wet-fr.js' => 'sha384-Wmv9TKyfR5J1mWb2gOuO/U+kU2IWhAhkpucFIaS7/e2NfQzDmoHMHe48LEGZ4LPC',
                    '/css/theme.min.css' => "sha384-8Zv9QtHw8eXoH0Y9X66chv+tbM2IAH5tx+DKEDs4i8BgEwnlI+NNmhcHj/V5Ap7W",
                    '/cdts/cdtsfixes.css' => "sha384-A6QvgojS/JaguRzXq2GiQ+o9LX2aR2Y4dYr20QK9d4YyTSepLebHiqEi08bDfAAo",
                    '/css/ie8-theme.min.css' => "sha384-UDOe2xIxALrh1/DwNugbG+b3+BcD+YkoJ3E6SpTsqz0FlJksoMN9id98N3TsleiA",
                    '/css/noscript.min.css' => "sha384-2N3SXVqbZ0Zeentk/Q58aSj+r7z38PXIqduAKjc4fzmjSf0d+o+X7L006nvCjU53",
                ],
                '4_0_32' => [
                    '/cdts/compiled/soyutils.js' => 'sha384-32eoaED5PWLqUcm/SmCNYkjyLGbZouGKcA7SqNkg4pw/HO5GQvYe41sFH2Gurff2',
                    '/cdts/compiled/wet-en.js' => 'sha384-suyV59gigqpkF4lJASRU4NSaIhak0d9IdqZzEczs61ndeeFCqzLo2XFdvn6Hi+OF',
                    '/cdts/compiled/wet-fr.js' => 'sha384-lKC/8wV1+9GDCPDDRxMv5fahcReyrn06T7dNJvQGS+Sr/UmQMspvV9mOji5qjWRT',
                    '/css/theme.min.css' => "sha384-OC8RXMtN4ILge7jffk24K2S+crP681ghM6SMHOeW8MAZ8PT4fLPc+5cBA9JIqnqB",
                    '/cdts/cdtsfixes.css' => "sha384-No+ATAwkMIc/2e9/908hPv/n6h84qeIT0ujDSDbsLXo3NdWjjOobQjOvQ6PDhuR6",
                    '/css/ie8-theme.min.css' => "sha384-clzigVbwqYHNkIrKxnU7kvGIA34SJUC0r1A3Q8cUkx3QeoSmxX/SL+9dmwqf+uCD",
                    '/css/noscript.min.css' => "sha384-YPGPGgtKCjAbqUw5iFn7pxdtJs4JKg1JM35Wk+/75p+CXi53r8prqn8SACFbXxXG",
                ],
                '4_0_45' => [
                    '/cdts/compiled/soyutils.js' => 'sha384-hfwnpowMIP7hDqCMoNULlqSq7k2nu8R7zl+zHfYpNc5iokyd+Gbk5NO5ZdJFCv0o',
                    '/cdts/compiled/wet-en.js' => 'sha384-LzGsBKw0oOMVMjvc1Mfm6KZ2pgLkVbgHOjEhZ60V/XGIX+Ke3esX8z/gAFc1m33F',
                    '/cdts/compiled/wet-fr.js' => 'sha384-RGNThZb8OwCKqSV92fTnqhF6IyALe9w8NaKC3U1nrFVkTbDQHoxgsFcOurjprxPf',
                    '/cdts/cdtsfixes.css' => "sha384-No+ATAwkMIc/2e9/908hPv/n6h84qeIT0ujDSDbsLXo3NdWjjOobQjOvQ6PDhuR6",
                    //'/css/theme.min.css'=>"sha384-OC8RXMtN4ILge7jffk24K2S+crP681ghM6SMHOeW8MAZ8PT4fLPc+5cBA9JIqnqB",
                    //'/css/ie8-theme.min.css'=>"sha384-clzigVbwqYHNkIrKxnU7kvGIA34SJUC0r1A3Q8cUkx3QeoSmxX/SL+9dmwqf+uCD",
                    //'/css/noscript.min.css'=>"sha384-YPGPGgtKCjAbqUw5iFn7pxdtJs4JKg1JM35Wk+/75p+CXi53r8prqn8SACFbXxXG",
                ],
                '4_0_47' => [
                    '/cdts/compiled/soyutils.js' => 'sha384-hfwnpowMIP7hDqCMoNULlqSq7k2nu8R7zl+zHfYpNc5iokyd+Gbk5NO5ZdJFCv0o',
                    '/cdts/compiled/wet-en.js' => 'sha384-RFLX8+SNneb1EFE/QS54hYWboakex4rPUwQUyNw3Io0sx1x9MAxq1OHWXUxMWWAj',
                    '/cdts/compiled/wet-fr.js' => 'sha384-4GQiTOfMlOg47eTT6irR52Shvh1vntK6FPCRpga+5E3SCgSWGiF4aFxyh1VMAnE3',
                    '/cdts/cdtsfixes.css' => "sha384-35bOpDzwDZ9ACuQn0HDzR4xmd2A9kSse71KDxuOQGAApvhD2NI+yZW5fRVl/VI+C",
                ],
                '5_0_0' => [
                    '/cdts/compiled/soyutils.js' => 'sha384-hfwnpowMIP7hDqCMoNULlqSq7k2nu8R7zl+zHfYpNc5iokyd+Gbk5NO5ZdJFCv0o',
                    '/cdts/compiled/wet-en.js' => 'sha384-ulFMH1PWenti4HPUhevZkviTg3VIc2X9R19+d2OtnyyBWWiJ5ogSW+G1qjwSS2y7',
                    '/cdts/compiled/wet-fr.js' => 'sha384-6tz+67Lsc1eo99Errrs8Cwu+OiOjIZ00Gb17iAP4O+ZhYZ5u8awc+SEb+h/xzhlc',
                    '/cdts/cdtsfixes.css' => "sha384-zSpYa4FHx3BrgIDTrj3QGfclWZJ6b3KtRRzwPmcZBEnd1Bl9U5TCUP0DqT/RJYGW",
                    '/cdts/cdtsapps.css' => "sha384-6fF78tukeGgTIwO3KIWClcj4QTOZUlpI3OGFYb9wKYf6XrWUSgxSdlbUepkvQql1",
                ],
                '5_0_1' => [
                    '/cdts/compiled/soyutils.js' => 'sha384-hfwnpowMIP7hDqCMoNULlqSq7k2nu8R7zl+zHfYpNc5iokyd+Gbk5NO5ZdJFCv0o',
                    '/cdts/compiled/wet-en.js' => 'sha384-RGMFGZMCJKgVZPBWoH+xj/UlWA8tKt5hnOqKri2Y/NpPeVYzg4+eI1K1NVfkR7VR',
                    '/cdts/compiled/wet-fr.js' => 'sha384-uUG0C9gTNvYwZ1GW/xEfNqqs/ZFGBcR6y+yyKSXEWuB3nqCpDelitWilNMGXMwT3',
                    '/cdts/cdtsfixes.css' => "sha384-zSpYa4FHx3BrgIDTrj3QGfclWZJ6b3KtRRzwPmcZBEnd1Bl9U5TCUP0DqT/RJYGW",
                    '/cdts/cdtsapps.css' => "sha384-6fF78tukeGgTIwO3KIWClcj4QTOZUlpI3OGFYb9wKYf6XrWUSgxSdlbUepkvQql1",
                ],
            ],
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
        'remember_me_seconds' => getenv('PHP_SESSION_TIME') ?: (ini_get('session.gc_maxlifetime') ?: 86400),
        /*
        'name' => 'laminas_basic',
        'cookie_secure' => true,
        'cookie_lifetime' => getenv('PHP_SESSION_TIME') ?: (ini_get('session.gc_maxlifetime') ?: 86400),
        'gc_maxlifetime' => (getenv('PHP_SESSION_TIME') ?: (ini_get('session.gc_maxlifetime') ?: 86400)) + 3600,
        /**/
    ],
    'session_storage' => [
        'type' => SessionArrayStorage::class,
    ],
];
