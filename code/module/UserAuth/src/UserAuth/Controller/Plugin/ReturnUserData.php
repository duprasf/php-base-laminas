<?php

namespace UserAuth\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\View\Model\JsonModel;
use UserAuth\Model\User;

class ReturnUserData extends AbstractPlugin
{
    public function __invoke(User $user, $remember = null, array $additionalDataToReturn = [], int $ttl = 129600)
    {
        $return = [];
        if($user->isLoggedIn()) {
            // 86400 = 24 hours, 129600 = 36 hours, 2419200 = 28 days
            $return = array_merge([
                'remember' => $remember,
                'jwt' => $user->getJWT($ttl),
            ], $additionalDataToReturn);
        }
        return new JsonModel($return);
    }
}
