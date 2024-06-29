<?php

namespace Application\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class LangFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $route = $container->get('router')->match($container->get('Request'));
        $lang  = $route
            ? (
                $route->getParam('locale', 'en')
                ?: $route->getParam('lang', 'en')
            )
            : 'en'
        ;
        return $lang;
    }
}
