<?php
/**
 * Application Plugin Factory
 * To create Application Plugin by injecting config array
 */

namespace Application\Factory\Controller\Plugin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Mvc\I18n\Translator;

class GetUrlHelperFactory implements FactoryInterface
{
    /**
    * Get the translator object
    *
    * @param ContainerInterface $container
    * @param mixed $requestedName
    * @param array $options
    *
    * @return \Laminas\Mvc\I18n\Translator
    */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new $requestedName($container->get('ViewHelperManager')->get('url'));
    }
}
