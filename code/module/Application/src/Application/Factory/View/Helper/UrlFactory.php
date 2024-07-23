<?php

namespace Application\Factory\View\Helper;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Laminas\Console\Request as ConsoleRequest;
use Laminas\Router\Http\RouteMatch;
use Laminas\Router\RouteStackInterface;
use Application\View\Helper\Url;


class UrlFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $helper = new $requestedName();
        $helper->setLang($container->get('lang'));

        $match = $container
            ->get('application')
            ->getMvcEvent()
            ->getRouteMatch()
        ;
        if ($match instanceof RouteMatch) {
            $helper->setRouteMatch($match);
        }

        $router = $container
            ->get('application')
            ->getMvcEvent()
            ->getRouter()
        ;
        if($router instanceof RouteStackInterface) {
            $helper->setRouter($router);
        }
        return $helper;
    }
}
