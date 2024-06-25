<?php
declare(strict_types=1);

namespace Application\Factory\Command;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use EmployeeDirectory\Model\ImportTool;

class CronjobCommandFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        ob_start();
        $container2 = require dirname(dirname(dirname(dirname(dirname(dirname(__DIR__)))))) . '/config/container.php';
        // Run the application!
        /** @var Application $app */
        $app = $container2->get('Application');
        $app->run();
        ob_clean();

        $obj = new $requestName();
        $obj->setEventManager($container->get('EventManager'));

        if(method_exists($obj, 'setSharedEventManager')) {
            call_user_func([$obj, 'setSharedEventManager'], $container->get('EventManager')->getSharedManager());
        }

        return $obj;
    }
}
