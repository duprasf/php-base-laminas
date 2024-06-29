<?php
/**
 * Application Plugin Factory
 * To create Application Plugin by injecting config array
 */

namespace Application\Factory\Controller\Plugin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Mvc\I18n\Translator;
use Application\Model\Metadata;

class CommonMetadataFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $obj = new $requestedName();
        $obj->setLang($container->get('lang'));
        $obj->setTranslator($container->get('MvcTranslator'));
        $obj->setMetadataObj($container->get(Metadata::class));
        $obj->setUrlObj($container->get('ViewHelperManager')->get('url'));
        $obj->setBreadcrumbsObj($container->get('breadcrumbs'));
        $obj->setRouteMatch(
            $container
                ->get('Application')
                ->getMvcEvent()
                ->getRouteMatch()
        );
        //$obj->setAppMetadata($container->get('commonMetadata'));

        return $obj;
    }
}
