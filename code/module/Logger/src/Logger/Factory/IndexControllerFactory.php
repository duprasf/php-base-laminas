<?php

declare(strict_types=1);

namespace Adhoc\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Adhoc\Controller\IndexController;


class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new IndexController();
        $config = $container->get('config');
        $config = $config['gc-notify-config']['Adhoc'];
        $nofity = $container->get('GcNotify');

        if(isset($config['appName'])) {
            $nofity->setAppName($config['appName']);
        }

        if(isset($config['apikey'])) {
            $nofity->setApiKey($config['apikey']);
        }

        if(isset($config['templates'])) {
            $nofity->setTemplates($config['templates']);
        }

        $obj->setGcNotify($nofity);
        return $obj;
    }
}
