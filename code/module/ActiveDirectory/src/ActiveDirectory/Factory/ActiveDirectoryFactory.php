<?php

declare(strict_types=1);

namespace ActiveDirectory\Factory;

use Exception;
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
        if(getExistingEnv('LAMINAS_LDAP_CONNECTIONS')) {
            $options = json_decode(getExistingEnv('LAMINAS_LDAP_CONNECTIONS'), true);
        }
        if(!$options && $container->has('ldap-options')) {
            $options = $container->get('ldap-options');
        }

        if(!$options) {
            throw new Exception('No configuration for LDAP found. Please specify environment variable LAMINAS_LDAP_CONNECTIONS or Laminas service ldap-options');
        }

        $ldaps = [];
        foreach($options as $op) {
            $ldaps[] = new Ldap($op);
        }
        $obj->setLdaps($ldaps);
        return $obj;
    }
}
