<?php
namespace Application\Factory\View\Helper;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Interop\Container\ContainerInterface;
use Application\View\Helper\GetLangSwitchUrl;

class GetLangSwitchUrlFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $sm)
    {
        $object = new GetLangSwitchUrl();
        $route = $sm->get('Application')->getMvcEvent()->getRouteMatch();
        $object->setRouteMatch($route);
        return $object;
    }

    public function __invoke(ContainerInterface $sm, $requestedName, ?array $options = null)
    {
        $object = new GetLangSwitchUrl();
        $route = $sm->get('Application')->getMvcEvent()->getRouteMatch();
        if($route) {
            $object->setRouteMatch($route);
        }
        return $object;
    }
}
