<?php

namespace GcDirectory\Factory\Model;

use Exception;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class GcDirectoryFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $sm, $requestedName, array|null $options = null)
    {
        $obj = new $requestedName();

        $obj->setLang($sm->get('lang'));
        if(!$sm->has('gc-directory-config') || !(getExistingEnv("GCDIRECTORY_SECRET_TOKEN") && getExistingEnv('GCDIRECTORY_API_URL'))) {
            throw new Exception('No config found for GcDirectory');
        }
        $config = $sm->has('gc-directory-config')
            ? $sm->get('gc-directory-config')
            : [
                "secret-token" => getExistingEnv("GCDIRECTORY_SECRET_TOKEN"),
                "base-url" => getExistingEnv('GCDIRECTORY_API_URL'),
                "username" => getExistingEnv('GCDIRECTORY_USER'),
                "password" => getExistingEnv('GCDIRECTORY_PASSWORD'),
                "adminKey" => getExistingEnv('GCDIRECTORY_ADMIN_KEY'),
                "deptId" => getExistingEnv('GCDIRECTORY_DEPT_ID'),
            ]
        ;
        $obj->setConfig($config);

        return $obj;
    }
}
