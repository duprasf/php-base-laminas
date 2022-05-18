<?php
namespace Application\View\Helper;

use \Laminas\ServiceManager\FactoryInterface;
use \Application\View\Helper\Url;
use \Laminas\Console\Console;
use \Laminas\Mvc\Router\RouteMatch;
use \Laminas\ServiceManager\ServiceLocatorInterface;
use \Interop\Container\ContainerInterface;

class UrlFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return $this->createService($container, $requestedName, $options);
    }

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        $helper = new Url();

        //$router = Console::isConsole() ? 'HttpRouter' : 'Router';
        $request = $serviceLocator->get('Request');
        $router = $request instanceof ConsoleRequest ? 'Router' : 'HttpRouter';
        $helper->setRouter($serviceLocator->get($router));

        $match = $serviceLocator
            ->get('application')
            ->getMvcEvent()
            ->getRouteMatch()
        ;

        if ($match instanceof RouteMatch) {
            $helper->setRouteMatch($match);
        }

        return $helper;
    }
}
