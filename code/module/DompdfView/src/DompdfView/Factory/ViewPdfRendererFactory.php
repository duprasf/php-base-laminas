<?php
namespace DompdfView\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\View\Renderer\PhpRenderer;
use Dompdf\Dompdf;

class ViewPdfRendererFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $viewManager = $container->get('ViewManager');

        $pdfRenderer = new $requestedName();
        //$pdfRenderer->setResolver($viewManager->getResolver());
        $renderer = $container->get(PhpRenderer::class);
        $pdfRenderer->setHtmlRenderer($renderer);
        $pdfRenderer->setEngine(new Dompdf());

        return $pdfRenderer;
    }
}
