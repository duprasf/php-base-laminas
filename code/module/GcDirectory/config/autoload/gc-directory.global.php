<?php
namespace GcDirectory;

return array(
    'public_assets'=>array(
        __NAMESPACE__=>array(
            'path'=>realpath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'public'),
            'whitelist'=>array('css','jpg','jpeg','png','gif',),
        ),
    ),
);
