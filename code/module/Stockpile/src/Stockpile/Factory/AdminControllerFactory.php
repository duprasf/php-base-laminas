<?php
declare(strict_types=1);

namespace Stockpile\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Stockpile\Controller\AdminController;
use Stockpile\Model\MovedPage;
use Stockpile\Model\Auth;

class AdminControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new AdminController();
        $config = $container->get('config');

        // configure GC Notify for your app if configed in your autoloader
        // see ExampleModule/config/autoload/gc-notify.local.php
        if(method_exists($obj, 'setGcNotify')) {
            $nofity = $container->get('GcNotify');
            $namespace = explode('\\', __NAMESPACE__)[0];
            if(isset($config['gc-notify-config'][$namespace])) {
                $notifyConfig = $config['gc-notify-config'][$namespace];

                if(isset($notifyConfig['appName'])) {
                    $nofity->setAppName($notifyConfig['appName']);
                }

                if(isset($notifyConfig['apikey'])) {
                    $nofity->setApiKey($notifyConfig['apikey']);
                }

                if(isset($notifyConfig['templates'])) {
                    $nofity->setTemplates($notifyConfig['templates']);
                }
            }
            $obj->setGcNotify($nofity);
        }


        $movedPageObj = $container->get(MovedPage::class);
        $obj->setMovedPageObj($movedPageObj);

        $authObj = $container->get(Auth::class);
        $obj->setAuthObj($authObj);

        return $obj;
    }
}
