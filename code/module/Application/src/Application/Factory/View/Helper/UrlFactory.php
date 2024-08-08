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
        $helper = new Url();
        $helper->setLang($container->get('lang'));

        $router = $container->get('Router');
        if($router instanceof RouteStackInterface) {
            $helper->setRouter($router);
        }

        $match = $router->match($container->get('Request'));
        if ($match instanceof RouteMatch) {
            $helper->setRouteMatch($match);
        }

        return $helper;
    }
}
