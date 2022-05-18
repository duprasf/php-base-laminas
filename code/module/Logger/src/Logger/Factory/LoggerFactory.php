<?php

declare(strict_types=1);

namespace Logger\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Logger\Model\DbLogger;
use Logger\Model\FileLogger;

class LoggerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $pdo = $container->get('pdoLogger');
        $path = $container->get('fileLogger');
        if($pdo) {
            $obj = new DbLogger();
            $obj->setDb($pdo);
            //$obj->setTranslator($container->get('MvcTranslator'));
        } else if($path) {
            $obj = new FileLogger();
            $obj->setFilename($path);
        } else {
            throw new \UnexpectedValueException('No defined logger available, please define pdoLogger or fileLogger');
        }

        return $obj;
    }
}
