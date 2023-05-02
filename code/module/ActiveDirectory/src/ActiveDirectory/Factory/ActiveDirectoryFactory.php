<?php

declare(strict_types=1);

namespace ActiveDirectory\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use ActiveDirectory\Model\ActiveDirectory;
use Laminas\Ldap\Ldap;

class ActiveDirectoryFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        // The factory created the object to be returned
        $obj = new ActiveDirectory();
        $options = $container->get('ldap-options');
        //$obj->setLdapConfig($options);

        $ldaps = [];
        foreach($options as $op) {
            $ldaps[] = new Ldap($op);
        }
        $obj->setLdaps($ldaps);
        return $obj;
    }
}
