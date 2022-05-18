<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class IndexControllerFactory extends FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $obj = new \Application\Controller\IndexController();
        $obj->setTranslator($container->get(Translator::class));

        return $obj;
    }
}
