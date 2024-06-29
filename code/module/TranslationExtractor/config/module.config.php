<?php

namespace TranslationExtractor;

return [
    'laminas-cli' => [
        'commands' => [
            'translation:extract' => Command\Extract::class,
        ],
    ],
];
