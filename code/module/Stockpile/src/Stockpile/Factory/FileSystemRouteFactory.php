<?php

declare(strict_types=1);

namespace Stockpile\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

use Stockpile\Route\FileSystemRoute;

class FileSystemRouteFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        // The factory created the object to be returned and will set
        // all the configuration required
        $config = $container->get('config');
        $options = $config['router']['routes']['file-system-page']['options'];
        $obj = new FileSystemRoute($options['regex'], $options['spec'], $options['defaults']);
        $obj->setConfig($config);

        // return the object
        return $obj;
    }
}
