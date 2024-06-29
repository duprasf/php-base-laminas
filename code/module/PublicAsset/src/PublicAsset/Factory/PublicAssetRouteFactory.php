<?php

namespace PublicAsset\Factory;

use PublicAsset\Route\PublicAssetRoute;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Interop\Container\ContainerInterface;

class PublicAssetRouteFactory implements FactoryInterface
{
    protected $creationOptions;
    public function setCreationOptions(array $creationOptions)
    {
        $this->creationOptions = $creationOptions;
    }

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $this->setCreationOptions($options);
        return $this->createService($container, $requestedName, $options);
    }

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');

        $route = new PublicAssetRoute($this->creationOptions['regex'], $this->creationOptions['spec'], $this->creationOptions['defaults']);
        $route->setSearchFolders(isset($config['public_assets']) ? $config['public_assets'] : array());
        return $route;
    }
}
