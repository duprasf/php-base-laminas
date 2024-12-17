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

            if(getExistingEnv('GC_NOTIFY_TEMPATES')) {
                $templates = json_decode(getExistingEnv('GC_NOTIFY_TEMPATES'), true);
                if(json_last_error() == JSON_ERROR_NONE) {
                    $obj->setTemplates($templates);
                }
            }
            if($container->has('gc-notify-templates')) {
                $obj->setTemplates($container->get('gc-notify-templates'));
            }

            if(getExistingEnv('GC_NOTIFY_API_KEY')) {
                $obj->setApiKey(getExistingEnv('GC_NOTIFY_API_KEY'));
            }
            if($container->has('gc-notify-error-reporting-key')) {
                $obj->setApiKey($container->get('gc-notify-error-reporting-key'));
            }

            if(getExistingEnv('GC_NOTIFY_ERROR_REPORTING_API_KEY')) {
                $obj->setErrorReportingKey(getExistingEnv('GC_NOTIFY_ERROR_REPORTING_API_KEY'));
            }
            if($container->has('gc-notify-error-reporting-key')) {
                $obj->setErrorReportingKey($container->get('gc-notify-error-reporting-key'));
            }

            if(getExistingEnv('GC_NOTIFY_ERROR_REPORTING_APP_NAME')) {
                $obj->setAppName(getExistingEnv('GC_NOTIFY_ERROR_REPORTING_APP_NAME'));
            }
            if(getExistingEnv('GC_NOTIFY_APP_NAME')) {
                $obj->setAppName(getExistingEnv('GC_NOTIFY_APP_NAME'));
            }
            if($container->has('gc-notify-error-reporting-app-name')) {
                $obj->setAppName($container->get('gc-notify-error-reporting-app-name'));
            }

            if(getExistingEnv('GC_NOTIFY_OVERWRITE_ALL_EMAIL')) {
                $obj->setOverwriteEmail(getExistingEnv('GC_NOTIFY_OVERWRITE_ALL_EMAIL'));
            }
            if($container->has('gc-notify-overwrite_all_email')) {
                $obj->setOverwriteEmail($container->get('gc-notify-overwrite_all_email'));
            }

        } catch(Exception $e) {
            print 'PHP < 7.1 is no longer supported (or GcNotify class is missing)';
            exit();
        }
        return $obj;
    }
}
