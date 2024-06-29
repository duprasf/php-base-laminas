<?php

namespace Application\Factory\View\Helper;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Application\View\Helper\GetLangSwitchUrl;

class GetLangSwitchUrlFactory implements FactoryInterface
{
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
