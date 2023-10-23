<?php
declare(strict_types=1);

namespace PublicAsset\Factory\Controller;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use EmployeeDirectory\Model\SignatureTool;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new $requestName();

        return $obj;
    }
}
