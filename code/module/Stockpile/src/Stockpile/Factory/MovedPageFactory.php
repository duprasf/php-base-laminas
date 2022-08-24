<?php

declare(strict_types=1);

namespace Stockpile\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

use Stockpile\Model\MovedPage;

class MovedPageFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        // The factory created the object to be returned and will set
        // all the configuration required
        $obj = new MovedPage();
        $obj->setEventManager($container->get('EventManager'));

        if($container->has('stockpilePdoMovedPages')) {
            $obj->setPdo($container->get('stockpilePdoMovedPages'));
        }
        // return the object
        return $obj;
    }
}
