<?php
/**
 * Application Plugin Factory
 * To create Application Plugin by injecting config array
 */

namespace Application\Controller\Plugin\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Laminas\Mvc\I18n\Translator;

class GetTranslatorFactory implements FactoryInterface
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
        return new \Application\Controller\Plugin\GetTranslator($container->get('MvcTranslator'), $container->get('lang'));
    }
}