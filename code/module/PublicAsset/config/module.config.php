<?php
namespace PublicAsset;

return array(
    'router' => array(
        'routes' => array(
            'public-asset-route'=>array(
                'type'=>Route\PublicAssetRoute::class,
                'options'=>array(
                    'regex'=>'(?P<path>/.+)$',
                    'spec'=>'/%lang%/%path%',
                    'defaults'=>array(
                        'controller'=>Controller\IndexController::class,
                        'action'=>'index',
                    ),
                    'constraints'=>array(
                        'path'=>'[\w\d/-]*$',
                    ),
                ),
                'may_terminate' => true,
            ),
        ),
    ),
    'route_manager' => array(
        'factories'=>array(
            Route\PublicAssetRoute::class=>Factory\PublicAssetRouteFactory::class,
        ),
    ),
    'controllers' => array(
        'factories' => array(
            Controller\IndexController::class => Factory\Controller\IndexControllerFactory::class,
        ),
    ),
);
