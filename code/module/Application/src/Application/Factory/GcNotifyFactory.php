<?php
namespace Application\Factory;

use Exception;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use GcNotify\GcNotify;
use GcNotify\GcNotify_PHP5;

class GcNotifyFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        try {
            if(version_compare(PHP_VERSION, '7.1.0') >= 0) {
                // loading the GcNotify class for PHP >= 7.1
                $obj = new GcNotify();
            } else {
                // loading the GcNotify class for PHP < 7.1
                $obj = new GcNotify_PHP5();
            }
            if($container->has('gc-notify-error-generic-template')) {
                $obj->setGenericErrorTemplate($container->get('gc-notify-error-generic-template'));
            }
            if($container->has('gc-notify-error-generic-email')) {
                $obj->setGenericErrorEmail($container->get('gc-notify-error-generic-email'));
            }
            if($container->has('gc-notify-error-reporting-key')) {
                $obj->setErrorReportingKey($container->get('gc-notify-error-reporting-key'));
            }
        } catch(Exception $e) {
            print 'Missing GC Notify class';
            exit();
        }
        return $obj;
    }
}
