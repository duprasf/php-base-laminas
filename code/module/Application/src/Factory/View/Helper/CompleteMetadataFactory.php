<?php
namespace Application\Factory\View\Helper;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Interop\Container\ContainerInterface;
use Application\View\Helper\CompleteMetadata;

class CompleteMetadataFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $object = new CompleteMetadata();
        $object->setMetadataBuilder($serviceLocator->get('metadataBuilder'));
        return $object;
    }

    public function __invoke(ContainerInterface $sm, $requestedName, ?array $options = null)
    {
        $object = new CompleteMetadata();
        $object->setMetadataBuilder($sm->get('metadataBuilder'));

        $cdtsVersion = $sm->has('cdts-version') ? $sm->get('cdts-version') : '4_0_32';
        $integrity = $sm->has('cdts-integrity') ? $sm->get('cdts-integrity') :
            ['4_0_32'=>[
                '/cdts/compiled/soyutils.js'=>'sha384-32eoaED5PWLqUcm/SmCNYkjyLGbZouGKcA7SqNkg4pw/HO5GQvYe41sFH2Gurff2',
                '/cdts/compiled/wet-en.js'=>'sha384-suyV59gigqpkF4lJASRU4NSaIhak0d9IdqZzEczs61ndeeFCqzLo2XFdvn6Hi+OF',
                '/cdts/compiled/wet-fr.js'=>'sha384-lKC/8wV1+9GDCPDDRxMv5fahcReyrn06T7dNJvQGS+Sr/UmQMspvV9mOji5qjWRT',
                '/css/theme.min.css'=>"sha384-OC8RXMtN4ILge7jffk24K2S+crP681ghM6SMHOeW8MAZ8PT4fLPc+5cBA9JIqnqB",
                '/cdts/cdtsfixes.css'=>"sha384-No+ATAwkMIc/2e9/908hPv/n6h84qeIT0ujDSDbsLXo3NdWjjOobQjOvQ6PDhuR6",
                '/css/ie8-theme.min.css'=>"sha384-clzigVbwqYHNkIrKxnU7kvGIA34SJUC0r1A3Q8cUkx3QeoSmxX/SL+9dmwqf+uCD",
                '/css/noscript.min.css'=>"sha384-YPGPGgtKCjAbqUw5iFn7pxdtJs4JKg1JM35Wk+/75p+CXi53r8prqn8SACFbXxXG",
            ]]
        ;
        $integrity = $integrity[$cdtsVersion] ?? [];

        $object->setCDTS([
            'version'=>$cdtsVersion,
            'integrity'=>$integrity,
            'path'=>sprintf($sm->has('cdts-path') ? $sm->get('cdts-path') : 'https://www.canada.ca/etc/designs/canada/cdts/gcweb/v%s', $cdtsVersion),
            'env'=>$sm->has('cdts-env') ? $sm->get('cdts-env') : 'dev',
        ]);
        return $object;
    }
}
