<?php
namespace Application\Factory;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Interop\Container\ContainerInterface;

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
