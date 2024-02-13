<?php
namespace DompdfView\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use DompdfView\Renderer\ViewPdfRenderer;

class ViewPdfStrategyFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $pdfRenderer = $container->get(ViewPdfRenderer::class);
        $pdfStrategy = new $requestedName($pdfRenderer);

        return $pdfStrategy;
    }
}
