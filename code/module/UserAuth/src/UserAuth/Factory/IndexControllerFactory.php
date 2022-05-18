<?php

declare(strict_types=1);

namespace UserAuth\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use UserAuth\Controller\IndexController;
use UserAuth\Model\User;


class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new IndexController();
        $config = $container->get('config');

        if(isset($config['UserAuth']) && isset($config['UserAuth']['password-rules'])) {
            $obj->setPasswordRules($config['UserAuth']['password-rules']);
        }

        $gcNotify = $config['gc-notify-config']['UserAuth'];
        $nofity = $container->get('GcNotify');

        if(isset($gcNotify['appName'])) {
            $nofity->setAppName($gcNotify['appName']);
        }

        if(isset($gcNotify['apikey'])) {
            $nofity->setApiKey($gcNotify['apikey']);
        }

        if(isset($gcNotify['templates'])) {
            $nofity->setTemplates($gcNotify['templates']);
        }

        $obj->setGcNotify($nofity);

        $obj->setUser($container->get(User::class));

        return $obj;
    }
}
