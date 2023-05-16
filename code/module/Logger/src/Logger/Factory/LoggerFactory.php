<?php
declare(strict_types=1);

namespace Logger\Factory;

use PDO;
use UnexpectedValueException;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Logger\Model\DbLogger;
use Logger\Model\FileLogger;

/**
* Return a logger class depending on what config is set
*
* If pdoLogger is set and a class of PDO, the DbLogger is returned
* If fileLogger is set to a writtable path, FileLogger is returned
* Otherwise an exception is thrown
*
* @throws UnexpectedValueException
*/
class LoggerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $pdo = $container->get('pdoLogger');
        $path = $container->get('fileLogger');
        if($pdo instanceof PDO) {
            $obj = new DbLogger();
            $obj->setDb($pdo);
            //$obj->setTranslator($container->get('MvcTranslator'));
        } else if($path && is_writable($path)) {
            $obj = new FileLogger();
            $obj->setFilename($path);
        } else {
            throw new UnexpectedValueException('No defined logger available, please define pdoLogger or fileLogger');
        }

        return $obj;
    }
}
