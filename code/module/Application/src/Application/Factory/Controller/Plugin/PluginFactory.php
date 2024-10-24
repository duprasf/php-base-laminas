<?php
/**
 * Application Plugin Factory
 * To create Application Plugin by injecting config array
 */

namespace Application\Factory\Controller\Plugin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use UserAuth\Model\User\User;
use GcNotify\GcNotify;

class PluginFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $obj = new $requestedName();

        $array = [
            'setUser'=>User::class,
            'setGcNotify'=>GcNotify::class,
            'setLang'=>'lang',
            'setTranslator'=>'MvcTranslator',
        ];

        foreach($array as $method=>$serviceName) {
            if(method_exists($obj, $method)) {
                call_user_func([$obj, $method], $container->get($serviceName));
            }
        }

        if(method_exists($obj, 'setUrlObj')) {
            call_user_func([$obj, 'setUrlObj'], $container->get('ViewHelperManager')->get('url'));
        }

        if(method_exists($obj, 'setRouteMatch')) {
            call_user_func(
                [$obj, 'setRouteMatch'],
                $container
                    ->get('Application')
                    ->getMvcEvent()
                    ->getRouteMatch()
            );
        }

        return $obj;
    }
}
