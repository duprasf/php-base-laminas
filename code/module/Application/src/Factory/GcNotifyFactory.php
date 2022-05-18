<?php
namespace Application\Factory;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Interop\Container\ContainerInterface;

class GcNotifyFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $servicelocator, $requestedName, ?array $options = null)
    {
        return $this->createService($servicelocator);
    }

    /**
    * Create service
    *
    * @param ServiceLocatorInterface $serviceLocator
    *
    * @return GcNotify
    */
    public function createService(ServiceLocatorInterface $sm)
    {
        try {
            if(version_compare(PHP_VERSION, '7.1.0') >= 0) {
                // loading the GcNotify class for PHP >= 7.1
                $obj = new \GcNotify\GcNotify();
            } else {
                // loading the GcNotify class for PHP < 7.1
                $obj = new \GcNotify\GcNotify_PHP5();
            }
            if($sm->has('gc-notify-error-generic-template')) {
                $obj->setGenericErrorTemplate($sm->get('gc-notify-error-generic-template'));
            }
            if($sm->has('gc-notify-error-generic-email')) {
                $obj->setGenericErrorEmail($sm->get('gc-notify-error-generic-email'));
            }
            if($sm->has('gc-notify-error-reporting-key')) {
                $obj->setErrorReportingKey($sm->get('gc-notify-error-reporting-key'));
            }
        } catch(\Exception $e) {
            print 'Missing GC Notify class';
            exit();
        }
        return $obj;
    }
}