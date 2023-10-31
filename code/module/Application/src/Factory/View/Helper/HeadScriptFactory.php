<?php
namespace Application\Factory\View\Helper;

use Laminas\ServiceManager\FactoryInterface;
use Interop\Container\ContainerInterface;
use Laminas\Console\Request as ConsoleRequest;
use Laminas\Mvc\Router\RouteMatch;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Application\View\Helper\HeadScript;

class HeadScriptFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return $this->__invoke($container, HeadScript::class, []);
    }

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $obj = new $requestedName();
        $config = $container->get('Config');
        $obj->setSearchFolders(isset($config['public_assets']) ? $config['public_assets'] : array());

        return $obj;
    }
}
