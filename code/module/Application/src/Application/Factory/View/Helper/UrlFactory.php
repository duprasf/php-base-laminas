<?php

namespace Application\Factory\View\Helper;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Laminas\Console\Request as ConsoleRequest;
use Laminas\Mvc\Router\RouteMatch;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Application\View\Helper\Url;

class UrlFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->get('Config');
        $helper = new Url();

        $request = $container->get('Request');
        $router = $request instanceof ConsoleRequest ? 'Router' : 'HttpRouter';
        $helper->setRouter($container->get($router));

        $match = $container
            ->get('application')
            ->getMvcEvent()
            ->getRouteMatch()
        ;

        if ($match instanceof RouteMatch) {
            $helper->setRouteMatch($match);
        }

        $helper->setLang($container->get('lang'));

        return $helper;
    }
}
