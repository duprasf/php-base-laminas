<?php

declare(strict_types=1);

namespace UserAuth\Factory\User;

use InvalidArgumentException;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use UserAuth\Model\JWT;
use UserAuth\Model\User\User;
use UserAuth\Exception\UserException;

class UserFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new $requestName();
        if(!$obj instanceof User) {
            throw new InvalidArgumentException("Cannot create a user that is not extending UserAuth\Model\User\User");
        }

        if($container->has('user-password-rules') && method_exists($obj, 'setPasswordRules')) {
            $obj->setPasswordRules($container->get('user-password-rules'));
        }

        if(method_exists($obj, 'setTranslator')) {
            $obj->setTranslator($container->get('MvcTranslator'));
        }

        $obj->setEventManager($container->get('EventManager'));
        $obj->setJwtObj($container->get(JWT::class));

        //$request = $container->get('Request');
        $auth = $container->get('request')->getHeader('Authorization');;
        $jwt = $auth ? str_replace('Bearer ', '', $auth->getFieldValue()) : null;
        if(!$jwt) {
            $auth = $container->get('request')->getHeader('X-Access-Token');;
            $jwt = $auth ? $auth->getFieldValue() : null;
        }
        $obj->setJwtFromFactory($jwt);
        if($jwt) {
            try {
                // try to login with Authorization or X-Access-Token...
                // This did not work since it requires the ID Field to be
                // specified before it is called.
                //$obj->setJwtFromFactory($jwt);
            } catch (UserException $e) {
                // ... do nothing if not logged in
            }
        }


        return $obj;
    }
}
