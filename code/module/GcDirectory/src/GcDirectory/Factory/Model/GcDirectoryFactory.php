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
        if(!$sm->has('gc-directory-config') || !(getenv("GCDIRECTORY_SECRET_TOKEN") && getenv('GCDIRECTORY_API_URL'))) {
            throw new Exception('No config found for GcDirectory');
        }
        $config = $sm->has('gc-directory-config')
            ? $sm->get('gc-directory-config')
            : [
                "secret-token" => getenv("GCDIRECTORY_SECRET_TOKEN"),
                "base-url" => getenv('GCDIRECTORY_API_URL'),
                "username" => getenv('GCDIRECTORY_USER'),
                "password" => getenv('GCDIRECTORY_PASSWORD'),
                "adminKey" => getenv('GCDIRECTORY_ADMIN_KEY'),
                "deptId" => getenv('GCDIRECTORY_DEPT_ID'),
            ]
        ;
        $obj->setConfig($config);

        return $obj;
    }
}
