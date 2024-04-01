<?php
namespace Application\Factory\View\Helper;

use Exception;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Application\View\Helper\CompleteMetadata;

class CompleteMetadataFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $sm, $requestedName, ?array $options = null)
    {
        $object = new CompleteMetadata();
        $object->setMetadataBuilder($sm->get('metadataBuilder'));

        if(!$sm->has('cdts-integrity')) {
            throw new Exception('You must specify a cdts integrity array');
        }
        $cdtsVersion = $sm->has('cdts-version') ? $sm->get('cdts-version') : '5_0_0';
        $integrity = $sm->get('cdts-integrity');
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
