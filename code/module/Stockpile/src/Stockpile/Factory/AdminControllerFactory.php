<?php
declare(strict_types=1);

namespace Stockpile\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use UserAuth\Model\Auth;
use Stockpile\Controller\AdminController;
use Stockpile\Model\MovedPage;

class AdminControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new AdminController();
        $config = $container->get('config');

        $movedPageObj = $container->get(MovedPage::class);
        $obj->setMovedPageObj($movedPageObj);

        $authObj = $container->get(Auth::class);
        $obj->setAuthObj($authObj);

        return $obj;
    }
}
