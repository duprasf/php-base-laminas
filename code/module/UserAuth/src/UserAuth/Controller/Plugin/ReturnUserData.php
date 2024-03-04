<?php
namespace UserAuth\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use UserAuth\Model\User;

class ReturnUserData extends AbstractPlugin
{
    public function __invoke(User $user, $remember=null)
    {
        // 2419200 = 28 days, 86400 = 24 hours, 129600 = 36 hours
        return new JsonModel([
            'remember'=>$remember,
            'jwt'=>$user->getJWT(129600),
//            'settings'=>$user->getSettings(),
        ]);
    }
}
