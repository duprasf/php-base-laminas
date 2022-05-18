<?php
namespace PublicAsset;

return array(
    'router' => array(
        'routes' => array(
            'public-asset-route'=>array(
                'type'=>'public-asset-route',
                'options'=>array(
                    'regex'=>'(?P<path>/.+)$',
                    'spec'=>'/%lang%/%path%',
                    'defaults'=>array(
                        'controller'=>'PublicAsset\Controller\Index',
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
            'public-asset-route'=>'PublicAsset\Factory\PublicAssetRouteFactory',
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'PublicAsset\Controller\Index' => 'PublicAsset\Controller\IndexController',
        ),
    ),
);
