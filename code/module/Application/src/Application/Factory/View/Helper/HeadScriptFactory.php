<?php
namespace Application\Factory\View\Helper;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class HeadScriptFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $obj = new $requestedName();
        $config = $container->get('Config');
        $obj->setSearchFolders(isset($config['public_assets']) ? $config['public_assets'] : array());

        return $obj;
    }
}
