<?php
namespace DompdfView;

return [
    'view_manager' => [
        'strategies' => [
            'ViewPdfStrategy'
        ],
    ],
    'service_manager' => [
        'aliases' => [
            'ViewPdfRenderer' => Renderer\ViewPdfRenderer::class,
            'ViewPdfStrategy' => Strategy\ViewPdfStrategy::class,
        ],
        'factories' => [
            Renderer\ViewPdfRenderer::class => Factory\ViewPdfRendererFactory::class,
            Strategy\ViewPdfStrategy::class => Factory\ViewPdfStrategyFactory::class,
        ],
    ],
];
