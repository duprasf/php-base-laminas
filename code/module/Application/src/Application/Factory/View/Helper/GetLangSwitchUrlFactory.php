<?php

namespace Application\Factory\View\Helper;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Application\View\Helper\GetLangSwitchUrl;

class GetLangSwitchUrlFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $obj = new GetLangSwitchUrl();
        $route = $container->get('Application')->getMvcEvent()->getRouteMatch();
        if($route) {
            $obj->setRouteMatch($route);
        }
        $request = $container->get('request');
        $obj->setQueryString($request->getQuery()->getArrayCopy());

        return $obj;
    }
}
