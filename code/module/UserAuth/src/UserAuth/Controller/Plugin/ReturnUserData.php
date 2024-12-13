<?php

namespace UserAuth\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\View\Model\JsonModel;
use UserAuth\Model\User\UserInterface;
use UserAuth\Model\UserInterface as OldInterface;

class ReturnUserData extends AbstractPlugin
{
    public function __invoke(UserInterface|OldInterface $user, $remember = null, array $additionalDataToReturn = [], int $length = 129600)
    {
        $return = [];
        if($user->isLoggedIn()) {
            // 86400 = 24 hours, 129600 = 36 hours, 2419200 = 28 days
            $return = array_merge([
                'remember' => $remember,
                'jwt' => $user->getJWT($length),
            ], $additionalDataToReturn);
        }
        return new JsonModel($return);
    }
}
