<?php
namespace Application\Factory\View\Helper;

use Laminas\ServiceManager\FactoryInterface;
use Interop\Container\ContainerInterface;
use Laminas\Console\Request as ConsoleRequest;
use Laminas\Mvc\Router\RouteMatch;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Application\View\Helper\Url;

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

        $helper->setLang($serviceLocator->get('lang'));

        return $helper;
    }
}
