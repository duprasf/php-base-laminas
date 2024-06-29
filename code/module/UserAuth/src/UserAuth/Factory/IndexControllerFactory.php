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

        if($container->has('user-auth-registration-allowed')) {
            $obj->setConfig('registrationAllowed', $container->get('user-auth-registration-allowed'));
        }

        if($container->has('user-auth-password-rules')) {
            $obj->setConfig('passwordRules', $container->get('user-auth-password-rules'));
            $obj->setPasswordRules($container->get('user-auth-password-rules'));
        }

        return $obj;
    }
}
