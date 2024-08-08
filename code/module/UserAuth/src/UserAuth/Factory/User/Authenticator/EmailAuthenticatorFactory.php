<?php

declare(strict_types=1);

namespace UserAuth\Factory\User\Authenticator;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use GcNotify\GcNotify;
use UserAuth\Model\User\Authenticator\EmailAuthenticator;

class EmailAuthenticatorFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new EmailAuthenticator();

        $obj->setEmailer($container->get(GcNotify::class))
            ->setLang($container->get('lang'))
            ->setTranslator($container->get('MvcTranslator'))
            ->setUrlHelper($container->get('ViewHelperManager')->get('url'))
        ;
        return $obj;
    }
}
