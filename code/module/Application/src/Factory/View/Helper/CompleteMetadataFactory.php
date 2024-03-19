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

        $cdtsVersion = $sm->has('cdts-version') ? $sm->get('cdts-version') : '5_0_0';
        $integrity = $sm->has('cdts-integrity') ? $sm->get('cdts-integrity') :
            ['5_0_0'=>[
                '/cdts/compiled/soyutils.js'=>'sha384-hfwnpowMIP7hDqCMoNULlqSq7k2nu8R7zl+zHfYpNc5iokyd+Gbk5NO5ZdJFCv0o',
                '/cdts/compiled/wet-en.js'=>'sha384-ulFMH1PWenti4HPUhevZkviTg3VIc2X9R19+d2OtnyyBWWiJ5ogSW+G1qjwSS2y7',
                '/cdts/compiled/wet-fr.js'=>'sha384-6tz+67Lsc1eo99Errrs8Cwu+OiOjIZ00Gb17iAP4O+ZhYZ5u8awc+SEb+h/xzhlc',
                '/cdts/cdtsfixes.css'=>"sha384-zSpYa4FHx3BrgIDTrj3QGfclWZJ6b3KtRRzwPmcZBEnd1Bl9U5TCUP0DqT/RJYGW",
                '/cdts/cdtsapps.css'=>"sha384-6fF78tukeGgTIwO3KIWClcj4QTOZUlpI3OGFYb9wKYf6XrWUSgxSdlbUepkvQql1",
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
