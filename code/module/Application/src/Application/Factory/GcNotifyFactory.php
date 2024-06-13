<?php
namespace Application\Factory;

use Exception;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use GcNotify\GcNotify;

class GcNotifyFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        try {
            if(version_compare(PHP_VERSION, '7.1.0') <= 0) {
                throw new Exception('PHP < 7.1 is no longer supported');
            }
            $obj = new GcNotify();
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
            print 'PHP < 7.1 is no longer supported (or GcNotify class is missing)';
            exit();
        }
        return $obj;
    }
}
