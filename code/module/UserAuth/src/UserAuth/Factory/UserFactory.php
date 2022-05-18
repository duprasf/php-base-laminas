<?php

declare(strict_types=1);

namespace UserAuth\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use UserAuth\Model\User;
use UserAuth\Model\UserAudit;

class UserFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new $requestName();
        $config = $container->get('config');

        if(isset($config['UserAuth']) && isset($config['UserAuth']['password-rules'])) {
            $obj->setPasswordRules($config['UserAuth']['password-rules']);
        }

        $obj->setParentDb($container->get('user-pdo'));

        $obj->setTranslator($container->get('MvcTranslator'));

        $router = $container->get('router');
        $obj->setUrlPlugin($router);

        $obj->setEventManager($container->get('EventManager'));

        $obj->setGcNotifyData([
            'api-key'=>$container->get('gc-notify-auth-system-key'),
            'confirm-email-template'=>$container->get('gc-notify-auth-confirm-email'),
            'reset-password-template'=>$container->get('gc-notify-auth-reset-password'),
        ]);
        if(get_class($obj) === 'UserAuth\Model\User') {
            $obj->loadFromSession();
        }
        return $obj;
    }
}
