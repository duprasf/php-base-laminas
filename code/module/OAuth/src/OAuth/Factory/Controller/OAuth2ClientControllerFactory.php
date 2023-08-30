<?php
declare(strict_types=1);

namespace OAuth\Factory\Controller;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use OAuth\Model\OAuth2Client;

class OAuth2ClientControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new $requestName();
        if(!$container->has('OAuth2Enabled') || $container->get('OAuth2Enabled') == false) {
            $obj->setEnabled(false);
            return $obj;
        }

        //$config = $container->get('config');

        $obj->setEnabled(true);
        $obj->setOAuth2Client($container->get(OAuth2Client::class));

        if($container->has('OAuthCallbackController') && $container->has('OAuthCallbackAction')) {
            $obj->setDefaultController(
                $container->get('OAuthCallbackController'),
                $container->get('OAuthCallbackAction')
            );
        }

        return $obj;
    }
}
