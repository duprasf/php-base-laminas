<?php
/**
 * Application Plugin Factory
 * To create Application Plugin by injecting config array
 */

namespace Application\Controller\Plugin\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Laminas\Mvc\I18n\Translator;

class LangFactory implements FactoryInterface
{
    /**
    * Get the translator
    *
    * @param ContainerInterface $container
    * @param mixed $requestedName
    * @param array $options
    *
    * @return \Laminas\Mvc\I18n\Translator
    */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new $requestedName($container->get('lang'));
    }
}