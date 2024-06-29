<?php

namespace Application\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

var_dump('This class should not be used anymore');
exit(basename(__FILE__).':'.__LINE__.PHP_EOL);

class GetLangSwitchUrlFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $object = new \Application\View\Helper\GetLangSwitchUrl();
        $route = $servicelocator->get('Application')->getMvcEvent()->getRouteMatch();
        $object->setRouteMatch($route);
        return $object;
    }

    public function __invoke(ContainerInterface $servicelocator, $requestedName, ?array $options = null)
    {
        $object = new \Application\View\Helper\GetLangSwitchUrl();
        $route = $servicelocator->get('Application')->getMvcEvent()->getRouteMatch();
        $object->setRouteMatch($route);
        return $object;
    }
}
